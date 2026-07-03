<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests;

use Illuminate\Support\Str;
use Simtabi\Laranail\Package\Scaffolder\Contracts\RepositoryInterface;

class HelpersTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createModule();
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

    public function test_role_generic_artifact_path_helpers()
    {
        // resolve from the artifact containers (config artifacts.kinds), role-generic
        $this->assertStringEndsWith(
            'platform/packages/Billing',
            str_replace(DIRECTORY_SEPARATOR, '/', package_path('Billing')),
        );
        $this->assertStringEndsWith(
            'platform/plugins/Shop/src',
            str_replace(DIRECTORY_SEPARATOR, '/', plugin_path('Shop', 'src')),
        );
        $this->assertStringEndsWith(
            'platform/modules/Blog',
            str_replace(DIRECTORY_SEPARATOR, '/', artifact_path('module', 'Blog')),
        );
    }
}
