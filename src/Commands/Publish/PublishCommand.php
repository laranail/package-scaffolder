<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Commands\Publish;

use Simtabi\Laranail\Package\Scaffolder\Commands\BaseCommand;
use Simtabi\Laranail\Package\Scaffolder\Publishing\AssetPublisher;

class PublishCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laranail::package-scaffolder.publish';

    protected array $deprecatedCommandAliases = ['module:publish'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish a module\'s assets to the application';

    public function executeAction($name): void
    {
        $module = $this->getModuleModel($name);

        $this->components->task("Publishing Assets <fg=cyan;options=bold>{$module->getName()}</> Module", function () use ($module): void {
            with(new AssetPublisher($module))
                ->setRepository($this->laravel['laranail.package-scaffolder.modules'])
                ->setConsole($this)
                ->publish();
        });

    }

    public function getInfo(): ?string
    {
        return 'Publishing module asset files ...';
    }
}
