<?php

namespace Simtabi\Laranail\Package\Scaffolder\Commands;

use Simtabi\Laranail\Package\Scaffolder\Contracts\RepositoryInterface;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;

class ListCommandTest extends BaseTestCase
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

    public function test_it_can_list_modules(): void
    {
        $code = $this->artisan('module:list');

        // We just want to make sure nothing throws an exception inside the list command
        $this->assertTrue(true);
        $this->assertSame(0, $code);
    }
}
