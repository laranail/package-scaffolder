<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Services\BugHunter;

use Illuminate\Support\Facades\File;

/**
 * MethodSignatureAnalyzer - Method signature validator
 *
 * Detects missing type hints, return types, and signature inconsistencies
 */
class MethodSignatureAnalyzer
{
    /**
     * Analyze method signatures in package
     *
     * @param  string  $path  Package path
     * @return array<string> List of issues found
     */
    public function analyzeSignatures(string $path): array
    {
        $issues = [];
        $srcPath = $path.'/src';

        if (! File::isDirectory($srcPath)) {
            return [];
        }

        $files = File::allFiles($srcPath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $fileIssues = $this->analyzeFile($file->getPathname());
                $issues = array_merge($issues, $fileIssues);
            }
        }

        return $issues;
    }

    /**
     * Analyze single file for method signature issues
     *
     * @param  string  $filePath  File path
     * @return array<string>
     */
    protected function analyzeFile(string $filePath): array
    {
        $issues = [];
        $content = File::get($filePath);
        $lines = explode("\n", $content);

        // Find method declarations
        foreach ($lines as $lineNum => $line) {
            // Simple regex for method detection (not perfect but good enough)
            if (preg_match('/^\s*(public|protected|private)\s+function\s+(\w+)\s*\((.*?)\)(\s*:\s*(\S+))?/', $line, $matches)) {
                $visibility = $matches[1];
                $methodName = $matches[2];
                $parameters = $matches[3];
                $returnType = $matches[5] ?? null;

                $relativePath = basename($filePath);

                // Check for missing return type (excluding __construct, __destruct, etc.)
                if (! $returnType && ! str_starts_with($methodName, '__')) {
                    $issues[] = "Missing return type in method {$methodName}() in {$relativePath}:{$lineNum}";
                }

                // Check parameters for missing types
                if ($parameters && ! str_contains($parameters, '...')) {
                    $params = explode(',', $parameters);
                    foreach ($params as $param) {
                        $param = trim($param);
                        // Skip if parameter has type hint or is variadic
                        if ($param && ! preg_match('/^(array|string|int|float|bool|object|callable|\w+\s+)/', $param)) {
                            $issues[] = "Missing type hint for parameter in method {$methodName}() in {$relativePath}:{$lineNum}";
                        }
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * Detect methods with missing type hints
     *
     * @param  string  $path  Package path
     * @return array<string>
     */
    public function detectMissingTypes(string $path): array
    {
        return $this->analyzeSignatures($path);
    }

    /**
     * Detect inconsistent return types
     *
     * @param  string  $path  Package path
     * @return array<string>
     */
    public function detectInconsistentReturns(string $path): array
    {
        // Simplified implementation
        // Full implementation would parse actual return statements
        return [];
    }

    /**
     * Detect unused parameters
     *
     * @param  string  $path  Package path
     * @return array<string>
     */
    public function detectUnusedParameters(string $path): array
    {
        // Simplified implementation
        // Full implementation would parse method bodies
        return [];
    }
}
