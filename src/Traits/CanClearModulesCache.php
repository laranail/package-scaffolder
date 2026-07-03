<?php

namespace Simtabi\Laranail\Package\Scaffolder\Traits;

trait CanClearModulesCache
{
    /**
     * Clear the modules cache if it is enabled
     */
    public function clearCache(): void
    {
        $this->laravel['modules']->resetModules();
    }
}
