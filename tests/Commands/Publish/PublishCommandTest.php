<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Commands\Publish;

use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Package\Scaffolder\Contracts\RepositoryInterface;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;

class PublishCommandTest extends BaseTestCase
{
    private Filesystem $finder;

    private string $modulePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createModule();
        $this->modulePath = $this->getModuleBasePath();
        $this->finder = $this->app['files'];
        $this->finder->put($this->modulePath.'/resources/assets/script.js', 'assetfile');
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_it_published_module_assets(): void
    {
        $code = $this->artisan('module:publish', ['module' => 'Blog']);

        $this->assertTrue(is_file(public_path('modules/blog/script.js')));
        $this->assertSame(0, $code);
    }
}
