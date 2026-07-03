<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Exceptions;

use Exception;

class InvalidAssetPath extends Exception
{
    public static function missingModuleName($asset): static
    {
        return new static("Module name was not specified in asset [$asset].");
    }
}
