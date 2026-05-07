<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Services\BugHunter;

use Illuminate\Support\Facades\File;

/**
 * NamespaceAnalyzer - Namespace consistency checker
 *
 * Validates PSR-4 compliance and namespace consistency
 */
class NamespaceAnalyzer
{
    /**
     * Analyze namespaces in package
     *
     * @param  string  $path  Package path
     * @return array<string> List of issues found
     */
    public function analyzeNamespaces(string $path): array
    {
        $issues = [];
        $srcPath = $path.'/src';

        if (! File::isDirectory($srcPath)) {
            return ['Source directory not found'];
        }

        // Get PSR-4 mapping from composer.json
        $psr4Mapping = $this->getPsr4Mapping($path);

        if (empty($psr4Mapping)) {
            return ['No PSR-4 autoload configuration found in composer.json'];
        }

        $files = File::allFiles($srcPath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $fileIssues = $this->analyzeFile($file->getPathname(), $psr4Mapping, $srcPath);
                $issues = array_merge($issues, $fileIssues);
            }
        }

        return $issues;
    }

    /**
     * Analyze single file for namespace issues
     *
     * @param  string  $filePath  File path
     * @param  array  $psr4Mapping  PSR-4 mapping
     * @param  string  $srcPath  Source directory path
     * @return array<string>
     */
    protected function analyzeFile(string $filePath, array $psr4Mapping, string $srcPath): array
    {
        $issues = [];
        $content = File::get($filePath);

        // Extract namespace from file
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $declaredNamespace = trim($matches[1]);

            // Calculate expected namespace based on file path
            $relativePath = str_replace($srcPath, '', dirname($filePath));
            $relativePath = trim($relativePath, '/\\');

            foreach ($psr4Mapping as $namespacePrefix => $mappedPath) {
                $expectedNamespace = $namespacePrefix;
                if ($relativePath) {
                    $expectedNamespace .= '\\'.str_replace(['/', '\\'], '\\', $relativePath);
                }
                $expectedNamespace = rtrim($expectedNamespace, '\\');

                if ($declaredNamespace !== $expectedNamespace) {
                    $issues[] = "Namespace mismatch in {$filePath}: declared '{$declaredNamespace}', expected '{$expectedNamespace}'";
                }
            }
        } else {
            $issues[] = "No namespace declaration found in {$filePath}";
        }

        return $issues;
    }

    /**
     * Detect namespace mismatches
     *
     * @param  string  $path  Package path
     * @return array<string>
     */
    public function detectMismatches(string $path): array
    {
        return $this->analyzeNamespaces($path);
    }

    /**
     * Validate PSR-4 compliance
     *
     * @param  string  $path  Package path
     * @return array<string>
     */
    public function validatePsr4Compliance(string $path): array
    {
        return $this->analyzeNamespaces($path);
    }

    /**
     * Suggest fixes for namespace issues
     *
     * @param  array  $issues  List of issues
     * @return array<string>
     */
    public function suggestFixes(array $issues): array
    {
        $fixes = [];

        foreach ($issues as $issue) {
            if (str_contains($issue, 'Namespace mismatch')) {
                $fixes[] = 'Run composer dump-autoload after fixing namespace declarations';
            } elseif (str_contains($issue, 'No namespace declaration')) {
                $fixes[] = 'Add proper namespace declaration at the top of the file';
            }
        }

        return array_unique($fixes);
    }

    /**
     * Get PSR-4 mapping from composer.json
     *
     * @param  string  $packagePath  Package path
     * @return array<string, string>
     */
    protected function getPsr4Mapping(string $packagePath): array
    {
        $composerPath = $packagePath.'/composer.json';

        if (! File::exists($composerPath)) {
            return [];
        }

        $composer = json_decode(File::get($composerPath), true);

        return $composer['autoload']['psr-4'] ?? [];
    }
}
