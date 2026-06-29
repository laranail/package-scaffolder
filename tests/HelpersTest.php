<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Simtabi\Laranail\Package\Scaffolder\Contracts\RepositoryInterface;

class HelpersTest extends BaseTestCase
{
    /**
     * @var Filesystem
     */
    private $finder;

    /**
     * @var string
     */
    private $modulePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->createModule();
        $this->modulePath = $this->getModuleAppPath();
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_it_finds_the_module_path()
    {
        $this->assertTrue(Str::contains(module_path('Blog'), 'modules/Blog'));
    }

    public function test_it_can_bind_a_relative_path_to_module_path()
    {
        $this->assertTrue(Str::contains(module_path('Blog', 'config/config.php'), 'modules/Blog/config/config.php'));
    }

    public function test_module_path_falls_back_to_configured_path_when_module_not_found()
    {
        $base = config('modules.paths.modules');

        $this->assertSame($base.DIRECTORY_SEPARATOR.'Unknown', module_path('Unknown'));
        $this->assertSame(
            $base.DIRECTORY_SEPARATOR.'Unknown'.DIRECTORY_SEPARATOR.'config/config.php',
            module_path('Unknown', 'config/config.php')
        );
    }
}
