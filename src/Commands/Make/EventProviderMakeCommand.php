<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Commands\Make;

use Override;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Simtabi\Laranail\Package\Scaffolder\Support\Stub;
use Simtabi\Laranail\Package\Scaffolder\Traits\ModuleCommandTrait;
use Simtabi\Laranail\Package\Scaffolder\Support\Config\GenerateConfigReader;

class EventProviderMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    protected $argumentName = 'module';

    protected $name = 'laranail::package-scaffolder.make-event-provider';

    protected $aliases = ['module:make-event-provider'];

    protected $description = 'Create a new event service provider class for the specified module.';

    public function getDestinationFilePath(): string
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $filePath = GenerateConfigReader::read('provider')->getPath();

        return $path . $filePath . '/' . $this->getEventServiceProviderName() . '.php';
    }

    #[Override]
    public function getDefaultNamespace(): string
    {
        return config('laranail.package-scaffolder.modules.paths.generator.provider.namespace')
            ?? $this->strip_app_folder(config('laranail.package-scaffolder.modules.paths.generator.provider.path', 'Providers'));
    }

    protected function getTemplateContents(): string
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub($this->getStubName(), [
            'NAMESPACE' => $this->getClassNamespace($module),
            'CLASS'     => $this->getClassNameWithoutNamespace(),
        ]))->render();
    }

    #[Override]
    protected function getArguments(): array
    {
        return [
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    #[Override]
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'su.'],
        ];
    }

    protected function getEventServiceProviderName(): array|string
    {
        return Str::studly('EventServiceProvider');
    }

    protected function getStubName(): string
    {
        return '/event-provider.stub';
    }

    private function getClassNameWithoutNamespace(): string
    {
        return class_basename($this->getEventServiceProviderName());
    }
}
