<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Simtabi\Laranail\Package\Scaffolder\Providers\ConsoleServiceProvider;
use Simtabi\Laranail\Package\Scaffolder\Providers\LaravelModulesServiceProvider;

abstract class BaseTestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (method_exists($this, 'withoutMockingConsoleOutput')) {
            $this->withoutMockingConsoleOutput();
        }
        // $this->setUpDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanGeneratedModules(base_path());

        parent::tearDown();
    }

    /**
     * Defensively clear generated modules + the cached manifest from any prior (or
     * interrupted) run in the shared Testbench skeleton — a leftover modules/{Name}
     * + bootstrap/cache/modules.php otherwise registers a provider whose class isn't
     * autoloadable and fails EVERY subsequent boot. Done in getEnvironmentSetUp (i.e.
     * before the module provider boots and reads the manifest).
     */
    private function cleanGeneratedModules(string $basePath): void
    {
        $files = new Filesystem;
        $files->deleteDirectory($basePath.'/modules');

        $manifest = $basePath.'/bootstrap/cache/modules.php';
        if ($files->exists($manifest)) {
            $files->delete($manifest);
        }
    }

    private function resetDatabase()
    {
        $this->artisan('migrate:reset', [
            '--database' => 'sqlite',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelModulesServiceProvider::class,
        ];
    }

    /**
     * Set up the environment.
     *
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app)
    {
        // Runs before the module provider boots / reads the manifest — clean first.
        $this->cleanGeneratedModules($app->basePath());

        $module_config = require __DIR__.'/../config/config.php';

        // enable all generators
        array_walk($module_config['paths']['generator'], function (&$item) {
            $item['generate'] = true;
        });

        $app['config']->set('app.asset_url', null);
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('modules.paths.modules', base_path('modules'));
        $app['config']->set('modules.paths', [
            'modules' => base_path('modules'),
            'assets' => public_path('modules'),
            'migration' => base_path('database/migrations'),
            'app_folder' => $module_config['paths']['app_folder'],
            'generator' => $module_config['paths']['generator'],
        ]);

        $app['config']->set('modules.composer-output', true);

        $app['config']->set('modules.commands', ConsoleServiceProvider::defaultCommands()->toArray());
    }

    protected function setUpDatabase()
    {
        $this->resetDatabase();
    }

    protected function createModule(string $moduleName = 'Blog'): int
    {
        return $this->artisan('module:make', ['name' => [$moduleName]]);
    }

    protected function getModuleAppPath(string $moduleName = 'Blog'): string
    {
        return base_path("modules/$moduleName/").rtrim(config('modules.paths.app_folder'), '/');
    }

    protected function getModuleBasePath(string $moduleName = 'Blog'): string
    {
        return base_path("modules/$moduleName");
    }
}
