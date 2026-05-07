<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Services\BugHunter;

use Illuminate\Support\Facades\File;

/**
 * CodeQualityAnalyzer - Code quality checks
 *
 * Detects code smells, complexity issues, and duplication
 */
class CodeQualityAnalyzer
{
    /**
     * Analyze code complexity
     *
     * @param  string  $path  Package path
     * @return array<string> List of issues found
     */
    public function analyzeComplexity(string $path): array
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
     * Analyze single file for complexity issues
     *
     * @param  string  $filePath  File path
     * @return array<string>
     */
    protected function analyzeFile(string $filePath): array
    {
        $issues = [];
        $content = File::get($filePath);
        $lines = explode("\n", $content);
        $relativePath = basename($filePath);

        // Check file length
        if (count($lines) > 500) {
            $issues[] = "File {$relativePath} is too long (".count($lines).' lines). Consider splitting into smaller files.';
        }

        // Check for very long lines
        foreach ($lines as $lineNum => $line) {
            if (strlen($line) > 120) {
                $issues[] = "Line too long in {$relativePath}:{$lineNum} (".strlen($line).' characters)';
            }
        }

        // Check for deeply nested code (simplified)
        $maxNesting = 0;
        $currentNesting = 0;
        foreach ($lines as $line) {
            $currentNesting += substr_count($line, '{');
            $currentNesting -= substr_count($line, '}');
            if ($currentNesting > $maxNesting) {
                $maxNesting = $currentNesting;
            }
        }

        if ($maxNesting > 5) {
            $issues[] = "Deep nesting detected in {$relativePath} (level {$maxNesting}). Consider refactoring.";
        }

        return $issues;
    }

    /**
     * Detect code smells
     *
     * @param  string  $path  Package path
     * @return array<string>
     */
    public function detectCodeSmells(string $path): array
    {
        $smells = [];
        $srcPath = $path.'/src';

        if (! File::isDirectory($srcPath)) {
            return [];
        }

        $files = File::allFiles($srcPath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $content = File::get($file->getPathname());
                $relativePath = basename($file->getPathname());

                // Check for god classes (too many methods)
                $methodCount = substr_count($content, 'function ');
                if ($methodCount > 20) {
                    $smells[] = "Possible god class in {$relativePath} ({$methodCount} methods)";
                }

                // Check for commented out code
                if (preg_match_all('/^\s*\/\/.*?(function|class|if|for|while)/m', $content, $matches)) {
                    $smells[] = "Commented out code detected in {$relativePath}";
                }
            }
        }

        return $smells;
    }

    /**
     * Detect code duplication (simplified)
     *
     * @param  string  $path  Package path
     * @return array<string>
     */
    public function detectDuplication(string $path): array
    {
        // This would require more sophisticated analysis
        // For now, return empty array
        return [];
    }

    /**
     * Calculate code metrics
     *
     * @param  string  $path  Package path
     * @return array<string, mixed>
     */
    public function calculateMetrics(string $path): array
    {
        $srcPath = $path.'/src';

        if (! File::isDirectory($srcPath)) {
            return [];
        }

        $files = File::allFiles($srcPath);
        $totalLines = 0;
        $totalClasses = 0;
        $totalMethods = 0;

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $content = File::get($file->getPathname());
                $totalLines += count(explode("\n", $content));
                $totalClasses += substr_count($content, 'class ');
                $totalClasses += substr_count($content, 'interface ');
                $totalClasses += substr_count($content, 'trait ');
                $totalMethods += substr_count($content, 'function ');
            }
        }

        return [
            'total_lines' => $totalLines,
            'total_classes' => $totalClasses,
            'total_methods' => $totalMethods,
            'avg_methods_per_class' => $totalClasses > 0 ? round($totalMethods / $totalClasses, 2) : 0,
        ];
    }
}
