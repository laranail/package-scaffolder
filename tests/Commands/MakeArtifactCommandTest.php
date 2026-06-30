<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;

class MakeArtifactCommandTest extends BaseTestCase
{
    private Filesystem $fs;

    private array $targets = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->fs = new Filesystem;
    }

    protected function tearDown(): void
    {
        foreach ($this->targets as $t) {
            $this->fs->deleteDirectory($t);
        }
        parent::tearDown();
    }

    private function tmp(): string
    {
        $dir = sys_get_temp_dir().'/laranail-cmd-'.uniqid();
        $this->targets[] = $dir;

        return $dir;
    }

    public function test_non_interactive_generates_a_package_with_flags()
    {
        $dir = $this->tmp();

        $code = Artisan::call('make:artifact', [
            'name' => 'Demo',
            '--type' => 'package',
            '--namespace' => 'Acme',
            '--features' => 'web-ui,rest-api',
            '--path' => $dir,
            '--no-interaction' => true,
        ]);

        $this->assertSame(0, $code, Artisan::output());
        $provider = $dir.'/Demo/src/Providers/DemoServiceProvider.php';
        $this->assertFileExists($provider);
        $this->assertStringContainsString('namespace Acme\\Demo\\Providers;', $this->fs->get($provider));
        // a feature left out of --features must not be generated
        $this->assertFileDoesNotExist($dir.'/Demo/src/Repositories/CachingPostRepository.php');
    }

    public function test_missing_required_type_fails_loudly()
    {
        $code = Artisan::call('make:artifact', [
            'name' => 'Demo',
            '--path' => $this->tmp(),
            '--no-interaction' => true,
        ]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('--type is required', Artisan::output());
    }

    public function test_unknown_feature_errors()
    {
        $code = Artisan::call('make:artifact', [
            'name' => 'Demo',
            '--type' => 'package',
            '--features' => 'web-ui,teleporter',
            '--path' => $this->tmp(),
            '--no-interaction' => true,
        ]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Unknown feature', Artisan::output());
    }

    public function test_plugin_required_when_type_is_plugin()
    {
        $code = Artisan::call('make:artifact', [
            'name' => 'Demo',
            '--type' => 'plugin',
            '--path' => $this->tmp(),
            '--no-interaction' => true,
        ]);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('--plugin is required', Artisan::output());
    }
}
