<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Services\Development;

use Illuminate\Support\Facades\File;
use Simtabi\Laranail\PackageTools\Contracts\AnalyzerInterface;

/**
 * DependencyAnalyzer - Dependency analysis
 *
 * Analyzes package dependencies, circular dependencies, and dependency tree
 */
class DependencyAnalyzer implements AnalyzerInterface
{
    protected array $dependencies = [];

    protected array $circularDependencies = [];

    /**
     * Analyze package dependencies
     *
     * @param  string  $packagePath  Path to package root
     * @return array<string, mixed> Analysis results
     */
    public function analyze(string $packagePath): array
    {
        $composerPath = $packagePath.'/composer.json';

        if (! File::exists($composerPath)) {
            return [
                'status' => 'error',
                'message' => 'composer.json not found',
            ];
        }

        $composer = json_decode(File::get($composerPath), true);

        $this->dependencies = [
            'require' => $composer['require'] ?? [],
            'require-dev' => $composer['require-dev'] ?? [],
        ];

        return [
            'dependencies' => $this->dependencies,
            'total_dependencies' => count($this->dependencies['require']),
            'dev_dependencies' => count($this->dependencies['require-dev']),
            'circular_dependencies' => $this->detectCircularDependencies(),
            'outdated' => $this->checkOutdated(),
        ];
    }

    /**
     * Detect circular dependencies
     *
     * @return array<string>
     */
    public function detectCircularDependencies(): array
    {
        // This is a simplified implementation
        // A full implementation would parse use statements and class dependencies
        $this->circularDependencies = [];

        return $this->circularDependencies;
    }

    /**
     * Check for outdated dependencies
     *
     * @return array<string, mixed>
     */
    protected function checkOutdated(): array
    {
        // This would integrate with Composer to check for updates
        // Placeholder implementation
        return [];
    }

    /**
     * Get dependency tree
     *
     * @return array<string, mixed>
     */
    public function getDependencyTree(): array
    {
        return $this->dependencies;
    }

    /**
     * `AnalyzerInterface::getReport` — render dependency findings.
     *
     * @param  array<string, mixed>  $findings
     */
    public function getReport(array $findings, string $format = 'json'): string
    {
        return match ($format) {
            'json' => json_encode($findings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}',
            'text' => $this->renderText($findings),
            default => throw new \InvalidArgumentException("Unsupported report format: {$format}"),
        };
    }

    /**
     * @param  array<string, mixed>  $findings
     */
    private function renderText(array $findings, string $prefix = ''): string
    {
        $lines = [];
        foreach ($findings as $key => $value) {
            $label = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            if (is_array($value)) {
                $lines[] = $this->renderText($value, $label);

                continue;
            }
            $rendered = is_bool($value) ? ($value ? 'true' : 'false') : (is_scalar($value) || $value === null ? (string) $value : json_encode($value));
            $lines[] = "{$label} = {$rendered}";
        }

        return implode("\n", array_filter($lines, static fn (string $l): bool => $l !== ''));
    }
}
