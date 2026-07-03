<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Commands\Database;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Simtabi\Laranail\Package\Scaffolder\Contracts\ActivatorInterface;
use Simtabi\Laranail\Package\Scaffolder\Contracts\RepositoryInterface;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;

class MigrateCommandTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('module:make', ['name' => ['Blog']]);
        $this->artisan('module:make-migration', ['name' => 'create_blog_posts_table', 'module' => 'Blog']);
    }

    protected function tearDown(): void
    {
        $this->artisan('module:delete', ['module' => ['Blog'], '--force' => true]);
        $this->app[ActivatorInterface::class]->reset();
        parent::tearDown();
    }

    public function test_it_migrates_shows_status_and_rolls_back_a_module(): void
    {
        Artisan::call('module:migrate', ['module' => ['Blog'], '--force' => true]);
        $this->assertTrue(Schema::hasTable('blog_posts'));

        Artisan::call('module:migrate-status', ['module' => ['Blog']]);
        $this->assertStringContainsString('blog_posts', Artisan::output());

        Artisan::call('module:migrate-rollback', ['module' => ['Blog'], '--force' => true]);
        $this->assertFalse(Schema::hasTable('blog_posts'));
    }

    public function test_it_resets_a_module_migrations(): void
    {
        Artisan::call('module:migrate', ['module' => ['Blog'], '--force' => true]);
        $this->assertTrue(Schema::hasTable('blog_posts'));

        Artisan::call('module:migrate-reset', ['module' => ['Blog'], '--force' => true]);
        $this->assertFalse(Schema::hasTable('blog_posts'));
    }

    public function test_it_refreshes_a_module_migrations(): void
    {
        Artisan::call('module:migrate', ['module' => ['Blog'], '--force' => true]);
        $this->assertTrue(Schema::hasTable('blog_posts'));

        Artisan::call('module:migrate-refresh', ['module' => ['Blog'], '--force' => true]);
        $this->assertTrue(Schema::hasTable('blog_posts'));
    }

    public function test_migrate_and_rollback_are_symmetric_for_a_disabled_module(): void
    {
        // The asymmetry #2159 reports: rollback worked on a disabled module but
        // migrate silently no-oped because the provider was never booted.
        $this->app[ActivatorInterface::class]->disable($this->app[RepositoryInterface::class]->find('Blog'));

        Artisan::call('module:migrate', ['module' => ['Blog'], '--force' => true]);
        $this->assertTrue(Schema::hasTable('blog_posts'), 'a disabled module should still migrate when targeted explicitly');

        Artisan::call('module:migrate-rollback', ['module' => ['Blog'], '--force' => true]);
        $this->assertFalse(Schema::hasTable('blog_posts'));
    }

    public function test_it_warns_when_a_module_has_no_migrations(): void
    {
        Artisan::call('module:migrate-rollback', ['module' => ['Blog'], '--subpath' => 'does_not_exist.php', '--force' => true]);

        $this->assertStringContainsString('No migrations found', Artisan::output());
    }
}
