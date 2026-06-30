<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Simtabi\Laranail\Package\Tools\Commands\InstallCommand;
use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;
use Some\NamespacePath\Blog\Blog;
use Some\NamespacePath\Blog\Console\CategoryCreateCommand;
use Some\NamespacePath\Blog\Console\CategoryListCommand;
use Some\NamespacePath\Blog\Console\CommentApproveCommand;
use Some\NamespacePath\Blog\Console\CommentListCommand;
use Some\NamespacePath\Blog\Console\PostCreateCommand;
use Some\NamespacePath\Blog\Console\PostDeleteCommand;
use Some\NamespacePath\Blog\Console\PostListCommand;
use Some\NamespacePath\Blog\Console\PostPublishCommand;
use Some\NamespacePath\Blog\Console\PostUnpublishCommand;
use Some\NamespacePath\Blog\Console\PublishScheduledPostsCommand;
use Some\NamespacePath\Blog\Console\StatsCommand;
use Some\NamespacePath\Blog\Contracts\PostRepositoryInterface;
use Some\NamespacePath\Blog\Doctor\BlogDoctorCheck;
use Some\NamespacePath\Blog\Events\CategoryDeleted;
use Some\NamespacePath\Blog\Events\CategorySaved;
use Some\NamespacePath\Blog\Events\CommentApproved;
use Some\NamespacePath\Blog\Events\CommentCreated;
use Some\NamespacePath\Blog\Events\CommentDeleted;
use Some\NamespacePath\Blog\Events\PostCreated;
use Some\NamespacePath\Blog\Events\PostDeleted;
use Some\NamespacePath\Blog\Events\PostForceDeleted;
use Some\NamespacePath\Blog\Events\PostPublished;
use Some\NamespacePath\Blog\Events\PostRestored;
use Some\NamespacePath\Blog\Events\PostUnpublished;
use Some\NamespacePath\Blog\Events\PostUpdated;
use Some\NamespacePath\Blog\Events\TagDeleted;
use Some\NamespacePath\Blog\Events\TagSaved;
use Some\NamespacePath\Blog\Http\Middleware\EnsureApiAbility;
use Some\NamespacePath\Blog\Http\Middleware\EnsurePostIsPublished;
use Some\NamespacePath\Blog\Listeners\FlushBlogCache;
use Some\NamespacePath\Blog\Listeners\SendPostPublishedNotification;
use Some\NamespacePath\Blog\Livewire\CommentForm;
use Some\NamespacePath\Blog\Livewire\PostList;
use Some\NamespacePath\Blog\Models\Category;
use Some\NamespacePath\Blog\Models\Comment;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Observers\CommentObserver;
use Some\NamespacePath\Blog\Observers\PostObserver;
use Some\NamespacePath\Blog\Policies\CategoryPolicy;
use Some\NamespacePath\Blog\Policies\CommentPolicy;
use Some\NamespacePath\Blog\Policies\PostPolicy;
use Some\NamespacePath\Blog\Processing\BodyProcessor;
// @artifact:start plugin-filament
use Some\NamespacePath\Blog\Providers\Integrations\FilamentBlogServiceProvider;
// @artifact:end plugin-filament
// @artifact:start plugin-nova
use Some\NamespacePath\Blog\Providers\Integrations\NovaBlogServiceProvider;
// @artifact:end plugin-nova
use Some\NamespacePath\Blog\Repositories\CachingPostRepository;
use Some\NamespacePath\Blog\Repositories\EloquentPostRepository;
use Some\NamespacePath\Blog\Search\SearchManager;
use Some\NamespacePath\Blog\Services\CategoryService;
use Some\NamespacePath\Blog\Services\CommentService;
use Some\NamespacePath\Blog\Services\PostService;
use Some\NamespacePath\Blog\Services\TagService;
use Some\NamespacePath\Blog\View\Components\Alert;

class BlogServiceProvider extends PackageServiceProvider
{
    /**
     * Describe the package declaratively. The `vendor/package` name is required
     * by package-tools. Config is namespaced under the vendor/package, so every
     * lookup is `config('modules.blog.*')` and the published file lands at
     * `config/modules/blog.php`. Views/translations are namespaced too
     * (`modules/blog::…`); component tags use the slug prefix `modules-blog`.
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('modules/blog')
            ->setPublishTagId('blog')
            ->hasConfigFile()
            // Views + translations are namespaced under the vendor/package, so they
            // resolve as view('modules/blog::…') and __('modules/blog::blog.…').
            ->hasViews('modules/blog')
            ->hasTranslations()
            // @artifact:start asset-pipeline
            ->hasAssets()
            // @artifact:end asset-pipeline
            ->discoversMigrations()
            ->runsMigrations()
            ->hasRoute('web')
            // @artifact:start rest-api
            ->hasRoute('api')
            // @artifact:end rest-api
            ->registerMiddlewareAlias('blog.published', EnsurePostIsPublished::class)
            // @artifact:start rest-api
            ->registerMiddlewareAlias('blog.ability', EnsureApiAbility::class)
            // @artifact:end rest-api
            ->hasCommands([
                // @artifact:start scheduling
                PublishScheduledPostsCommand::class,
                // @artifact:end scheduling
                PostCreateCommand::class,
                PostListCommand::class,
                PostPublishCommand::class,
                PostUnpublishCommand::class,
                PostDeleteCommand::class,
                CategoryCreateCommand::class,
                CategoryListCommand::class,
                CommentListCommand::class,
                CommentApproveCommand::class,
                StatsCommand::class,
            ])
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->startWith(fn (InstallCommand $c) => $c->info('Installing the Blog package…'))
                    ->publishConfigFile()
                    // @artifact:start asset-pipeline
                    ->publishAssets()
                    // @artifact:end asset-pipeline
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->endWith(fn (InstallCommand $c) => $c->info('Blog is ready. Visit /blog or use the Blog facade.'));
            })
            ->hasDoctorCheck(BlogDoctorCheck::class)
            ->hasAboutSection('Blog', fn (): array => [
                'Posts' => Post::query()->count(),
                'Published' => Post::query()->published()->count(),
            ]);
    }

    /**
     * Container bindings. Runs in the register phase with config available.
     */
    public function packageRegistered(): void
    {
        $this->app->bind(PostRepositoryInterface::class, EloquentPostRepository::class);

        // Bind the services + extensibility singletons as predictable container
        // entries so consumers can `app->extend(...)` them to decorate (the last
        // extender wraps outermost). The manager backs the facade.
        $this->app->singleton(PostService::class);
        $this->app->singleton(CommentService::class);
        $this->app->singleton(CategoryService::class);
        $this->app->singleton(TagService::class);
        $this->app->singleton(SearchManager::class);
        $this->app->singleton(BodyProcessor::class);
        $this->app->singleton(Blog::class);

        // Optional panel adapters — each self-disables when its panel is absent,
        // so all three usage modes (package/module/plugin) pick them up safely.
        // @artifact:start plugin-filament
        $this->app->register(FilamentBlogServiceProvider::class);
        // @artifact:end plugin-filament
        // @artifact:start plugin-nova
        $this->app->register(NovaBlogServiceProvider::class);
        // @artifact:end plugin-nova
    }

    /**
     * Observers, gates, listeners, rate limiters and scheduling. Runs once
     * everything else is booted.
     */
    public function packageBooted(): void
    {
        // Stable polymorphic-type aliases (commentable/taggable) so the DB never
        // stores the find-replaceable placeholder FQCN. The package's OWN models are
        // code-canonical (Blog::morphMap()) and registered LAST, so they always alias
        // correctly even if a host clears/overrides the config; the config key is for
        // the host's own morphable models. Non-enforcing + additive by design.
        Relation::morphMap([
            ...(array) config('modules.blog.morph_map', []),
            ...Blog::morphMap(),
        ]);

        Post::observe(PostObserver::class);
        Comment::observe(CommentObserver::class);

        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::define('blog.publish', [PostPolicy::class, 'publish']);
        Gate::define('blog.moderate-comments', static fn ($user): bool => method_exists($user, 'hasRole') && $user->hasRole('admin'));

        // @artifact:start notifications
        Event::listen(PostPublished::class, SendPostPublishedNotification::class);
        // @artifact:end notifications

        // @artifact:start caching
        // Opt-in caching: decorate the repository (the worked example of container
        // extend() decoration) and bust it on any lifecycle event. Done in boot so
        // host config is fully merged; the repo is only resolved lazily afterwards.
        if (config('modules.blog.cache.enabled', false)) {
            $this->app->extend(
                PostRepositoryInterface::class,
                static fn (PostRepositoryInterface $repo, $app): PostRepositoryInterface => new CachingPostRepository($repo, $app->make('cache.store')),
            );

            Event::listen([
                PostCreated::class, PostUpdated::class, PostDeleted::class,
                PostRestored::class, PostForceDeleted::class, PostPublished::class, PostUnpublished::class,
                CommentCreated::class, CommentApproved::class, CommentDeleted::class,
                // Category/tag changes can change eager-loaded data on cached posts/feeds.
                CategorySaved::class, CategoryDeleted::class, TagSaved::class, TagDeleted::class,
            ], FlushBlogCache::class);
        }
        // @artifact:end caching

        // @artifact:start web-ui
        $this->registerComponents();
        $this->registerComposerPartials();
        // @artifact:end web-ui

        RateLimiter::for('blog-comments', static function (Request $request): Limit {
            $perMinute = (int) config('modules.blog.rate_limiting.comments_per_minute', 5);

            return Limit::perMinute($perMinute)->by($request->user()?->getAuthIdentifier() ?: $request->ip());
        });

        // @artifact:start scheduling
        $this->registerSchedule();
        // @artifact:end scheduling
    }

    // @artifact:start web-ui
    /**
     * Register Blade (<x-{prefix}::…>) and Livewire (<livewire:{prefix}.…>)
     * components under a unique, configurable prefix.
     *
     * Robustness: the configured prefix is normalised to a safe slug (an
     * invalid/empty value falls back to the default); components are registered
     * under BOTH the fixed {@see Blog::COMPONENT_PREFIX} and the host's prefix;
     * and the active prefix is shared to the package's own views as
     * `$blogComponentPrefix` so none of them hardcode it (rename-safe). Done in
     * packageBooted() because config is not yet merged inside configurePackage().
     */
    protected function registerComponents(): void
    {
        $prefix = $this->componentPrefix();

        $prefixes = array_values(array_unique([Blog::COMPONENT_PREFIX, $prefix]));

        // The View\Components namespace, derived by reflection so it survives a
        // root-namespace rename. Registered under each prefix → <x-{prefix}::alert>.
        $namespace = (new \ReflectionClass(Alert::class))->getNamespaceName();
        $componentsPath = $this->package->basePath('resources/views/components');

        foreach ($prefixes as $value) {
            Blade::componentNamespace($namespace, $value);
            // Classless support: any .blade.php in the components dir WITHOUT a
            // class is usable as <x-{prefix}::name>. Class components win their
            // names (Laravel resolves classes before anonymous), so they coexist.
            Blade::anonymousComponentPath($componentsPath, $value);
        }

        // Share helpers to the package's own views so none of them hardcode the
        // prefix, the layout or route names — keeping them rename/embed-safe.
        View::composer($this->package->viewNamespace().'::*', static function (\Illuminate\Contracts\View\View $view) use ($prefix): void {
            $view->with('blogComponentPrefix', $prefix);
            $view->with('blogLayout', (string) config('modules.blog.ui.layout', 'modules/blog::layouts.master'));
            $view->with('blogRoute', static function (string $name, mixed $params = []): string {
                $route = (string) config("modules.blog.ui.routes.{$name}", "blog.{$name}");

                return Route::has($route) ? route($route, $params) : '#';
            });
        });

        // @artifact:start livewire
        if (! class_exists(Livewire::class)) {
            return;
        }

        $register = static function () use ($prefixes): void {
            foreach ($prefixes as $value) {
                Livewire::component("{$value}.post-list", PostList::class);
                Livewire::component("{$value}.comment-form", CommentForm::class);
            }
        };

        $this->app->bound('livewire') ? $register() : $this->app->booted($register);
        // @artifact:end livewire
    }

    /**
     * Auto-inject data into the composer-backed sidebar partials via a single
     * declarative map (adding a widget = one entry). Each `@include('modules/blog::partials.*')`
     * receives its data with no controller wiring. Data comes from the Blog
     * manager (services), never queried here.
     */
    protected function registerComposerPartials(): void
    {
        $map = [
            'categories' => fn (Blog $blog): array => ['categories' => $blog->categories()],
            'recent-posts' => fn (Blog $blog): array => ['recentPosts' => $blog->recentPosts()],
            'popular-posts' => fn (Blog $blog): array => ['popularPosts' => $blog->popularPosts()],
            'archive' => fn (Blog $blog): array => ['archive' => $blog->archive()],
            'tags' => fn (Blog $blog): array => ['tags' => $blog->tags()],
        ];

        foreach ($map as $partial => $resolver) {
            View::composer("modules/blog::partials.{$partial}", function (\Illuminate\Contracts\View\View $view) use ($resolver): void {
                $view->with($resolver($this->app->make(Blog::class)));
            });
        }
    }

    /**
     * The normalised, collision-safe component prefix. A misconfigured value
     * (empty or non-slug) safely falls back to the package default.
     */
    protected function componentPrefix(): string
    {
        $prefix = Str::slug((string) config('modules.blog.components.prefix', Blog::COMPONENT_PREFIX));

        return $prefix !== '' ? $prefix : Blog::COMPONENT_PREFIX;
    }
    // @artifact:end web-ui

    // @artifact:start scheduling
    /**
     * Self-register the scheduled-publish command unless the host opts out.
     */
    protected function registerSchedule(): void
    {
        $this->callAfterResolving(Schedule::class, static function (Schedule $schedule): void {
            if (! config('modules.blog.scheduling.enabled', true)) {
                return;
            }

            $event = $schedule->command('blog:publish-scheduled')
                ->cron((string) config('modules.blog.scheduling.cron', '* * * * *'))
                ->withoutOverlapping()
                ->runInBackground();

            if (($timezone = config('modules.blog.scheduling.timezone')) !== null) {
                $event->timezone((string) $timezone);
            }

            if (config('modules.blog.scheduling.on_one_server', false)) {
                $event->onOneServer();
            }
        });
    }
    // @artifact:end scheduling
}
