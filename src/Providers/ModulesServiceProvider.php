<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Providers;

use Override;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\ProviderRepository;
use Simtabi\Laranail\Package\Scaffolder\Support\ModuleManifest;
use Simtabi\Laranail\Package\Scaffolder\Contracts\RepositoryInterface;

abstract class ModulesServiceProvider extends ServiceProvider
{
    /**
     * The container alias the module repository is registered under.
     *
     * Container aliases share one flat map with the host application and every other
     * package, so the name carries the vendor and the package slug.
     */
    public const string CONTAINER_ALIAS = 'laranail.package-scaffolder.modules';

    /**
     * The bare alias, kept so code written against nwidart/laravel-modules (`app('modules')`,
     * `$app['modules']`) keeps resolving the same repository.
     *
     * @deprecated since 0.1, removable in the next minor after 0.1. Use {@see self::CONTAINER_ALIAS}
     *             or {@see RepositoryInterface}. It stays a plain container alias rather than a
     *             warning binding, so `isAlias()`/`getAlias()` and instance swaps behave as before;
     *             the deprecation is documented, not raised at runtime.
     */
    public const string DEPRECATED_CONTAINER_ALIAS = 'modules';

    /**
     * Booting the package.
     */
    public function boot() {}

    /**
     * Register all modules.
     */
    #[Override]
    public function register() {}

    /**
     * Get the services provided by the provider.
     */
    #[Override]
    public function provides(): array
    {
        return [RepositoryInterface::class, self::CONTAINER_ALIAS, self::DEPRECATED_CONTAINER_ALIAS];
    }

    /**
     * Register all modules.
     */
    protected function registerModules()
    {
        $manifest = app(ModuleManifest::class);

        (new ProviderRepository($this->app, new Filesystem, $this->getCachedModulePath()))
            ->load($manifest->getProviders());

        $manifest->registerFiles();

    }

    /**
     * Register package's namespaces.
     */
    protected function registerNamespaces()
    {
        $configPath = __DIR__ . '/../../config/config.php';
        $stubsPath = dirname(__DIR__, 2) . '/stubs';

        // A path, not a dotted key: the config key is
        // `laranail.package-scaffolder.modules`, which Laravel reads from
        // config/laranail/package-scaffolder/modules.php.
        //
        // The tags were `config`, `stubs` and `vite` — about as generic as a
        // publish tag can be. `vendor:publish --tag=config` would fire every
        // package that claimed it, in registration order.
        $this->publishes([
            $configPath => config_path('laranail/package-scaffolder/modules.php'),
        ], 'laranail::package-scaffolder-config');

        // artifacts.php had no tag, so the file the configuration docs call
        // user-editable could not be published at all.
        $this->publishes([
            dirname(__DIR__, 2) . '/config/artifacts.php' => config_path('laranail/package-scaffolder/artifacts.php'),
        ], 'laranail::package-scaffolder-artifacts');

        $this->publishes([
            $stubsPath => base_path('stubs/laranail-package-scaffolder'),
        ], 'laranail::package-scaffolder-stubs');

        $this->publishes([
            __DIR__ . '/../../scripts/vite-module-loader.js' => base_path('vite-module-loader.js'),
        ], 'laranail::package-scaffolder-vite');
    }

    /**
     * Register the service provider.
     */
    abstract protected function registerServices();

    /**
     * Register providers.
     */
    protected function registerProviders()
    {
        $this->app->register(ConsoleServiceProvider::class);
        $this->app->register(ContractsServiceProvider::class);
    }

    protected function getCachedModulePath()
    {
        return Str::replaceLast('services.php', 'modules.php', $this->app->getCachedServicesPath());
    }
}
