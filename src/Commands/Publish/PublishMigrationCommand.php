<?php

namespace Simtabi\Laranail\Package\Scaffolder\Commands\Publish;

use Simtabi\Laranail\Package\Scaffolder\Commands\BaseCommand;
use Simtabi\Laranail\Package\Scaffolder\Migrations\Migrator;
use Simtabi\Laranail\Package\Scaffolder\Publishing\MigrationPublisher;

class PublishMigrationCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laranail::package-scaffolder.publish-migration';

    protected $aliases = ['module:publish-migration'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Publish a module's migrations to the application";

    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $this->components->task("Publishing Migration <fg=cyan;options=bold>{$module->getName()}</> Module", function () use ($module): void {
            with(new MigrationPublisher(new Migrator($module, $this->getLaravel())))
                ->setRepository($this->laravel['modules'])
                ->setConsole($this)
                ->publish();
        });
    }

    public function getInfo(): ?string
    {
        return 'Publishing module migrations ...';
    }
}
