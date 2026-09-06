<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Commands\Publish;

use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;
use Simtabi\Laranail\Package\Scaffolder\Contracts\RepositoryInterface;

class PublishTranslationCommandTest extends BaseTestCase
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

    public function test_it_published_module_translations(): void
    {
        $code = $this->artisan('module:publish-translation', ['module' => 'Blog']);

        $this->assertDirectoryExists(base_path('resources/lang/blog'));
        $this->assertSame(0, $code);
    }
}
