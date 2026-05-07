<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Providers;

use Illuminate\Support\ServiceProvider;
use Simtabi\Laranail\PackageScaffolder\Commands\GeneratePackageCommand;
use Simtabi\Laranail\PackageScaffolder\Services\BlueprintService;
use Simtabi\Laranail\PackageScaffolder\Services\PackageStructureService;
use Simtabi\Laranail\PackageScaffolder\Services\PlaceholderService;
use Simtabi\Laranail\PackageScaffolder\Services\StubService;

/**
 * Scaffolder Service Provider
 *
 * Registers scaffolder services, commands, and configuration
 */
class ScaffolderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge configuration from config/scaffolder.php
        $this->mergeConfigFrom(
            __DIR__.'/../../../config/scaffolder.php',
            'scaffolder'
        );

        // Register services as singletons
        $this->app->singleton(BlueprintService::class);
        $this->app->singleton(StubService::class);
        $this->app->singleton(PlaceholderService::class);
        $this->app->singleton(PackageStructureService::class, function ($app) {
            return new PackageStructureService(
                $app->make(StubService::class),
                $app->make(PlaceholderService::class)
            );
        });
    }

    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../../../config/scaffolder.php' => config_path('scaffolder.php'),
        ], 'packager-scaffolder-config');

        // Publish stubs
        $this->publishes([
            __DIR__.'/../../../stubs' => resource_path('stubs/packager'),
        ], 'packager-stubs');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                GeneratePackageCommand::class,
            ]);
        }
    }
}
