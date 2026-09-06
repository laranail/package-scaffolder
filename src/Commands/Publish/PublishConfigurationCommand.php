<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Commands\Publish;

use Override;
use Symfony\Component\Console\Input\InputOption;
use Simtabi\Laranail\Package\Scaffolder\Facades\Module;
use Simtabi\Laranail\Package\Scaffolder\Commands\BaseCommand;

class PublishConfigurationCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laranail::package-scaffolder.publish-config';

    protected $aliases = ['module:publish-config'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Publish a module's config files to the application";

    public function executeAction($name): void
    {
        $this->call('vendor:publish', [
            '--provider' => $this->getServiceProviderForModule($name),
            '--force'    => $this->option('force'),
            '--tag'      => ['config'],
        ]);
    }

    public function getInfo(): ?string
    {
        return 'Publishing module config files ...';
    }

    #[Override]
    protected function getOptions(): array
    {
        return [
            ['--force', '-f', InputOption::VALUE_NONE, 'Force the publishing of config files'],
        ];
    }

    private function getServiceProviderForModule(string $module): string
    {
        $moduleModel = Module::find($module);

        $namespace = $this->laravel['config']->get('laranail.package-scaffolder.modules.namespace');
        $moduleName = $moduleModel->getName();
        $provider = $this->laravel['config']->get('laranail.package-scaffolder.modules.paths.generator.provider.path');
        $provider = str_replace($this->laravel['config']->get('laranail.package-scaffolder.modules.paths.app_folder'), '', $provider);
        $provider = str_replace('/', '\\', $provider);

        return "$namespace\\$moduleName\\$provider\\{$moduleName}ServiceProvider";
    }
}
