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

class RepositoryMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    protected $argumentName = 'name';

    protected $name = 'laranail::package-scaffolder.make-repository';

    protected $aliases = ['module:make-repository'];

    protected $description = 'Create a new repository class for the specified module.';

    public function getDestinationFilePath(): string
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $filePath = GenerateConfigReader::read('repository')->getPath() ?? config('laranail.package-scaffolder.modules.paths.app_folder') . 'Repositories';

        return $path . $filePath . '/' . $this->getRepositoryName() . '.php';
    }

    #[Override]
    public function getDefaultNamespace(): string
    {
        return config('laranail.package-scaffolder.modules.paths.generator.repository.namespace', 'Repositories');
    }

    protected function getTemplateContents(): string
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub($this->getStubName(), [
            'CLASS_NAMESPACE' => $this->getClassNamespace($module),
            'CLASS'           => $this->getClassNameWithoutNamespace(),
        ]))->render();
    }

    #[Override]
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the repository class.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    #[Override]
    protected function getOptions(): array
    {
        return [
            ['invokable', 'i', InputOption::VALUE_NONE, 'Generate an invokable action class', null],
            ['force', 'f', InputOption::VALUE_NONE, 'su.'],
        ];
    }

    protected function getRepositoryName(): array|string
    {
        return Str::studly($this->argument('name'));
    }

    protected function getStubName(): string
    {
        return $this->option('invokable') === true ? '/repository-invoke.stub' : '/repository.stub';
    }

    private function getClassNameWithoutNamespace(): string
    {
        return class_basename($this->getRepositoryName());
    }
}
