<?php

namespace Simtabi\Laranail\Package\Scaffolder\Commands\Make;

use Illuminate\Support\Str;
use Override;
use Simtabi\Laranail\Package\Scaffolder\Support\Config\GenerateConfigReader;
use Simtabi\Laranail\Package\Scaffolder\Support\Stub;
use Simtabi\Laranail\Package\Scaffolder\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;

class ComponentClassMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name of argument name.
     *
     * @var string
     */
    protected $argumentName = 'name';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laranail::package-scaffolder.make-component';

    protected $aliases = ['module:make-component'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new component-class for the specified module.';

    #[Override]
    public function handle(): int
    {
        if (parent::handle() === E_ERROR) {
            return E_ERROR;
        }
        $this->writeComponentViewTemplate();

        return 0;
    }

    /**
     * Write the view template for the component.
     *
     * @return void
     */
    protected function writeComponentViewTemplate()
    {
        $this->call('module:make-component-view', ['name' => $this->argument('name'), 'module' => $this->argument('module')]);
    }

    #[Override]
    public function getDefaultNamespace(): string
    {
        return config('modules.paths.generator.component-class.namespace')
            ?? $this->strip_app_folder(config('modules.paths.generator.component-class.path', 'View/Component'));
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    #[Override]
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the component.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    protected function getTemplateContents(): string
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub('/component-class.stub', [
            'NAMESPACE' => $this->getClassNamespace($module),
            'CLASS' => $this->getClass(),
            'LOWER_NAME' => $module->getLowerName(),
            'COMPONENT_NAME' => 'components.'.Str::lower($this->argument('name')),
        ]))->render();
    }

    protected function getDestinationFilePath(): string
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());
        $factoryPath = GenerateConfigReader::read('component-class');

        return $path.$factoryPath->getPath().'/'.$this->getFileName();
    }

    private function getFileName(): string
    {
        return Str::studly($this->argument('name')).'.php';
    }
}
