<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;
use Simtabi\Laranail\Package\Scaffolder\Support\Artifacts\ArtifactGenerator;
use Simtabi\Laranail\Package\Scaffolder\Support\Artifacts\GenerationRequest;
use Simtabi\Laranail\Package\Scaffolder\Support\Artifacts\HostComposerWriter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

/**
 * Generate a module / package / plugin from the blueprint template. Runs
 * interactively (a guided TUI) or unattended (flags); both share one
 * validation + generation path via the laranail/console interaction service, so
 * a flag and its prompt can never drift. Missing required input in
 * non-interactive mode fails loudly; a non-TTY defaults to non-interactive.
 */
class MakeArtifactCommand extends Command
{
    use SupportsNamespacedNames;

    protected $name = 'laranail::package-scaffolder.new';

    /** @var list<string> */
    protected $aliases = ['make:artifact'];

    protected $description = 'Generate a module, package or plugin from the blueprint.';

    public function handle(): int
    {
        $nonInteractive = ! $this->input->isInteractive() || (bool) $this->option('no-interaction');
        $io = $this->services->interaction()->setNonInteractive($nonInteractive);

        try {
            $type = $this->resolveChoice($io, 'type', 'Artifact type', array_keys((array) config('artifacts.kinds')), $nonInteractive);
            $plugin = $this->resolvePlugin($io, $nonInteractive);
            $name = $this->resolveName($io, $nonInteractive);
            $entity = $this->resolveEntity($io, $name, $nonInteractive);
            $namespace = $this->resolveNamespace($io, $nonInteractive);
            $features = $this->resolveFeatures($io, $nonInteractive);
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $vendor = Str::lower((string) ($this->option('vendor') ?: config('modules.composer.vendor') ?: 'laranail'));

        $request = new GenerationRequest($type, $plugin, $features, $name, $namespace, $vendor, (bool) $this->option('force'), $entity);

        $base = $this->option('path') ?: base_path((string) config("artifacts.kinds.{$type}"));
        $target = rtrim($base, '/').'/'.$request->studly();
        $source = dirname(__DIR__, 2).'/stubs/blueprint';

        // Artifact identity is keyed by name across ALL containers (module.json
        // name + activator), so a name must be globally unique. Skipped for an
        // explicit --path (caller owns the location) or --force.
        if (! $this->option('force') && ! $this->option('path')) {
            $files = new Filesystem;
            foreach ((array) config('artifacts.kinds') as $containerPath) {
                $existing = base_path((string) $containerPath).'/'.$request->studly();
                if ($files->isDirectory($existing)) {
                    $this->components->error(sprintf(
                        'An artifact named [%s] already exists at [%s]. Names must be unique across all containers.',
                        $request->studly(),
                        $existing,
                    ));

                    return self::FAILURE;
                }
            }
        }

        try {
            (new ArtifactGenerator(new Filesystem, (array) config('artifacts'), $this->pintBinary()))
                ->generate($request, $source, $target);
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        if (! $this->option('no-repo')) {
            (new HostComposerWriter(new Filesystem))->wire(base_path('composer.json'));
        }

        $this->components->info(sprintf('Generated %s [%s] (%s\\%s) at %s', $type, $request->studly(), $namespace, $request->studly(), $target));

        return self::SUCCESS;
    }

    /** The Pint binary to format generated output with (scaffolder's, else host's). */
    private function pintBinary(): ?string
    {
        foreach ([dirname(__DIR__, 2).'/vendor/bin/pint', base_path('vendor/bin/pint')] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function resolveChoice($io, string $option, string $label, array $options, bool $nonInteractive): string
    {
        $value = $this->option($option);

        if ($value !== null && $value !== '') {
            if (! in_array($value, $options, true)) {
                throw new \InvalidArgumentException("--{$option} must be one of: ".implode(', ', $options).'.');
            }

            return (string) $value;
        }

        if ($nonInteractive) {
            throw new \InvalidArgumentException("--{$option} is required in non-interactive mode (one of: ".implode(', ', $options).').');
        }

        return $io->askSelect($label, $options, 0);
    }

    /**
     * Panel is an independent, mutually-exclusive choice for ANY artifact shape:
     * nova, filament, or none. A single flag value can never select both; an
     * invalid value is rejected. Defaults to `none` (a panel-free artifact).
     */
    private function resolvePlugin($io, bool $nonInteractive): string
    {
        $types = (array) config('artifacts.plugin_types'); // nova | filament | none
        $value = $this->option('plugin');

        if ($value !== null && $value !== '') {
            if (! in_array($value, $types, true)) {
                throw new \InvalidArgumentException('--plugin must be one of: '.implode(', ', $types).'.');
            }

            return (string) $value;
        }

        if ($nonInteractive) {
            return 'none';
        }

        $default = array_search('none', array_values($types), true);

        return $io->askSelect('Admin panel', $types, $default === false ? 0 : $default);
    }

    private function resolveName($io, bool $nonInteractive): string
    {
        $name = (string) ($this->argument('name') ?? '');

        if ($name === '') {
            if ($nonInteractive) {
                throw new \InvalidArgumentException('The "name" argument is required in non-interactive mode.');
            }

            $name = $io->askText('Artifact name (StudlyCase)', 'Blog', '', true);
        }

        if (Str::studly($name) === '') {
            throw new \InvalidArgumentException('The artifact name is invalid.');
        }

        return $name;
    }

    /**
     * The primary entity (Post → {entity}). The blueprint decouples it from the
     * artifact name (Blog ≠ Post), so it's prompted; the default is the singular of
     * the artifact name.
     */
    private function resolveEntity($io, string $name, bool $nonInteractive): string
    {
        $default = Str::singular(Str::studly($name));
        $entity = (string) ($this->option('entity') ?? '');

        if ($entity === '') {
            $entity = $nonInteractive ? $default : $io->askText('Primary entity (StudlyCase)', $default, $default, false);
        }

        $entity = Str::studly($entity !== '' ? $entity : $default);

        if ($entity === '') {
            throw new \InvalidArgumentException('The entity name is invalid.');
        }

        return $entity;
    }

    private function resolveNamespace($io, bool $nonInteractive): string
    {
        $ns = (string) ($this->option('namespace') ?? '');
        $default = (string) config('artifacts.default_namespace', 'Modules');

        if ($ns === '') {
            $ns = $nonInteractive ? $default : $io->askText('Root namespace', $default, $default, false);
        }

        $ns = trim($ns !== '' ? $ns : $default, '\\');

        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\\\\[A-Za-z_][A-Za-z0-9_]*)*$/', $ns)) {
            throw new \InvalidArgumentException("Invalid root namespace [{$ns}].");
        }

        return $ns;
    }

    /**
     * @return list<string>
     */
    private function resolveFeatures($io, bool $nonInteractive): array
    {
        $selectable = array_keys((array) config('artifacts.features'));
        $selectable[] = 'livewire'; // sub-toggle, independently selectable

        $csv = (string) ($this->option('features') ?? '');
        $repeated = (array) $this->option('feature');

        if ($csv !== '') {
            $list = array_map('trim', explode(',', $csv));
        } elseif ($repeated !== []) {
            $list = $repeated;
        } elseif ($nonInteractive) {
            return $selectable; // default = full blueprint
        } else {
            $list = $io->askMultiSelect('Features', $selectable, $selectable);
        }

        $list = array_values(array_filter($list, static fn ($f) => $f !== ''));
        $unknown = array_diff($list, $selectable);

        if ($unknown !== []) {
            throw new \InvalidArgumentException('Unknown feature(s): '.implode(', ', $unknown).'. Valid: '.implode(', ', $selectable).'.');
        }

        return $this->resolveRequires(array_values(array_unique($list)));
    }

    /**
     * Pull in each selected feature's `requires` (transitively), so a dependency
     * can never be silently missing (e.g. selecting `livewire` pulls in `web-ui`).
     *
     * @param  list<string>  $list
     * @return list<string>
     */
    private function resolveRequires(array $list): array
    {
        $requires = $this->requiresMap();
        $resolved = $list;

        do {
            $added = false;
            foreach ($resolved as $feature) {
                foreach ($requires[$feature] ?? [] as $dep) {
                    if (! in_array($dep, $resolved, true)) {
                        $resolved[] = $dep;
                        $added = true;
                    }
                }
            }
        } while ($added);

        return array_values(array_unique($resolved));
    }

    /**
     * Feature → its required features, read from the config catalog (top-level
     * features and their sub-toggles).
     *
     * @return array<string, list<string>>
     */
    private function requiresMap(): array
    {
        $map = [];
        foreach ((array) config('artifacts.features') as $key => $def) {
            $map[$key] = array_values((array) ($def['requires'] ?? []));
            foreach ((array) ($def['sub'] ?? []) as $subKey => $subDef) {
                $map[$subKey] = array_values((array) ($subDef['requires'] ?? []));
            }
        }

        return $map;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::OPTIONAL, 'The artifact name (StudlyCase).'],
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    protected function getOptions()
    {
        return [
            ['type', null, InputOption::VALUE_REQUIRED, 'package | module | plugin'],
            ['plugin', null, InputOption::VALUE_REQUIRED, 'Admin panel: nova | filament | none (mutually exclusive; default none)'],
            ['features', null, InputOption::VALUE_REQUIRED, 'Comma-separated feature list (default: all).'],
            ['feature', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Repeatable feature flag.'],
            ['entity', null, InputOption::VALUE_REQUIRED, 'Primary entity name (default: singular of the artifact name).'],
            ['namespace', null, InputOption::VALUE_REQUIRED, 'Root PHP namespace (default from config).'],
            ['vendor', null, InputOption::VALUE_REQUIRED, 'Composer vendor (default from config).'],
            ['path', null, InputOption::VALUE_REQUIRED, 'Override the container base path.'],
            ['force', null, InputOption::VALUE_NONE, 'Overwrite an existing target.'],
            ['no-repo', null, InputOption::VALUE_NONE, 'Skip wiring the host composer.json (merge-plugin + path repositories).'],
        ];
    }
}
