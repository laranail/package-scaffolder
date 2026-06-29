<?php

namespace Simtabi\Laranail\Package\Scaffolder\Laravel;

use Illuminate\Container\Container;
use Simtabi\Laranail\Package\Scaffolder\FileRepository;

class LaravelFileRepository extends FileRepository
{
    /**
     * {@inheritdoc}
     */
    protected function createModule(Container $app, string $name, string $path): Module
    {
        return new Module($app, $name, $path);
    }
}
