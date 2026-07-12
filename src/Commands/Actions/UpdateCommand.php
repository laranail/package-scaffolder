<?php

namespace Simtabi\Laranail\Package\Scaffolder\Commands\Actions;

use Simtabi\Laranail\Package\Scaffolder\Commands\BaseCommand;

class UpdateCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laranail::package-scaffolder.update';

    protected $aliases = ['module:update'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update dependencies for the specified module or for all modules.';

    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $this->components->task("Updating <fg=cyan;options=bold>{$module->getName()}</> Module", function () use ($module): void {
            $this->laravel['modules']->update($module);
        });
    }

    public function getInfo(): ?string
    {
        return 'Updating Module ...';
    }
}
