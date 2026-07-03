<?php

namespace Simtabi\Laranail\Package\Scaffolder\Commands\Actions;

use Illuminate\Console\Command;
use Override;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;
use Simtabi\Laranail\Package\Scaffolder\Support\Module;
use Symfony\Component\Console\Input\InputOption;

class ListCommand extends Command
{
    use SupportsNamespacedNames;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laranail::package-scaffolder.list';

    protected $aliases = ['module:list'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show list of all modules.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->twoColumnDetail('<fg=gray>Status / Name</>', '<fg=gray>Path / priority</>');
        collect($this->getRows())->each(function ($row) {

            $this->components->twoColumnDetail("[{$row[1]}] {$row[0]}", "{$row[3]} [{$row[2]}]");
        });

        return 0;
    }

    /**
     * Get table rows.
     *
     * @return array
     */
    public function getRows()
    {
        $rows = [];

        /** @var Module $module */
        foreach ($this->getModules() as $module) {
            $rows[] = [
                $module->getName(),
                $module->isEnabled() ? '<fg=green>Enabled</>' : '<fg=red>Disabled</>',
                $module->get('priority'),
                $module->getPath(),
            ];
        }

        return $rows;
    }

    public function getModules()
    {
        return match ($this->option('only')) {
            'enabled' => $this->laravel['modules']->getByStatus(1),
            'disabled' => $this->laravel['modules']->getByStatus(0),
            'priority' => $this->laravel['modules']->getPriority($this->option('direction')),
            default => $this->laravel['modules']->all(),
        };
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    #[Override]
    protected function getOptions()
    {
        return [
            ['only', 'o', InputOption::VALUE_OPTIONAL, 'Types of modules will be displayed.', null],
            ['direction', 'd', InputOption::VALUE_OPTIONAL, 'The direction of ordering.', 'asc'],
        ];
    }
}
