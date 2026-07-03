<?php

namespace Simtabi\Laranail\Package\Scaffolder\Providers;

use Composer\InstalledVersions;
use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Foundation\Events\DiscoverEvents;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Illuminate\Translation\Translator;
use Override;
use Simtabi\Laranail\Package\Scaffolder\Contracts\ActivatorInterface;
use Simtabi\Laranail\Package\Scaffolder\Contracts\RepositoryInterface;
use Simtabi\Laranail\Package\Scaffolder\Exceptions\InvalidActivatorClass;
use Simtabi\Laranail\Package\Scaffolder\Facades\Module;
use Simtabi\Laranail\Package\Scaffolder\Laravel\LaravelFileRepository;
use Simtabi\Laranail\Package\Scaffolder\Laravel\Module as LaravelModule;
use Simtabi\Laranail\Package\Scaffolder\Support\ModuleManifest;
use Simtabi\Laranail\Package\Scaffolder\Support\Stub;
use Simtabi\Laranail\Package\Scaffolder\Traits\PathNamespace;
use Simtabi\Laranail\Package\Scaffolder\Traits\ResolvesModuleNamespace;
use SplFileInfo;

class LaravelModulesServiceProvider extends ModulesServiceProvider
{
    use PathNamespace;
    use ResolvesModuleNamespace;

    /**
     * Booting the package.
     */
    public function boot(): void
    {
        $this->registerNamespaces();
        $this->registerEventDiscovery();

        AboutCommand::add('Package Scaffolder', [
            'Version' => fn () => InstalledVersions::getPrettyVersion('laranail/package-scaffolder'),
        ]);

        // Create @module() blade directive.
        Blade::if('module', fn (string $name): bool|\Simtabi\Laranail\Package\Scaffolder\Support\Module => module($name));
    }

    /**
     * Make Laravel's event discovery resolve module listener paths to their
     * real namespace. Without this, files under Modules/{Module}/app/Listeners
     * are mapped using the application namespace and never resolve (#2128).
     */
    protected function registerEventDiscovery(): void
    {
        if (! $this->app['config']->get('modules.auto-discover.events', true)) {
            return;
        }

        DiscoverEvents::guessClassNamesUsing(fn (SplFileInfo $file, string $basePath): string => $this->moduleClassFromFile($file)
            ?? $this->defaultClassFromFile($file, $basePath));
    }

    /**
     * Resolve the fully-qualified class name for a file that belongs to a
     * module, using the module's real namespace and app folder.
     */
    protected function moduleClassFromFile(SplFileInfo $file): ?string
    {
        $realPath = $file->getRealPath();

        if ($realPath === false) {
            return null;
        }

        foreach (Module::all() as $module) {
            $modulePath = realpath($module->getPath());
            if ($modulePath === false) {
                continue;
            }
            if (! str_starts_with($realPath, $modulePath.DIRECTORY_SEPARATOR)) {
                continue;
            }

            $namespace = $this->getModuleNamespace($module);

            if ($namespace === null) {
                return null;
            }

            $relative = ltrim(substr($realPath, strlen($modulePath)), DIRECTORY_SEPARATOR);
            $relative = $this->strip_app_folder(str_replace(DIRECTORY_SEPARATOR, '/', $relative));
            $relative = preg_replace('/\.php$/', '', $relative);

            return $namespace.'\\'.str_replace('/', '\\', $relative);
        }

        return null;
    }

    /**
     * Laravel's default DiscoverEvents class-name resolution, used as the
     * fallback for files that do not belong to a module.
     */
    protected function defaultClassFromFile(SplFileInfo $file, string $basePath): string
    {
        $class = trim(Str::replaceFirst($basePath, '', $file->getRealPath()), DIRECTORY_SEPARATOR);

        return ucfirst(Str::camel(str_replace(
            [DIRECTORY_SEPARATOR, ucfirst(basename(app()->path())).'\\'],
            ['\\', app()->getNamespace()],
            ucfirst(Str::replaceLast('.php', '', $class))
        )));
    }

    /**
     * Register the service provider.
     */
    #[Override]
    public function register(): void
    {
        $this->registerServices();
        $this->setupStubPath();
        $this->registerProviders();

        $this->registerMigrations();
        $this->registerTranslations();

        $this->mergeConfigFrom(__DIR__.'/../../config/config.php', 'modules');
        $this->mergeConfigFrom(__DIR__.'/../../config/artifacts.php', 'artifacts');

        $this->registerModules();
    }

    /**
     * Setup stub path.
     */
    public function setupStubPath(): void
    {
        $path = $this->app['config']->get('modules.stubs.path') ?? dirname(__DIR__, 2).'/stubs';
        Stub::setBasePath($path);

        $this->app->booted(function ($app): void {
            /** @var RepositoryInterface $moduleRepository */
            $moduleRepository = $app[RepositoryInterface::class];
            if ($moduleRepository->config('stubs.enabled') === true) {
                Stub::setBasePath($moduleRepository->config('stubs.path'));
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function registerServices()
    {
        $this->app->singleton(RepositoryInterface::class, function ($app): LaravelFileRepository {
            $path = $app['config']->get('modules.paths.modules');

            return new LaravelFileRepository($app, $path);
        });
        $this->app->singleton(ActivatorInterface::class, function ($app): object {
            $activator = $app['config']->get('modules.activator');
            $class = $app['config']->get('modules.activators.'.$activator)['class'];

            if ($class === null) {
                throw InvalidActivatorClass::missingConfig();
            }

            return new $class($app);
        });
        $this->app->alias(RepositoryInterface::class, 'modules');

        $this->app->singleton(
            ModuleManifest::class,
            fn (): ModuleManifest => new ModuleManifest(
                new Filesystem,
                app(RepositoryInterface::class)->getScanPaths(),
                $this->getCachedModulePath(),
                app(ActivatorInterface::class)
            )
        );

    }

    protected function registerMigrations(): void
    {
        if (! $this->app['config']->get('modules.auto-discover.migrations', true)) {
            return;
        }

        $this->app->resolving(Migrator::class, function (Migrator $migrator): void {
            $migration_path = $this->app['config']->get('modules.paths.generator.migration.path');
            collect(Module::allEnabled())
                ->each(function (LaravelModule $module) use ($migration_path, $migrator): void {
                    $migrator->path($module->getExtraPath($migration_path));
                });
        });
    }

    protected function registerTranslations(): void
    {
        if (! $this->app['config']->get('modules.auto-discover.translations', true)) {
            return;
        }
        $this->callAfterResolving('translator', function (TranslatorContract $translator): void {
            if (! $translator instanceof Translator) {
                return;
            }

            collect(Module::allEnabled())
                ->each(function (LaravelModule $module) use ($translator): void {
                    $path = $module->getExtraPath($this->app['config']->get('modules.paths.generator.lang.path'));
                    $translator->addNamespace($module->getLowerName(), $path);
                    $translator->addJsonPath($path);
                });
        });
    }
}
