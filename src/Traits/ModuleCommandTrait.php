<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Traits;

trait ModuleCommandTrait
{
    public function getModuleName(): string
    {
        $module = $this->argument('module') ?: app('modules')->getUsedNow();

        $module = app('modules')->findOrFail($module);

        return $module->getStudlyName();
    }
}
