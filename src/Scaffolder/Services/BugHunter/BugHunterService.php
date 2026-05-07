<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Services\BugHunter;

use Illuminate\Support\Facades\File;

/**
 * BugHunterService - Master bug detection orchestrator
 *
 * Orchestrates all bug detection services to scan packages for issues
 */
class BugHunterService
{
    public function __construct(
        protected NamespaceAnalyzer $namespaceAnalyzer,
        protected MethodSignatureAnalyzer $methodSignatureAnalyzer,
        protected CodeQualityAnalyzer $codeQualityAnalyzer
    ) {}

    /**
     * Scan package for all types of issues
     *
     * @param  string  $packagePath  Path to package root
     * @return array<string, mixed> Scan results
     */
    public function scanPackage(string $packagePath): array
    {
        if (! File::isDirectory($packagePath)) {
            return [
                'status' => 'error',
                'message' => 'Package path does not exist',
            ];
        }

        $results = [
            'namespace_issues' => $this->detectNamespaceIssues($packagePath),
            'method_signature_issues' => $this->detectMethodSignatureInconsistencies($packagePath),
            'code_quality_issues' => $this->detectCodeQualityIssues($packagePath),
            'hardcoded_values' => $this->detectHardcodedValues($packagePath),
            'security_issues' => $this->detectSecurityVulnerabilities($packagePath),
        ];

        $totalIssues = array_sum(array_map('count', $results));

        return [
            'status' => $totalIssues === 0 ? 'clean' : 'issues_found',
            'total_issues' => $totalIssues,
            'results' => $results,
            'scanned_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Detect namespace issues
     *
     * @param  string  $packagePath  Package path
     * @return array<string>
     */
    public function detectNamespaceIssues(string $packagePath): array
    {
        return $this->namespaceAnalyzer->analyzeNamespaces($packagePath);
    }

    /**
     * Detect method signature inconsistencies
     *
     * @param  string  $packagePath  Package path
     * @return array<string>
     */
    public function detectMethodSignatureInconsistencies(string $packagePath): array
    {
        return $this->methodSignatureAnalyzer->analyzeSignatures($packagePath);
    }

    /**
     * Detect code quality issues
     *
     * @param  string  $packagePath  Package path
     * @return array<string>
     */
    protected function detectCodeQualityIssues(string $packagePath): array
    {
        return $this->codeQualityAnalyzer->analyzeComplexity($packagePath);
    }

    /**
     * Detect hardcoded values (project-specific strings)
     *
     * @param  string  $packagePath  Package path
     * @return array<string>
     */
    public function detectHardcodedValues(string $packagePath): array
    {
        $issues = [];
        $srcPath = $packagePath.'/src';

        if (! File::isDirectory($srcPath)) {
            return $issues;
        }

        $files = File::allFiles($srcPath);

        // Patterns for common hardcoded values
        $patterns = [
            '/[\'"]Simtabi\\\\Tusente\\\\/' => 'Hardcoded "Tusente" namespace',
            '/[\'"]tusente-/' => 'Hardcoded "tusente-" prefix',
            '/[\'"]modules\//' => 'Hardcoded "modules/" path',
            '/[\'"]packages\//' => 'Hardcoded "packages/" path',
            '/[\'"]App\\\\/' => 'Hardcoded "App" namespace (except in comments)',
        ];

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $content = File::get($file->getPathname());
                $relativePath = str_replace($packagePath.'/', '', $file->getPathname());

                foreach ($patterns as $pattern => $description) {
                    if (preg_match($pattern, $content, $matches)) {
                        // Skip if it's in a comment
                        $lines = explode("\n", $content);
                        foreach ($lines as $lineNum => $line) {
                            if (preg_match($pattern, $line)) {
                                $trimmed = trim($line);
                                if (! str_starts_with($trimmed, '//') && ! str_starts_with($trimmed, '*')) {
                                    $issues[] = "{$description} in {$relativePath}:{$lineNum}";
                                }
                            }
                        }
                    }
                }
            }
        }

        return array_unique($issues);
    }

    /**
     * Detect security vulnerabilities
     *
     * @param  string  $packagePath  Package path
     * @return array<string>
     */
    public function detectSecurityVulnerabilities(string $packagePath): array
    {
        $issues = [];
        $srcPath = $packagePath.'/src';

        if (! File::isDirectory($srcPath)) {
            return $issues;
        }

        $files = File::allFiles($srcPath);

        $dangerousPatterns = [
            '/\beval\s*\(/' => 'Dangerous eval() function',
            '/\bexec\s*\(/' => 'exec() function - ensure input is sanitized',
            '/\bshell_exec\s*\(/' => 'shell_exec() function - ensure input is sanitized',
            '/\bsystem\s*\(/' => 'system() function - ensure input is sanitized',
            '/\bpassthru\s*\(/' => 'passthru() function - ensure input is sanitized',
        ];

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $content = File::get($file->getPathname());
                $relativePath = str_replace($packagePath.'/', '', $file->getPathname());

                foreach ($dangerousPatterns as $pattern => $description) {
                    if (preg_match($pattern, $content)) {
                        $issues[] = "{$description} in {$relativePath}";
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * Generate bug report
     *
     * @param  array  $issues  Scan results
     * @param  string  $format  Report format (text, json, html)
     * @return string Formatted report
     */
    public function generateReport(array $issues, string $format = 'text'): string
    {
        return match ($format) {
            'json' => json_encode($issues, JSON_PRETTY_PRINT),
            'html' => $this->generateHtmlReport($issues),
            default => $this->generateTextReport($issues),
        };
    }

    /**
     * Generate text report
     *
     * @param  array  $issues  Issues array
     */
    protected function generateTextReport(array $issues): string
    {
        $report = "Bug Hunter Report\n";
        $report .= "==================\n\n";
        $report .= "Status: {$issues['status']}\n";
        $report .= "Total Issues: {$issues['total_issues']}\n";
        $report .= "Scanned At: {$issues['scanned_at']}\n\n";

        foreach ($issues['results'] as $category => $items) {
            $report .= strtoupper(str_replace('_', ' ', $category)).":\n";
            if (empty($items)) {
                $report .= "  ✓ No issues found\n\n";
            } else {
                foreach ($items as $item) {
                    $report .= "  ✗ {$item}\n";
                }
                $report .= "\n";
            }
        }

        return $report;
    }

    /**
     * Generate HTML report
     *
     * @param  array  $issues  Issues array
     */
    protected function generateHtmlReport(array $issues): string
    {
        // Simplified HTML report
        $html = '<h1>Bug Hunter Report</h1>';
        $html .= "<p>Status: {$issues['status']}</p>";
        $html .= "<p>Total Issues: {$issues['total_issues']}</p>";

        foreach ($issues['results'] as $category => $items) {
            $html .= '<h2>'.ucwords(str_replace('_', ' ', $category)).'</h2>';
            if (empty($items)) {
                $html .= "<p style='color:green'>✓ No issues found</p>";
            } else {
                $html .= '<ul>';
                foreach ($items as $item) {
                    $html .= "<li style='color:red'>{$item}</li>";
                }
                $html .= '</ul>';
            }
        }

        return $html;
    }
}
