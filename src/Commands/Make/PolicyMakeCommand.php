<?php

namespace Simtabi\Laranail\Package\Scaffolder\Commands\Make;

use Illuminate\Support\Str;
use Override;
use Simtabi\Laranail\Package\Scaffolder\Support\Config\GenerateConfigReader;
use Simtabi\Laranail\Package\Scaffolder\Support\Stub;
use Simtabi\Laranail\Package\Scaffolder\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;

class PolicyMakeCommand extends GeneratorCommand
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
    protected $name = 'laranail::package-scaffolder.make-policy';

    protected $aliases = ['module:make-policy'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new policy class for the specified module.';

    #[Override]
    public function getDefaultNamespace(): string
    {
        return config('modules.paths.generator.policies.namespace')
            ?? $this->strip_app_folder(config('modules.paths.generator.policies.path', 'Policies'));
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
            ['name', InputArgument::REQUIRED, 'The name of the policy class.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    protected function getTemplateContents(): string
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub('/policy.plain.stub', [
            'NAMESPACE' => $this->getClassNamespace($module),
            'CLASS' => $this->getClass(),
        ]))->render();
    }

    protected function getDestinationFilePath(): string
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $policyPath = GenerateConfigReader::read('policies');

        return $path.$policyPath->getPath().'/'.$this->getFileName().'.php';
    }

    /**
     * @return string
     */
    private function getFileName()
    {
        return Str::studly($this->argument('name'));
    }
}
