<?php

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Package\Scaffolder\Contracts\RepositoryInterface;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

class FactoryMakeCommandTest extends BaseTestCase
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

    public function test_it_makes_factory(): void
    {
        $code = $this->artisan('module:make-factory', ['name' => 'Post', 'module' => 'Blog']);

        $factoryFile = $this->getModuleBasePath().'/database/factories/PostFactory.php';

        $this->assertTrue(is_file($factoryFile), 'Factory file was not created.');
        $this->assertMatchesSnapshot($this->finder->get($factoryFile));
        $this->assertSame(0, $code);
    }
}
