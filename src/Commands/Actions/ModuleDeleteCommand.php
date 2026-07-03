<?php

namespace Simtabi\Laranail\Package\Scaffolder\Commands\Actions;

use Closure;
use Override;
use Simtabi\Laranail\Package\Scaffolder\Commands\BaseCommand;
use Simtabi\Laranail\Package\Scaffolder\Contracts\ConfirmableCommand;

class ModuleDeleteCommand extends BaseCommand implements ConfirmableCommand
{
    protected $name = 'laranail::package-scaffolder.delete';

    protected $aliases = ['module:delete'];

    protected $description = 'Delete a module from the application';

    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);
        $this->components->task("Deleting <fg=cyan;options=bold>{$module->getName()}</> Module", function () use ($module) {
            $module->delete();
        });
    }

    public function getInfo(): ?string
    {
        return 'deleting module ...';
    }

    #[Override]
    public function getConfirmableLabel(): string
    {
        return 'Warning: Do you want to remove the module?';
    }

    public function getConfirmableCallback(): Closure|bool|null
    {
        return true;
    }
}
