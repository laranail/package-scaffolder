<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Tests\Contracts;

use Simtabi\Laranail\Package\Scaffolder\Contracts\RepositoryInterface;
use Simtabi\Laranail\Package\Scaffolder\Laravel\LaravelFileRepository;
use Simtabi\Laranail\Package\Scaffolder\Tests\BaseTestCase;

class RepositoryInterfaceTest extends BaseTestCase
{
    public function test_it_binds_repository_interface_with_implementation(): void
    {
        $this->assertInstanceOf(LaravelFileRepository::class, app(RepositoryInterface::class));
    }
}
