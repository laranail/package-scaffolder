<?php

namespace Nwidart\Modules\Commands\Make;

use Illuminate\Support\Str;
use Nwidart\Modules\Support\Config\GenerateConfigReader;
use Nwidart\Modules\Support\Stub;
use Nwidart\Modules\Traits\CanClearModulesCache;
use Nwidart\Modules\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class SeedMakeCommand extends GeneratorCommand
{
    use CanClearModulesCache;
    use ModuleCommandTrait;

    protected $argumentName = 'name';

    /**
     * The console command name.
     */
    protected $name = 'module:make-seed';

    /**
     * The console command description.
     */
    protected $description = 'Create a new seeder for the specified module.';

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of seeder will be created.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            [
                'master',
                null,
                InputOption::VALUE_NONE,
                'Indicates the seeder will created is a master database seeder.',
            ],
            [
                'without-base',
                null,
                InputOption::VALUE_NONE,
                'Do not auto-generate the module base database seeder when it is missing.',
            ],
        ];
    }

    /**
     * Ensure the module's base database seeder exists before generating a
     * specific seeder, so newly created seeders always have a base to be
     * called from (#2147).
     */
    public function handle(): int
    {
        $autoBase = ! $this->option('master') && ! $this->option('without-base');

        // Capture the module before generating, since calling module:make-seed
        // for the base re-runs this same command instance and would otherwise
        // overwrite the current input.
        $module = $autoBase ? $this->getModuleName() : null;

        $result = parent::handle();

        if ($result === 0 && $module !== null) {
            $this->ensureBaseSeederExists($module);
        }

        return $result;
    }

    private function ensureBaseSeederExists(string $moduleName): void
    {
        $module = $this->laravel['modules']->findOrFail($moduleName);

        $seederPath = GenerateConfigReader::read('seeder');
        $baseName = Str::studly($module->getName()).'DatabaseSeeder';
        $basePath = $this->laravel['modules']->getModulePath($module->getName()).$seederPath->getPath().'/'.$baseName.'.php';

        if ($this->laravel['files']->exists($basePath)) {
            return;
        }

        $this->call('module:make-seed', [
            'name' => $module->getName(),
            'module' => $module->getName(),
            '--master' => true,
        ]);
    }

    protected function getTemplateContents(): mixed
    {
        $module = $this->laravel['modules']->findOrFail($this->getModuleName());

        return (new Stub('/seeder.stub', [
            'NAME' => $this->getSeederName(),
            'MODULE' => $this->getModuleName(),
            'NAMESPACE' => $this->getClassNamespace($module),

        ]))->render();
    }

    protected function getDestinationFilePath(): mixed
    {
        $this->clearCache();

        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $seederPath = GenerateConfigReader::read('seeder');

        return $path.$seederPath->getPath().'/'.$this->getSeederName().'.php';
    }

    /**
     * Get the seeder name.
     */
    private function getSeederName(): string
    {
        $string = $this->argument('name');
        $string .= $this->option('master') ? 'Database' : '';
        $suffix = 'Seeder';

        if (strpos($string, $suffix) === false) {
            $string .= $suffix;
        }

        return Str::studly($string);
    }

    /**
     * Get default namespace.
     */
    public function getDefaultNamespace(): string
    {
        return config('modules.paths.generator.seeder.namespace')
            ?? $this->strip_app_folder(config('modules.paths.generator.seeder.path', 'Database/Seeders'));
    }
}
