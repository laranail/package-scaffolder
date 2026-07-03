<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Exceptions;

use Exception;

class InvalidActivatorClass extends Exception
{
    public static function missingConfig(): static
    {
        return new static("You don't have a valid activator configuration class. This might be due to your config being out of date. \n Run php artisan vendor:publish --provider=\"Simtabi\Laranail\Package\Scaffolder\Providers\LaravelModulesServiceProvider\" --force to publish the up to date configuration");
    }
}
