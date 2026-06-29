<?php

namespace Simtabi\Laranail\Package\Scaffolder\Commands\Actions;

use Simtabi\Laranail\Package\Scaffolder\Commands\BaseCommand;

class DisableCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laranail::package-scaffolder.disable';

    protected $aliases = ['module:disable'];

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'laranail::package-scaffolder.disable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disable an array of modules.';

    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $status = $module->isDisabled()
            ? '<fg=red;options=bold>Disabled</>'
            : '<fg=green;options=bold>Enabled</>';

        $this->components->task("Disabling <fg=cyan;options=bold>{$module->getName()}</> Module, old status: $status", function () use ($module) {
            $module->disable();
        });
    }

    public function getInfo(): ?string
    {
        return 'Disabling module ...';
    }
}
