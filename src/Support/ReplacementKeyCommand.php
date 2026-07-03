<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Support;

use Simtabi\Laranail\Package\Scaffolder\Generators\ModuleGenerator;

abstract class ReplacementKeyCommand
{
    public function __construct(protected ModuleGenerator $generator) {}

    abstract public function handle(): string;
}
