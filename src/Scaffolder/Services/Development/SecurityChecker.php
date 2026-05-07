<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Services\Development;

use Illuminate\Support\Facades\File;

/**
 * SecurityChecker - Security vulnerability scanning
 *
 * Checks packages and dependencies for known security vulnerabilities
 */
class SecurityChecker
{
    protected array $vulnerabilities = [];

    /**
     * Check package for security issues
     *
     * @param  string  $packagePath  Path to package root
     * @return array<string, mixed> Security scan results
     */
    public function check(string $packagePath): array
    {
        $this->vulnerabilities = [];

        // Check composer.lock exists
        $lockPath = $packagePath.'/composer.lock';
        if (! File::exists($lockPath)) {
            return [
                'status' => 'warning',
                'message' => 'No composer.lock found. Run composer install first.',
                'vulnerabilities' => [],
            ];
        }

        // Scan dependencies
        $dependencies = $this->scanDependencies($packagePath);

        // Check for common security issues
        $issues = $this->checkCommonIssues($packagePath);

        return [
            'status' => empty($this->vulnerabilities) && empty($issues) ? 'secure' : 'vulnerable',
            'vulnerabilities' => $this->vulnerabilities,
            'issues' => $issues,
            'checked_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Scan dependencies for vulnerabilities
     *
     * @param  string  $packagePath  Package path
     * @return array<string, mixed>
     */
    public function scanDependencies(string $packagePath): array
    {
        $composerPath = $packagePath.'/composer.json';

        if (! File::exists($composerPath)) {
            return [];
        }

        $composer = json_decode(File::get($composerPath), true);
        $dependencies = array_merge(
            $composer['require'] ?? [],
            $composer['require-dev'] ?? []
        );

        $vulnerable = [];

        foreach ($dependencies as $package => $version) {
            // Check against known vulnerable packages
            if ($this->isKnownVulnerable($package, $version)) {
                $vulnerable[$package] = [
                    'version' => $version,
                    'severity' => 'high',
                    'description' => 'Known vulnerability detected',
                ];
            }
        }

        $this->vulnerabilities = $vulnerable;

        return $vulnerable;
    }

    /**
     * Get detected vulnerabilities
     *
     * @return array<string, mixed>
     */
    public function getVulnerabilities(): array
    {
        return $this->vulnerabilities;
    }

    /**
     * Check for common security issues in code
     *
     * @param  string  $packagePath  Package path
     * @return array<string>
     */
    protected function checkCommonIssues(string $packagePath): array
    {
        $issues = [];

        // Check for eval() usage
        if ($this->hasPatternInFiles($packagePath, '/\beval\s*\(/')) {
            $issues[] = 'Potentially dangerous eval() function detected';
        }

        // Check for shell_exec() usage
        if ($this->hasPatternInFiles($packagePath, '/\bshell_exec\s*\(/')) {
            $issues[] = 'shell_exec() function detected - ensure input is sanitized';
        }

        // Check for unserialize() on user input
        if ($this->hasPatternInFiles($packagePath, '/\bunserialize\s*\(\s*\$_(GET|POST|REQUEST)/')) {
            $issues[] = 'Unsafe unserialize() on user input detected';
        }

        return $issues;
    }

    /**
     * Check if pattern exists in PHP files
     *
     * @param  string  $path  Directory path
     * @param  string  $pattern  Regex pattern
     */
    protected function hasPatternInFiles(string $path, string $pattern): bool
    {
        $srcPath = $path.'/src';

        if (! File::isDirectory($srcPath)) {
            return false;
        }

        $files = File::allFiles($srcPath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $content = File::get($file->getPathname());
                if (preg_match($pattern, $content)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if package/version is known to be vulnerable
     *
     * @param  string  $package  Package name
     * @param  string  $version  Version constraint
     */
    protected function isKnownVulnerable(string $package, string $version): bool
    {
        // This would typically query a vulnerability database
        // For now, this is a placeholder
        return false;
    }
}
