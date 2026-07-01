<?php

namespace Simtabi\Laranail\Package\Scaffolder\Providers;

use Simtabi\Laranail\Package\Scaffolder\Contracts\ActivatorInterface;
use Simtabi\Laranail\Package\Scaffolder\Contracts\RepositoryInterface;
use Simtabi\Laranail\Package\Scaffolder\Lumen\LumenFileRepository;
use Simtabi\Laranail\Package\Scaffolder\Support\Stub;

class LumenModulesServiceProvider extends ModulesServiceProvider
{
    /**
     * Booting the package.
     */
    public function boot()
    {
        $this->setupStubPath();
    }

    /**
     * Register all modules.
     */
    public function register()
    {
        $this->registerNamespaces();
        $this->registerServices();
        $this->registerModules();
        $this->registerProviders();
    }

    /**
     * Setup stub path.
     */
    public function setupStubPath()
    {
        Stub::setBasePath(dirname(__DIR__).'/Commands/stubs');

        if (app('modules')->config('stubs.enabled') === true) {
            Stub::setBasePath(app('modules')->config('stubs.path'));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function registerServices()
    {
        $this->app->singleton(RepositoryInterface::class, function ($app) {
            $path = $app['config']->get('modules.paths.modules');

            return new LumenFileRepository($app, $path);
        });
        $this->app->singleton(ActivatorInterface::class, function ($app) {
            $activator = $app['config']->get('modules.activator');
            $class = $app['config']->get('modules.activators.'.$activator)['class'];

            return new $class($app);
        });
        $this->app->alias(RepositoryInterface::class, 'modules');
    }
}
