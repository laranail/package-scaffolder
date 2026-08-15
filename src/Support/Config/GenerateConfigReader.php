<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Support\Config;

class GenerateConfigReader
{
    public static function read(string $value): GeneratorPath
    {
        return new GeneratorPath(config("laranail.package-scaffolder.modules.paths.generator.$value"));
    }
}
