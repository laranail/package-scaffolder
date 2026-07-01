<?php

namespace Simtabi\Laranail\Package\Scaffolder\Traits;

use Illuminate\Support\Collection;
use Simtabi\Laranail\Package\Scaffolder\Support\Config\GenerateConfigReader;
use Simtabi\Laranail\Package\Scaffolder\Support\Module;

/**
 * Resolves the migration paths for a module so every migrate command
 * (migrate, rollback, reset, refresh, status) operates on the same set of
 * directories. This is what keeps forward and backward operations symmetric
 * and lets disabled modules be handled too (#2159).
 */
trait ModuleMigrationPaths
{
    /**
     * Get the migration paths for the given module, as absolute directories.
     *
     * @return array<int, string>
     */
    protected function getModuleMigrationPaths(Module $module): array
    {
        $modulePath = $module->getPath();

        // Paths registered via loadMigrationsFrom() in booted module providers.
        // This is what the core migrator already knows about and supports
        // modules that register migrations from more than one directory.
        $registered = collect(app('migrator')->paths())
            ->filter(fn (string $path) => str_starts_with($path, $modulePath));

        // The module's own migration directory. Disabled modules don't have a
        // booted provider, so their paths are never registered; including this
        // keeps every command working regardless of the module's status.
        $own = $module->getExtraPath($this->getModuleMigrationRelativePath($module));

        return $this->normalizeMigrationPaths($registered->push($own));
    }

    /**
     * Get a single migration target (a specific file under the module's
     * migration directory) when a subpath is provided, falling back to all
     * module migration paths otherwise.
     *
     * @return array<int, string>
     */
    protected function getModuleMigrationTarget(Module $module, ?string $subpath): array
    {
        if (empty($subpath)) {
            return $this->getModuleMigrationPaths($module);
        }

        $target = $module->getExtraPath($this->getModuleMigrationRelativePath($module).'/'.$subpath);

        return file_exists($target) ? [$target] : [];
    }

    /**
     * The module-relative migration directory, honouring an explicit override
     * in the module's module.json `migration.path`.
     */
    protected function getModuleMigrationRelativePath(Module $module): string
    {
        $config = $module->get('migration');

        return (is_array($config) && array_key_exists('path', $config))
            ? $config['path']
            : GenerateConfigReader::read('migration')->getPath();
    }

    /**
     * @param  Collection<int, string>  $paths
     * @return array<int, string>
     */
    private function normalizeMigrationPaths(Collection $paths): array
    {
        return $paths
            ->filter(fn (string $path) => is_dir($path))
            ->unique()
            ->values()
            ->all();
    }
}
