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

class ScopeMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    protected $argumentName = 'name';

    protected $name = 'laranail::package-scaffolder.make-scope';

    protected $aliases = ['module:make-scope'];

    protected $description = 'Create a new scope class for the specified module.';

    public function getDestinationFilePath(): string
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $filePath = GenerateConfigReader::read('scopes')->getPath() ?? config('laranail.package-scaffolder.modules.paths.generator.model.path') . '/Scopes';

        return $path . $filePath . '/' . $this->getScopeName() . '.php';
    }

    #[Override]
    public function getDefaultNamespace(): string
    {
        $namespace = config('laranail.package-scaffolder.modules.paths.generator.model.path');

        $parts = explode('/', $namespace);
        $models = end($parts);

        return $models . '\Scopes';
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
            ['name', InputArgument::REQUIRED, 'The name of the scope class.'],
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

    protected function getScopeName(): array|string
    {
        return Str::studly($this->argument('name'));
    }

    protected function getStubName(): string
    {
        return '/scope.stub';
    }

    private function getClassNameWithoutNamespace(): string
    {
        return class_basename($this->getScopeName());
    }
}
