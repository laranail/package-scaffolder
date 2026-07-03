<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Activators;

use Illuminate\Filesystem\Filesystem;
use Override;
use Simtabi\Laranail\Package\Scaffolder\Activators\FileActivator;
use Simtabi\Laranail\Package\Scaffolder\Laravel\Module;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class FileActivatorTest extends BaseTestCase
{
    use MatchesSnapshots;

    private TestModule $module;

    private Filesystem $finder;

    private FileActivator $activator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->module = new TestModule($this->app, 'Recipe', __DIR__.'/stubs/valid/Recipe');
        $this->finder = $this->app['files'];
        $this->activator = new FileActivator($this->app);
    }

    protected function tearDown(): void
    {
        $this->activator->reset();
        parent::tearDown();
    }

    public function test_it_creates_valid_json_file_after_enabling(): void
    {
        $this->activator->enable($this->module);
        $this->assertMatchesSnapshot($this->finder->get($this->activator->getStatusesFilePath()));

        $this->activator->setActive($this->module, true);
        $this->assertMatchesSnapshot($this->finder->get($this->activator->getStatusesFilePath()));
    }

    public function test_it_creates_valid_json_file_after_disabling(): void
    {
        $this->activator->disable($this->module);
        $this->assertMatchesSnapshot($this->finder->get($this->activator->getStatusesFilePath()));

        $this->activator->setActive($this->module, false);
        $this->assertMatchesSnapshot($this->finder->get($this->activator->getStatusesFilePath()));
    }

    public function test_it_can_check_module_enabled_status(): void
    {
        $this->activator->enable($this->module);
        $this->assertTrue($this->activator->hasStatus($this->module, true));

        $this->activator->setActive($this->module, true);
        $this->assertTrue($this->activator->hasStatus($this->module, true));
    }

    public function test_it_can_check_module_disabled_status(): void
    {
        $this->activator->disable($this->module);
        $this->assertTrue($this->activator->hasStatus($this->module, false));

        $this->activator->setActive($this->module, false);
        $this->assertTrue($this->activator->hasStatus($this->module, false));
    }

    public function test_it_can_check_status_of_module_that_hasnt_been_enabled_or_disabled(): void
    {
        $this->assertTrue($this->activator->hasStatus($this->module, false));
    }
}

class TestModule extends Module
{
    #[Override]
    public function registerProviders(): void
    {
        parent::registerProviders();
    }
}
