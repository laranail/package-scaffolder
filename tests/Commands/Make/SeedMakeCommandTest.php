<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Package\Scaffolder\Contracts\ActivatorInterface;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;

class SeedMakeCommandTest extends BaseTestCase
{
    private Filesystem $finder;

    private string $modulePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->modulePath = base_path('modules/Blog');
        $this->artisan('module:make', ['name' => ['Blog']]);
    }

    protected function tearDown(): void
    {
        $this->artisan('module:delete', ['module' => ['Blog'], '--force' => true]);
        $this->app[ActivatorInterface::class]->reset();
        parent::tearDown();
    }

    public function test_it_auto_generates_the_base_seeder_when_missing(): void
    {
        $basePath = $this->modulePath.'/database/seeders/BlogDatabaseSeeder.php';
        $this->finder->delete($basePath);
        $this->assertFalse($this->finder->exists($basePath));

        $code = $this->artisan('module:make-seed', ['name' => 'Posts', 'module' => 'Blog']);

        $this->assertTrue($this->finder->exists($this->modulePath.'/database/seeders/PostsSeeder.php'));
        $this->assertTrue($this->finder->exists($basePath), 'the base seeder should be auto-generated (#2147)');
        $this->assertSame(0, $code);
    }

    public function test_it_can_skip_the_base_seeder_with_without_base(): void
    {
        $basePath = $this->modulePath.'/database/seeders/BlogDatabaseSeeder.php';
        $this->finder->delete($basePath);

        $code = $this->artisan('module:make-seed', ['name' => 'Posts', 'module' => 'Blog', '--without-base' => true]);

        $this->assertTrue($this->finder->exists($this->modulePath.'/database/seeders/PostsSeeder.php'));
        $this->assertFalse($this->finder->exists($basePath));
        $this->assertSame(0, $code);
    }
}
