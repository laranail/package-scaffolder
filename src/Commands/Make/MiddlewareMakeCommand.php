<?php

namespace Simtabi\Laranail\Package\Scaffolder\Commands\Make;

use Illuminate\Support\Str;
use Override;
use Simtabi\Laranail\Package\Scaffolder\Support\Config\GenerateConfigReader;
use Simtabi\Laranail\Package\Scaffolder\Support\Stub;
use Simtabi\Laranail\Package\Scaffolder\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MiddlewareMakeCommand extends GeneratorCommand
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
    protected $name = 'laranail::package-scaffolder.make-middleware';

    protected $aliases = ['module:make-middleware'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new middleware class for the specified module.';

    #[Override]
    public function getDefaultNamespace(): string
    {
        return config('modules.paths.generator.filter.namespace')
            ?? $this->strip_app_folder(config('modules.paths.generator.filter.path', 'Http/Middleware'));
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
            ['name', InputArgument::REQUIRED, 'The name of the command.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    #[Override]
    protected function getOptions()
    {
        return [
            ['inertia', null, InputOption::VALUE_NONE, 'Generate an Inertia HandleInertiaRequests middleware.'],
        ];
    }

    /**
     * @return mixed
     */
    protected function getTemplateContents()
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        $stub = $this->option('inertia') ? '/middleware/handle-inertia-requests.stub' : '/middleware.stub';

        return (new Stub($stub, [
            'NAMESPACE' => $this->getClassNamespace($module),
            'CLASS' => $this->getClass(),
        ]))->render();
    }

    /**
     * @return mixed
     */
    protected function getDestinationFilePath()
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $middlewarePath = GenerateConfigReader::read('filter');

        return $path.$middlewarePath->getPath().'/'.$this->getFileName().'.php';
    }

    /**
     * @return string
     */
    private function getFileName()
    {
        return Str::studly($this->argument('name'));
    }

    /**
     * Run the command.
     */
    #[Override]
    public function handle(): int
    {

        $this->components->info('Creating middleware...');

        parent::handle();

        return 0;
    }
}
