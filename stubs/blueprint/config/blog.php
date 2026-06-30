<?php

declare(strict_types=1);

use Some\NamespacePath\Blog\Blog;
use Some\NamespacePath\Blog\Enums\PostStatus;
use Some\NamespacePath\Blog\Models\Category;
use Some\NamespacePath\Blog\Models\Comment;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Models\Tag;
use Some\NamespacePath\Blog\Processing\Stages\SanitizeHtmlStage;

return [

    /*
    |--------------------------------------------------------------------------
    | Package name
    |--------------------------------------------------------------------------
    */

    'name' => 'Blog',

    /*
    |--------------------------------------------------------------------------
    | User model
    |--------------------------------------------------------------------------
    |
    | The host application's authenticatable model used for post authors and
    | comment authors. The package never hard-depends on it: it is referenced
    | only through this config value, so any User model works.
    |
    */

    'user_model' => env('BLOG_USER_MODEL', '\\App\\Models\\User'),

    // @artifact:start web-ui
    /*
    |--------------------------------------------------------------------------
    | Components
    |--------------------------------------------------------------------------
    |
    | The prefix for the package's Blade (<x-{prefix}::…>) and Livewire
    | (<livewire:{prefix}.…>) components. Defaults to the vendor/package slug
    | "modules-blog" (tags can't contain "/"); override it for your app if you like.
    | Views/translations use the matching "modules/blog::" namespace.
    |
    */

    'components' => [
        'prefix' => Blog::COMPONENT_PREFIX,
    ],
    // @artifact:end web-ui

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'per_page' => 15,
        'max_per_page' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Comments
    |--------------------------------------------------------------------------
    |
    | "allow_guests" lets unauthenticated visitors post comments. New comments
    | are never auto-approved unless "auto_approve" is explicitly enabled.
    | "honeypot" is the name of a hidden field that must stay empty, and
    | "min_submit_seconds" rejects submissions faster than a human could type.
    |
    */

    'comments' => [
        'allow_guests' => true,
        'auto_approve' => false,
        'honeypot' => 'website',
        'min_submit_seconds' => 2,
        'max_length' => 2000,
    ],

    // @artifact:start notifications
    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        'channels' => ['mail'],
    ],
    // @artifact:end notifications

    // @artifact:start scheduling
    /*
    |--------------------------------------------------------------------------
    | Scheduling
    |--------------------------------------------------------------------------
    |
    | The package self-registers the "blog:publish-scheduled" command on the
    | host scheduler. Disable to own the schedule yourself. "timezone" interprets
    | the cron expression (null = the app's default); "on_one_server" runs the task
    | on a single server in a multi-server deployment (needs a shared cache lock).
    |
    */

    'scheduling' => [
        'enabled' => true,
        'cron' => '* * * * *',
        'timezone' => env('BLOG_SCHEDULE_TIMEZONE'),
        'on_one_server' => env('BLOG_SCHEDULE_ON_ONE_SERVER', false),
    ],
    // @artifact:end scheduling

    /*
    |--------------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------------
    |
    | Both route groups can be toggled, prefixed and wrapped in middleware
    | without touching the package. The API write routes default to Sanctum but
    | you may swap that for any guard/middleware your app uses.
    |
    */

    'routes' => [
        'web' => [
            'enabled' => true,
            'prefix' => 'blog',
            // Base middleware (public reads + guest comment submission).
            'middleware' => ['web'],
            // Added to author write routes. Add 'verified' if your User implements MustVerifyEmail.
            'auth_middleware' => ['auth'],
        ],
        // @artifact:start rest-api
        'api' => [
            'enabled' => true,
            'prefix' => 'api/v1',
            'name' => 'api.blog.',
            // Applied to all API routes.
            'middleware' => ['api', 'throttle:api'],
            // Additional middleware applied only to write (mutating) routes.
            'auth_middleware' => ['auth:sanctum'],
            // Optional Sanctum token abilities/scopes. When non-null, the matching
            // routes additionally require a token with that ability. Null = no gate
            // (won't break plain-token setups).
            'abilities' => [
                'write' => env('BLOG_API_WRITE_ABILITY'),       // e.g. 'blog:write'
                'moderate' => env('BLOG_API_MODERATE_ABILITY'), // e.g. 'blog:moderate'
            ],
        ],
        // @artifact:end rest-api
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate limiting
    |--------------------------------------------------------------------------
    */

    'rate_limiting' => [
        // Max comment submissions per minute, per IP / per user.
        'comments_per_minute' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Post bodies are stripped to an allow-list of HTML tags on save (a
    | dependency-free baseline; pair with a full purifier for rich input).
    | Disable if you sanitize upstream.
    |
    */

    'security' => [
        'sanitize_html' => true,
        'allowed_tags' => '<p><br><a><strong><em><ul><ol><li><blockquote><h2><h3><h4><code><pre><img>',
    ],

    /*
    |--------------------------------------------------------------------------
    | Body processing
    |--------------------------------------------------------------------------
    |
    | Post bodies pass through these ordered save-time stages (an Illuminate
    | Pipeline) at the model layer. The default sanitizes HTML. Add your own with
    | Blog::pipe(MyStage::class), the 'blog.body.stages' container tag, or here
    | (class-strings only, so config:cache stays serializable). "markdown" enables
    | render-on-display of Markdown bodies (requires league/commonmark).
    |
    */

    'processing' => [
        'markdown' => env('BLOG_MARKDOWN', false),
        'stages' => [
            SanitizeHtmlStage::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    |
    | The active post-search driver. "database" (default) is the built-in LIKE
    | search; "scout" requires laravel/scout + the Searchable trait on Post.
    | Register custom drivers with Blog::extendSearch('name', fn ($app) => ...).
    |
    */

    'search' => [
        'driver' => env('BLOG_SEARCH_DRIVER', 'database'),
    ],

    // @artifact:start caching
    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Opt-in read caching (decorates the post repository). Invalidation is
    | event-driven, so writes from any source bust it. Portable across stores.
    |
    */

    'cache' => [
        'enabled' => env('BLOG_CACHE', false),
        'ttl' => env('BLOG_CACHE_TTL', 3600),
        // Short negative TTL: how long a "missing slug" is cached (anti cache-penetration).
        'miss_ttl' => env('BLOG_CACHE_MISS_TTL', 60),
    ],
    // @artifact:end caching

    // How popularPosts() ranks: 'comments' (default) or 'views'.
    'popular_by' => env('BLOG_POPULAR_BY', 'comments'),

    /*
    |--------------------------------------------------------------------------
    | Morph map (host models)
    |--------------------------------------------------------------------------
    |
    | The package's OWN models (Post/Comment/Category/Tag) are aliased in code
    | (Blog::morphMap()) and always registered, so their polymorphic records store a
    | stable alias instead of the find-replaceable placeholder FQCN — you don't need
    | to list them here, and clearing this can't break them. Register YOUR OWN
    | commentable/taggable models here (alias => class) so they store an alias too:
    |
    |   'product' => \App\Models\Product::class,
    |
    */

    'morph_map' => [
        // 'product' => \App\Models\Product::class,
    ],

    // Estimated reading speed for the reading_time accessor.
    'reading' => [
        'words_per_minute' => 200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    |
    | Field limits used by the Form Requests (via the ProvidesPostRules /
    | ProvidesCategoryRules traits) and the package's custom Rule classes — so no
    | length literal is hardcoded in a request. "reserved_slugs" are rejected by
    | the NotReservedSlug rule (a post slug must not shadow a web route segment).
    |
    */

    'validation' => [
        'title_max' => 255,
        'slug_max' => 255,
        'excerpt_max' => 500,
        'body_max' => 65535,
        'meta_title_max' => 255,
        'meta_description_max' => 500,
        'url_max' => 2048,
        'tag_max' => 50,
        'category_description_max' => 1000,
        'author_name_max' => 100,
        'email_max' => 255,
        // Max length of a query-string filter value (category/tag/search).
        'filter_max' => 255,
        'reserved_slugs' => ['create', 'edit', 'feed', 'sitemap', 'sitemap.xml'],
    ],

    // @artifact:start feeds
    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Toggle the optional public endpoints. The RSS/Atom feed and sitemap are
    | served from the web routes when enabled.
    |
    */

    'features' => [
        'rss' => true,
        'sitemap' => true,
        'feed_limit' => 20,
        'related_limit' => 5,
        // Default size of the recent/popular/featured widgets.
        'widget_limit' => 5,
        // Count page views on the web show route (Blog::recordView).
        'count_views' => env('BLOG_COUNT_VIEWS', false),
    ],
    // @artifact:end feeds

    /*
    |--------------------------------------------------------------------------
    | UI
    |--------------------------------------------------------------------------
    |
    | Default status applied to new posts, and the default ordering column for
    | the public feed. "sortable" is the allow-list the API validates the
    | ?sort= parameter against (prevents arbitrary order-by injection).
    |
    */

    'ui' => [
        'default_status' => PostStatus::Draft->value,
        'sortable' => ['published_at', 'created_at', 'title', 'views'],

        /*
        | The parent Blade layout the package's own pages @extends. Point this at
        | your host app's layout to embed the pages in your own chrome — or ignore
        | the pages entirely and drop the <x-{prefix}::posts|post|comments> section
        | components into your own views.
        */
        'layout' => 'modules/blog::layouts.master',

        /*
        | Route names the components/partials link to. Remap them if you mount the
        | package's routes under different names; missing routes degrade to "#".
        */
        'routes' => [
            'index' => 'blog.index',
            'show' => 'blog.show',
            'create' => 'blog.create',
            'edit' => 'blog.edit',
            'store' => 'blog.store',
            'update' => 'blog.update',
            'destroy' => 'blog.destroy',
            'comment_store' => 'blog.comments.store',
            'feed' => 'blog.feed',
            'sitemap' => 'blog.sitemap',
        ],

        /*
        | Host auth route names used by the auth components (login/register/logout).
        */
        'auth' => [
            'login' => 'login',
            'register' => 'register',
            'logout' => 'logout',
        ],

        // @artifact:start asset-pipeline
        /*
        | The CSS bundle the <x-{prefix}::assets /> component loads. The package
        | ships three independent bundles — Tailwind v4, Bootstrap 5, and "vanilla"
        | (its own framework-agnostic base styles, bring-your-own CSS). Use "none"
        | to load nothing and style everything yourself.
        */
        'framework' => env('BLOG_UI_FRAMEWORK', 'tailwind'), // tailwind | bootstrap | vanilla | none

        'assets' => [
            // Where the published Vite manifest + build live (relative to public/).
            'base' => 'vendor/blog/build',
            // Vite manifest path. Defaults to the published location; falls back
            // to the package-local build during local development.
            'manifest' => public_path('vendor/blog/build/.vite/manifest.json'),
            // Entry points (Vite manifest keys) per framework.
            'bundles' => [
                'tailwind' => [
                    'resources/assets/css/tailwind.css',
                    'resources/assets/scripts/tailwind.js',
                ],
                'bootstrap' => [
                    'resources/assets/sass/bootstrap.scss',
                    'resources/assets/scripts/bootstrap.js',
                ],
                'vanilla' => [
                    'resources/assets/scripts/app.js',
                ],
            ],
        ],
        // @artifact:end asset-pipeline
    ],

];
