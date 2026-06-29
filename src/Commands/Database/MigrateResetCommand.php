<?php

namespace Simtabi\Laranail\Package\Scaffolder\Commands\Database;

use Simtabi\Laranail\Package\Scaffolder\Commands\BaseCommand;
use Simtabi\Laranail\Package\Scaffolder\Contracts\ConfirmableCommand;
use Simtabi\Laranail\Package\Scaffolder\Traits\ModuleMigrationPaths;
use Symfony\Component\Console\Input\InputOption;

class MigrateResetCommand extends BaseCommand implements ConfirmableCommand
{
    use ModuleMigrationPaths;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:migrate-reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the modules migrations.';

    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $paths = $this->getModuleMigrationPaths($module);

        if (empty($paths)) {
            $this->components->warn("No migrations found for module <fg=cyan;options=bold>{$module->getName()}</>");

            return;
        }

        $this->call('migrate:reset', array_filter([
            '--path' => $paths,
            '--database' => $this->option('database'),
            '--pretend' => $this->option('pretend'),
            '--force' => $this->option('force'),
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
            ['direction', 'd', InputOption::VALUE_OPTIONAL, 'The direction of ordering.', 'desc'],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
            ['pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'],
        ];
    }
}
