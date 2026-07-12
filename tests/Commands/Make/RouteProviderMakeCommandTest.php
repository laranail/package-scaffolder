<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Package\Scaffolder\Contracts\RepositoryInterface;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class RouteProviderMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

    private Filesystem $finder;

    private string $modulePath;

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

    public function test_it_generates_a_new_service_provider_class(): void
    {
        $path = $this->modulePath.'/Providers/RouteServiceProvider.php';
        $this->finder->delete($path);
        $code = $this->artisan('module:route-provider', ['module' => 'Blog']);

        $this->assertTrue(is_file($path));
        $this->assertSame(0, $code);
    }

    public function test_it_generated_correct_file_with_content(): void
    {
        $path = $this->modulePath.'/Providers/RouteServiceProvider.php';
        $this->finder->delete($path);
        $code = $this->artisan('module:route-provider', ['module' => 'Blog']);

        $file = $this->finder->get($path);

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace(): void
    {
        $this->app['config']->set('modules.paths.generator.provider.path', 'SuperProviders');

        $code = $this->artisan('module:route-provider', ['module' => 'Blog']);

        $file = $this->finder->get($this->getModuleBasePath().'/SuperProviders/RouteServiceProvider.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_default_namespace_specific(): void
    {
        $this->app['config']->set('modules.paths.generator.provider.namespace', 'SuperProviders');

        $path = $this->modulePath.'/Providers/RouteServiceProvider.php';
        $this->finder->delete($path);
        $code = $this->artisan('module:route-provider', ['module' => 'Blog']);

        $file = $this->finder->get($path);

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_overwrite_route_file_names(): void
    {
        $this->app['config']->set('modules.stubs.files.routes/web', 'SuperRoutes/web.php');
        $this->app['config']->set('modules.stubs.files.routes/api', 'SuperRoutes/api.php');

        $code = $this->artisan('module:route-provider', ['module' => 'Blog', '--force' => true]);

        $file = $this->finder->get($this->modulePath.'/Providers/RouteServiceProvider.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_overwrite_file(): void
    {
        $this->artisan('module:route-provider', ['module' => 'Blog']);
        $this->app['config']->set('modules.stubs.files.routes/web', 'SuperRoutes/web.php');

        $code = $this->artisan('module:route-provider', ['module' => 'Blog', '--force' => true]);
        $file = $this->finder->get($this->modulePath.'/Providers/RouteServiceProvider.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_can_change_the_custom_controller_namespace(): void
    {
        $this->app['config']->set('modules.paths.generator.controller.path', 'Base/Http/Controllers');
        $this->app['config']->set('modules.paths.generator.provider.path', 'Base/Providers');

        $code = $this->artisan('module:route-provider', ['module' => 'Blog']);
        $file = $this->finder->get($this->getModuleBasePath().'/Base/Providers/RouteServiceProvider.php');

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_omits_web_routes_when_disabled(): void
    {
        $this->app['config']->set('modules.paths.generator.routes.web', false);

        $path = $this->modulePath.'/Providers/RouteServiceProvider.php';
        $this->finder->delete($path);
        $code = $this->artisan('module:route-provider', ['module' => 'Blog', '--force' => true]);

        $file = $this->finder->get($path);

        $this->assertStringNotContainsString('mapWebRoutes', $file);
        $this->assertStringContainsString('mapApiRoutes', $file);
        $this->assertStringNotContainsString('%START_', $file);
        $this->assertStringNotContainsString('%END_', $file);
        $this->assertSame(0, $code);
    }

    public function test_it_omits_api_routes_when_disabled(): void
    {
        $this->app['config']->set('modules.paths.generator.routes.api', false);

        $path = $this->modulePath.'/Providers/RouteServiceProvider.php';
        $this->finder->delete($path);
        $code = $this->artisan('module:route-provider', ['module' => 'Blog', '--force' => true]);

        $file = $this->finder->get($path);

        $this->assertStringNotContainsString('mapApiRoutes', $file);
        $this->assertStringContainsString('mapWebRoutes', $file);
        $this->assertStringNotContainsString('%START_', $file);
        $this->assertStringNotContainsString('%END_', $file);
        $this->assertSame(0, $code);
    }
}
