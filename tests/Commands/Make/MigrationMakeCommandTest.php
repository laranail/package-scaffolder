<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Package\Scaffolder\Contracts\RepositoryInterface;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class MigrationMakeCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

    private Filesystem $finder;

    private string $modulePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->createModule();
        $this->modulePath = $this->getModuleBasePath();
    }

    protected function tearDown(): void
    {
        $this->app[RepositoryInterface::class]->delete('Blog');
        parent::tearDown();
    }

    public function test_it_generates_a_new_migration_class(): void
    {
        $code = $this->artisan('module:make-migration', ['name' => 'create_posts_table', 'module' => 'Blog']);

        $files = $this->finder->allFiles($this->modulePath.'/database/migrations');

        $this->assertCount(1, $files);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_correct_create_migration_file_content(): void
    {
        $code = $this->artisan('module:make-migration', ['name' => 'create_posts_table', 'module' => 'Blog']);

        $migrations = $this->finder->allFiles($this->modulePath.'/database/migrations');
        $fileName = $migrations[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath.'/database/migrations/'.$fileName);

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_correct_add_migration_file_content(): void
    {
        $code = $this->artisan('module:make-migration', ['name' => 'add_something_to_posts_table', 'module' => 'Blog']);

        $migrations = $this->finder->allFiles($this->modulePath.'/database/migrations');
        $fileName = $migrations[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath.'/database/migrations/'.$fileName);

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_correct_delete_migration_file_content(): void
    {
        $code = $this->artisan('module:make-migration', ['name' => 'delete_something_from_posts_table', 'module' => 'Blog']);

        $migrations = $this->finder->allFiles($this->modulePath.'/database/migrations');
        $fileName = $migrations[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath.'/database/migrations/'.$fileName);

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_correct_drop_migration_file_content(): void
    {
        $code = $this->artisan('module:make-migration', ['name' => 'drop_posts_table', 'module' => 'Blog']);

        $migrations = $this->finder->allFiles($this->modulePath.'/database/migrations');
        $fileName = $migrations[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath.'/database/migrations/'.$fileName);

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_correct_default_migration_file_content(): void
    {
        $code = $this->artisan('module:make-migration', ['name' => 'something_random_name', 'module' => 'Blog']);

        $migrations = $this->finder->allFiles($this->modulePath.'/database/migrations');
        $fileName = $migrations[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath.'/database/migrations/'.$fileName);

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }

    public function test_it_generates_foreign_key_constraints(): void
    {
        $code = $this->artisan('module:make-migration', ['name' => 'create_posts_table', 'module' => 'Blog', '--fields' => 'belongsTo:user:id:users']);

        $migrations = $this->finder->allFiles($this->modulePath.'/database/migrations');
        $fileName = $migrations[0]->getRelativePathname();
        $file = $this->finder->get($this->modulePath.'/database/migrations/'.$fileName);

        $this->assertMatchesSnapshot($file);
        $this->assertSame(0, $code);
    }
}
