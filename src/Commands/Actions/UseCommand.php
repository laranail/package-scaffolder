<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Commands\Actions;

use Simtabi\Laranail\Package\Scaffolder\Commands\BaseCommand;

class UseCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laranail::package-scaffolder.use';

    protected array $deprecatedCommandAliases = ['module:use'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Use the specified module.';

    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $this->components->task("Using <fg=cyan;options=bold>{$module->getName()}</> Module", function () use ($module): void {
            $this->laravel['laranail.package-scaffolder.modules']->setUsed($module);
        });
    }

    public function getInfo(): ?string
    {
        return 'Using Module ...';
    }
}
