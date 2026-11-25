<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Tests;

use Simtabi\Laranail\Package\Scaffolder\Facades\Module;
use Simtabi\Laranail\Package\Scaffolder\Contracts\ActivatorInterface;
use Simtabi\Laranail\Package\Scaffolder\Contracts\RepositoryInterface;
use Simtabi\Laranail\Package\Scaffolder\Exceptions\InvalidActivatorClass;

class LaravelModulesServiceProviderTest extends BaseTestCase
{
    public function test_it_binds_modules_key_to_repository_class(): void
    {
        $this->assertInstanceOf(RepositoryInterface::class, app(RepositoryInterface::class));
        $this->assertInstanceOf(RepositoryInterface::class, app('modules'));
    }

    /**
     * The repository holds state (`addLocation()`, `setStubPath()`). ContractsServiceProvider used to
     * re-bind it non-shared over this provider's singleton, so every resolve built a fresh one: a
     * location added through the facade was invisible to `app('modules')`, and one added through the
     * container was invisible to everyone.
     */
    public function test_the_repository_is_one_shared_instance_across_container_aliases_and_facade(): void
    {
        $this->assertTrue($this->app->getBindings()[RepositoryInterface::class]['shared']);
        $this->assertSame(app(RepositoryInterface::class), app(RepositoryInterface::class));
        $this->assertSame(app(RepositoryInterface::class), app('modules'));
        $this->assertSame(app(RepositoryInterface::class), app('laranail.package-scaffolder.modules'));

        app(RepositoryInterface::class)->addLocation('/tmp/scaffolder-shared-probe');

        $this->assertContains('/tmp/scaffolder-shared-probe', Module::getPaths());
        $this->assertContains('/tmp/scaffolder-shared-probe', app('modules')->getPaths());
    }

    public function test_it_binds_activator_to_activator_class(): void
    {
        $this->assertInstanceOf(ActivatorInterface::class, app(ActivatorInterface::class));
    }

    public function test_it_throws_exception_if_config_is_invalid(): void
    {
        $this->expectException(InvalidActivatorClass::class);

        $this->app['config']->set('laranail.package-scaffolder.modules.activators.file', ['class' => null]);

        app()->forgetInstance(ActivatorInterface::class);

        $this->assertInstanceOf(ActivatorInterface::class, app(ActivatorInterface::class));
    }
}
