<?php

namespace Simtabi\Laranail\Package\Scaffolder\Commands\Database;

use Simtabi\Laranail\Package\Scaffolder\Commands\BaseCommand;
use Simtabi\Laranail\Package\Scaffolder\Traits\ModuleMigrationPaths;
use Symfony\Component\Console\Input\InputOption;

class MigrateStatusCommand extends BaseCommand
{
    use ModuleMigrationPaths;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laranail::package-scaffolder.migrate-status';

    protected $aliases = ['module:migrate-status'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Status for all module migrations';

    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $paths = $this->getModuleMigrationPaths($module);

        if ($paths === []) {
            $this->components->warn("No migrations found for module <fg=cyan;options=bold>{$module->getName()}</>");

            return;
        }

        $this->call('migrate:status', array_filter([
            '--path' => $paths,
            '--database' => $this->option('database'),
            '--realpath' => true,
        ]));
    }

    public function getInfo(): ?string
    {
        return null;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['direction', 'd', InputOption::VALUE_OPTIONAL, 'The direction of ordering.', 'asc'],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],
        ];
    }
}
