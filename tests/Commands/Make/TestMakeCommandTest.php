<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Package\Scaffolder\Contracts\ActivatorInterface;
use Simtabi\Laranail\Package\Scaffolder\Contracts\RepositoryInterface;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class TestMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

    private Filesystem $finder;

    private string $modulePath;

    private ActivatorInterface $activator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->createModule();
        $this->modulePath = $this->getModuleBasePath();
        $this->activator = $this->app[ActivatorInterface::class];
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        $this->activator->reset();
        parent::tearDown();
    }

    public function test_it_generates_a_new_unit_test_class(): void
    {
        $code = $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog']);

        $this->assertTrue(is_file($this->modulePath.'/tests/Unit/EloquentPostRepositoryTest.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generates_a_new_feature_test_class(): void
    {
        $code = $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog', '--feature' => true]);

        $this->assertTrue(is_file($this->modulePath.'/tests/Feature/EloquentPostRepositoryTest.php'));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_unit_file_with_content(): void
    {
        $code = $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/tests/Unit/EloquentPostRepositoryTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_feature_file_with_content(): void
    {
        $code = $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog', '--feature' => true]);

        $file = $this->finder->get($this->modulePath.'/tests/Feature/EloquentPostRepositoryTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_unit_namespace(): void
    {
        $this->app['config']->set('modules.paths.generator.test-unit.path', 'SuperTests/Unit');

        $code = $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog']);

        $file = $this->finder->get($this->getModuleBasePath().'/SuperTests/Unit/EloquentPostRepositoryTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_unit_namespace_specific(): void
    {
        $this->app['config']->set('modules.paths.generator.test.namespace', 'SuperTests\\Unit');

        $code = $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog']);

        $file = $this->finder->get($this->modulePath.'/tests/Unit/EloquentPostRepositoryTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_feature_namespace(): void
    {
        $this->app['config']->set('modules.paths.generator.test-feature.path', 'SuperTests/Feature');

        $code = $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog', '--feature' => true]);

        $file = $this->finder->get($this->modulePath.'/SuperTests/Feature/EloquentPostRepositoryTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_feature_namespace_specific(): void
    {
        $this->app['config']->set('modules.paths.generator.test-feature.namespace', 'SuperTests\\Feature');

        $code = $this->artisan('module:make-test', ['name' => 'EloquentPostRepositoryTest', 'module' => 'Blog', '--feature' => true]);

        $file = $this->finder->get($this->getModuleBasePath().'/tests/Feature/EloquentPostRepositoryTest.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
