<?php

namespace Nwidart\Modules\Tests\Commands\Database;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Tests\BaseTestCase;

class ThrowingSeeder extends Seeder
{
    public function run(): void
    {
        throw new \RuntimeException('seeder blew up');
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
        $this->artisan('module:delete', ['--all' => true, '--force' => true]);
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
}
