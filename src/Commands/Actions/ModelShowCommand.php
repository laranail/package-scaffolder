<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Commands\Actions;

use Override;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

use function Laravel\Prompts\search;

use Illuminate\Support\Facades\File;
use Illuminate\Database\Console\ShowModelCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;

#[AsCommand('laranail::package-scaffolder.model-show', 'Show information about an Eloquent model in modules')]
class ModelShowCommand extends ShowModelCommand implements PromptsForMissingInput
{
    use SupportsNamespacedNames;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laranail::package-scaffolder.model-show';

    protected $aliases = ['module:model-show'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show information about an Eloquent model in modules';

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'laranail::package-scaffolder.model-show {model : The model to show}
                {--database= : The database connection to use}
                {--json : Output the model as JSON}';

    public function findModels(string $model): Collection
    {
        $pattern = sprintf(
            '%s/*/%s/%s.php',
            config('laranail.package-scaffolder.modules.paths.modules'),
            config('laranail.package-scaffolder.modules.paths.generator.model.path'),
            $model,
        );

        return collect(File::glob($pattern))
            ->map($this->formatModuleNamespace(...));
    }

    #[Override]
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'model' => fn (): int|string => search(
                label: 'Select Model',
                options: fn (string $search_value) => $this->findModels(
                    Str::of($search_value)->wrap('', '*')->toString(),
                )->toArray(),
                placeholder: 'type some thing',
                required: 'You must select one Model',
            ),
        ];
    }

    private function formatModuleNamespace(string $path): string
    {
        return
            Str::of($path)
                ->after(base_path() . DIRECTORY_SEPARATOR)
                ->replace(
                    [config('laranail.package-scaffolder.modules.paths.app_folder'), '/', '.php'],
                    ['', '\\', ''],
                )->toString();
    }
}
