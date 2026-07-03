<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Commands\Database;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Simtabi\Laranail\Package\Scaffolder\Contracts\ActivatorInterface;
use Simtabi\Laranail\Package\Scaffolder\Contracts\RepositoryInterface;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;

class ThrowingSeeder extends Seeder
{
    public function run(): void
    {
        throw new RuntimeException('seeder blew up');
    }
}

class SeedCommandTest extends BaseTestCase
{
    private $finder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->artisan('module:make', ['name' => ['Blog']]);
    }

    protected function tearDown(): void
    {
        // Delete only the module created in setUp(). Never use --all here: the
        // scan-path test registers the on-disk fixtures as modules, and --all
        // would delete those fixture directories from the repository.
        $this->artisan('module:delete', ['module' => ['Blog'], '--force' => true]);
        $this->app[ActivatorInterface::class]->reset();
        parent::tearDown();
    }

    public function test_it_reports_failure_when_a_seeder_throws()
    {
        // Point the module's seeds at a seeder that throws.
        $path = base_path('modules/Blog/module.json');
        $json = json_decode($this->finder->get($path), true);
        $json['migration'] = ['seeds' => [ThrowingSeeder::class]];
        $this->finder->put($path, json_encode($json, JSON_PRETTY_PRINT));

        // Re-resolve the module so the new module.json is picked up.
        $this->app[RepositoryInterface::class]->scan();

        Artisan::call('module:seed', ['module' => ['Blog']]);
        $output = Artisan::output();

        // Before the fix the task closure returned `false`, which the task
        // component rendered as DONE; it must render FAIL now (#2151).
        $this->assertStringContainsString('FAIL', $output);
        $this->assertStringNotContainsString('DONE', $output);
    }

    public function test_it_seeds_a_module_in_a_scan_path_with_a_non_default_namespace()
    {
        // Register the Recipe fixture (namespace Modules\Recipe) as a scanned
        // module and make the default-namespace guess miss, so the seeder can
        // only be resolved via the module's own composer.json psr-4 (#1861).
        config([
            'modules.namespace' => 'SomethingElse',
            'modules.scan.enabled' => true,
            'modules.scan.paths' => [dirname(__DIR__, 2).'/stubs/valid'],
        ]);

        $this->app[RepositoryInterface::class]->scan();

        Artisan::call('module:seed', ['module' => ['Recipe']]);
        $output = Artisan::output();

        $this->assertStringContainsString('Module [Recipe] seeded.', $output);
        $this->assertStringNotContainsString('FAIL', $output);
    }
}
