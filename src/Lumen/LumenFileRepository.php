<?php

namespace Simtabi\Laranail\Package\Scaffolder\Lumen;

use Illuminate\Container\Container;
use Simtabi\Laranail\Package\Scaffolder\Repositories\FileRepository;

class LumenFileRepository extends FileRepository
{
    /**
     * {@inheritdoc}
     */
    protected function createModule(Container $app, string $name, ?string $path = null): Module
    {
        return new Module($app, $name, $path);
    }
}
