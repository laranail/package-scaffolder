<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Traits;

trait ModuleCommandTrait
{
    public function getModuleName(): string
    {
        $module = $this->argument('module') ?: app('laranail.package-scaffolder.modules')->getUsedNow();

        $module = app('laranail.package-scaffolder.modules')->findOrFail($module);

        return $module->getStudlyName();
    }
}
