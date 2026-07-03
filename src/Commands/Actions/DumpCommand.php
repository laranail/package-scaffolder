<?php

namespace Simtabi\Laranail\Package\Scaffolder\Commands\Actions;

use Simtabi\Laranail\Package\Scaffolder\Commands\BaseCommand;

class DumpCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laranail::package-scaffolder.dump';

    protected $aliases = ['module:dump'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dump-autoload the specified module or for all module.';

    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $this->components->task("Generating for <fg=cyan;options=bold>{$module->getName()}</> Module", function () use ($module): void {
            chdir($module->getPath());

            passthru('composer dump -o -n -q');
        });
    }

    public function getInfo(): ?string
    {
        return 'Generating optimized autoload modules';
    }
}
