<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Services;

use Illuminate\Support\Facades\File;
use Simtabi\Laranail\PackageScaffolder\Contracts\ServiceInterface;

/**
 * BlueprintService - Package blueprint parsing and validation
 *
 * Handles parsing and validation of package blueprints for generation
 */
class BlueprintService implements ServiceInterface
{
    /**
     * Parse blueprint configuration
     *
     * @param  array  $blueprint  Blueprint data
     * @return array Parsed blueprint
     */
    public function parse(array $blueprint): array
    {
        return [
            'vendor' => $blueprint['vendor'] ?? 'vendor',
            'package' => $blueprint['package'] ?? 'package',
            'namespace' => $blueprint['namespace'] ?? $this->generateNamespace($blueprint),
            'description' => $blueprint['description'] ?? '',
            'author' => $blueprint['author'] ?? [],
            'features' => $blueprint['features'] ?? [],
            'structure' => $blueprint['structure'] ?? $this->getDefaultStructure(),
        ];
    }

    /**
     * Validate blueprint
     *
     * @param  array  $blueprint  Blueprint to validate
     * @return bool True if valid
     */
    public function validate(array $blueprint): bool
    {
        $required = ['vendor', 'package'];

        foreach ($required as $field) {
            if (empty($blueprint[$field])) {
                return false;
            }
        }

        // Validate naming conventions
        if (! $this->isValidName($blueprint['vendor']) || ! $this->isValidName($blueprint['package'])) {
            return false;
        }

        return true;
    }

    /**
     * Get directories from blueprint
     *
     * @param  array  $blueprint  Blueprint data
     * @return array<string> List of directories
     */
    public function getDirectories(array $blueprint): array
    {
        $structure = $blueprint['structure'] ?? $this->getDefaultStructure();

        return $structure['directories'] ?? [];
    }

    /**
     * Get file mapping from blueprint
     *
     * @param  array  $blueprint  Blueprint data
     * @return array<string, string> File mapping [stub => target]
     */
    public function getFileMapping(array $blueprint): array
    {
        $structure = $blueprint['structure'] ?? $this->getDefaultStructure();

        return $structure['files'] ?? [];
    }

    /**
     * Resolve complete package structure
     *
     * @param  array  $blueprint  Blueprint data
     * @return array Complete structure
     */
    public function resolveStructure(array $blueprint): array
    {
        $parsed = $this->parse($blueprint);

        return [
            'package_name' => "{$parsed['vendor']}/{$parsed['package']}",
            'namespace' => $parsed['namespace'],
            'directories' => $this->getDirectories($parsed),
            'files' => $this->getFileMapping($parsed),
            'features' => $parsed['features'],
        ];
    }

    /**
     * Generate namespace from vendor and package
     *
     * @param  array  $blueprint  Blueprint data
     * @return string Namespace
     */
    protected function generateNamespace(array $blueprint): string
    {
        $vendor = ucfirst($blueprint['vendor'] ?? 'Vendor');
        $package = ucfirst($blueprint['package'] ?? 'Package');

        return "{$vendor}\\{$package}";
    }

    /**
     * Get default package structure
     *
     * @return array Default structure
     */
    protected function getDefaultStructure(): array
    {
        return config('generator.template', [
            'directories' => [
                'src',
                'config',
                'database/migrations',
                'resources/views',
                'tests',
            ],
            'files' => [
                'composer.json',
                'README.md',
            ],
        ]);
    }

    /**
     * Validate package name
     *
     * @param  string  $name  Name to validate
     */
    protected function isValidName(string $name): bool
    {
        return preg_match('/^[a-z0-9\-]+$/', $name) === 1;
    }
}
