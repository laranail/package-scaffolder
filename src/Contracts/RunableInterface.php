<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Contracts;

interface RunableInterface
{
    /**
     * Run the specified command.
     */
    public function run(string $command);
}
