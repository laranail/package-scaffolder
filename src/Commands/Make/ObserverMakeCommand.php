<?php

namespace Simtabi\Laranail\Package\Scaffolder\Commands\Make;

use Illuminate\Support\Str;
use Override;
use Simtabi\Laranail\Package\Scaffolder\Support\Config\GenerateConfigReader;
use Simtabi\Laranail\Package\Scaffolder\Support\Stub;
use Simtabi\Laranail\Package\Scaffolder\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;

class ObserverMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laranail::package-scaffolder.make-observer';

    protected $aliases = ['module:make-observer'];

    /**
     * The name of argument name.
     *
     * @var string
     */
    protected $argumentName = 'name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new observer for the specified module.';

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    #[Override]
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The observer name will be created.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be created.'],
        ];
    }

    protected function getTemplateContents(): string
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub('/observer.stub', [
            'NAMESPACE' => $this->getClassNamespace($module),
            'NAME' => $this->getModelName(),
            'MODEL_NAMESPACE' => $this->getModelNamespace(),
            'NAME_VARIABLE' => $this->getModelVariable(),
        ]))->render();
    }

    /**
     * Get model namespace.
     */
    public function getModelNamespace(): string
    {
        $moduleNamespace = $this->laravel['modules']->config('namespace'); // 'Modules'
        $moduleName = $this->laravel['modules']->findOrFail($this->getModuleName());

        $path = $this->laravel['modules']->config('paths.generator.model.path', 'Entities'); // current is 'app/Models'
        $appFolder = $this->laravel['modules']->config('paths.app_folder', 'app/');

        if (str_starts_with($path, $appFolder)) {
            $path = substr($path, strlen($appFolder)); // has 'Models'
        }

        $nsPart = trim(str_replace('/', '\\', $path), '\\'); // 'Models'

        return $moduleNamespace.'\\'.$moduleName.'\\'.$nsPart; // Modules\Core\Models
    }

    /**
     * @return mixed|string
     */
    private function getModelName()
    {
        return Str::studly($this->argument('name'));
    }

    /**
     * @return mixed|string
     */
    private function getModelVariable(): string
    {
        return '$'.Str::lower($this->argument('name'));
    }

    protected function getDestinationFilePath(): string
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $observerPath = GenerateConfigReader::read('observer');

        return $path.$observerPath->getPath().'/'.$this->getFileName();
    }

    private function getFileName(): string
    {
        return Str::studly($this->argument('name')).'Observer.php';
    }

    #[Override]
    public function handle(): int
    {
        $this->components->info('Creating observer...');

        parent::handle();

        return 0;
    }

    /**
     * Get default namespace.
     */
    #[Override]
    public function getDefaultNamespace(): string
    {

        $path = config('modules.paths.generator.observer.path', 'Observers'); // 'app/Observers'
        $appFolder = config('modules.paths.app_folder', 'app/');

        if (str_starts_with($path, $appFolder)) {
            $path = substr($path, strlen($appFolder)); // 'Observers'
        }

        return trim(str_replace('/', '\\', $path), '\\'); // 'Observers'
    }
}
