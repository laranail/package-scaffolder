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

class JobMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laranail::package-scaffolder.make-job';

    protected $aliases = ['module:make-job'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new job class for the specified module';

    protected $argumentName = 'name';

    #[Override]
    public function getDefaultNamespace(): string
    {
        return config('laranail.package-scaffolder.modules.paths.generator.jobs.namespace')
            ?? $this->strip_app_folder(config('laranail.package-scaffolder.modules.paths.generator.jobs.path', 'Jobs'));
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
            ['name', InputArgument::REQUIRED, 'The name of the job.'],
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
            ['sync', null, InputOption::VALUE_NONE, 'Indicates that job should be synchronous.'],
        ];
    }

    /**
     * Get template contents.
     */
    protected function getTemplateContents(): string
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub($this->getStubName(), [
            'NAMESPACE' => $this->getClassNamespace($module),
            'CLASS'     => $this->getClass(),
        ]))->render();
    }

    /**
     * Get the destination file path.
     */
    protected function getDestinationFilePath(): string
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $jobPath = GenerateConfigReader::read('jobs');

        return $path . $jobPath->getPath() . '/' . $this->getFileName() . '.php';
    }

    protected function getStubName(): string
    {
        if ($this->option('sync')) {
            return '/job.stub';
        }

        return '/job-queued.stub';
    }

    /**
     * @return string
     */
    private function getFileName()
    {
        return Str::studly($this->argument('name'));
    }
}
