<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Commands;

use Illuminate\Console\Command;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;
use Simtabi\Laranail\Package\Scaffolder\Contracts\RepositoryInterface;
use Simtabi\Laranail\Package\Scaffolder\Support\Module;

class LaravelModulesV6Migrator extends Command
{
    use SupportsNamespacedNames;

    protected $name = 'laranail::package-scaffolder.v6:migrate';

    protected $aliases = ['module:v6:migrate'];

    protected $description = 'Migrate legacy v5 module statuses to the v6 format.';

    public function handle(): int
    {
        $moduleStatuses = [];
        /** @var RepositoryInterface $modules */
        $modules = $this->laravel['modules'];

        $modules = $modules->all();
        /** @var Module $module */
        foreach ($modules as $module) {
            if ($module->json()->get('active') === 1) {
                $module->enable();
                $moduleStatuses[] = [$module->getName(), 'Enabled'];
            }
            if ($module->json()->get('active') === 0) {
                $module->disable();
                $moduleStatuses[] = [$module->getName(), 'Disabled'];
            }
        }
        $this->info('All modules have been migrated.');
        $this->table(['Module name', 'Status'], $moduleStatuses);

        return 0;
    }
}
