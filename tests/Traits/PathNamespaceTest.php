<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Traits;

use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;

class PathNamespaceTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->class = new UsePathNamespaceTrait;
    }

    public function test_studly_path()
    {
        $this->assertSame('Blog/Services', $this->class->studly_path('/blog/services'));
    }

    public function test_studly_namespace()
    {
        $this->assertSame('/blog/services', $this->class->studly_namespace('/blog/services'));
    }

    public function test_path_namespace()
    {
        $this->assertSame('Blog\Services', $this->class->path_namespace('/blog/services'));
    }

    public function test_module_namespace()
    {
        $this->assertSame('Modules\Blog/services', $this->class->module_namespace('blog/services'));
    }

    public function test_clean_path()
    {
        $this->assertSame('blog/services', $this->class->clean_path('blog/services'));
        $this->assertSame('', $this->class->clean_path(''));
    }

    public function test_app_path()
    {
        $configPath = config('modules.paths.app_folder');
        $configPath = rtrim($configPath, '/');

        $this->assertSame($configPath, $this->class->app_path());
        $this->assertSame($configPath, $this->class->app_path(null));
        $this->assertSame('app/blog/services', $this->class->app_path('blog/services'));
    }

    public function test_app_path_treats_app_variants_consistently()
    {
        // default app_folder = 'app/'. 'app', 'app/' and 'App' must all collapse
        // to the app root rather than duplicating the folder (#2152).
        $this->assertSame('app', $this->class->app_path('app'));
        $this->assertSame('app', $this->class->app_path('app/'));
        $this->assertSame('app', $this->class->app_path('App'));
        $this->assertSame('app/Http/Controllers', $this->class->app_path('app/Http/Controllers'));
        $this->assertSame('app/Http/Controllers', $this->class->app_path('Http/Controllers'));
    }

    public function test_app_path_with_custom_app_folder()
    {
        config(['modules.paths.app_folder' => 'src/']);

        // the bug: 'app' (no slash) used to yield 'src/app' instead of 'src'.
        $this->assertSame('src', $this->class->app_path('app'));
        $this->assertSame('src', $this->class->app_path('app/'));
        $this->assertSame('src', $this->class->app_path('src'));
        $this->assertSame('src/Models', $this->class->app_path('src/Models'));
        $this->assertSame('src/Models', $this->class->app_path('Models'));
    }

    public function test_strip_app_folder_removes_the_prefix_as_a_prefix_not_a_char_mask()
    {
        // default app_folder = 'app/'
        $this->assertSame('Providers', $this->class->strip_app_folder('app/Providers'));
        $this->assertSame('Http/Controllers', $this->class->strip_app_folder('app/Http/Controllers'));

        // a lowercase next segment must survive: ltrim() would have eaten the
        // leading "a"/"p" characters and produced "i" here (#2152/#2164).
        $this->assertSame('api', $this->class->strip_app_folder('app/api'));

        // paths that don't start with the app folder are left untouched.
        $this->assertSame('Providers', $this->class->strip_app_folder('Providers'));
        $this->assertSame('SuperProviders', $this->class->strip_app_folder('SuperProviders'));

        // null/empty tolerated (config key may be absent).
        $this->assertSame('', $this->class->strip_app_folder(null));
    }

    public function test_strip_app_folder_respects_a_custom_app_folder()
    {
        config(['modules.paths.app_folder' => 'src/']);

        $this->assertSame('Providers', $this->class->strip_app_folder('src/Providers'));

        // 'app/...' is no longer the configured app folder, so keep it intact.
        $this->assertSame('app/Providers', $this->class->strip_app_folder('app/Providers'));

        // an empty app_folder leaves everything intact.
        config(['modules.paths.app_folder' => '']);
        $this->assertSame('app/Providers', $this->class->strip_app_folder('app/Providers'));
    }
}
