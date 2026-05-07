<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Concerns\Package;

use Simtabi\Laranail\PackageScaffolder\Services\Development\SecurityChecker;

/**
 * HasSecurityChecking - Security vulnerability checking
 *
 * Enables security vulnerability scanning for packages
 */
trait HasSecurityChecking
{
    protected ?SecurityChecker $securityCheckerService = null;

    /**
     * Run security check on package
     *
     * @return array<string, mixed> Security check results
     */
    public function runSecurityCheck(): array
    {
        $checker = $this->getSecurityCheckerService();

        $packagePath = $this->packageBasePath();

        return $checker->check($packagePath);
    }

    /**
     * Scan package dependencies for vulnerabilities
     *
     * @return array<string, mixed>
     */
    public function scanDependencies(): array
    {
        $checker = $this->getSecurityCheckerService();

        $packagePath = $this->packageBasePath();

        return $checker->scanDependencies($packagePath);
    }

    /**
     * Get detected security vulnerabilities
     *
     * @return array<string, mixed>
     */
    public function getVulnerabilities(): array
    {
        $checker = $this->getSecurityCheckerService();

        return $checker->getVulnerabilities();
    }

    /**
     * Check if package has security vulnerabilities
     */
    public function hasVulnerabilities(): bool
    {
        $vulnerabilities = $this->getVulnerabilities();

        return ! empty($vulnerabilities);
    }

    /**
     * Display security check results
     */
    public function displaySecurityStatus(): static
    {
        $results = $this->runSecurityCheck();

        if ($results['status'] === 'secure') {
            echo "✓ No security issues detected\n";
        } else {
            echo "✗ Security issues found:\n";
            foreach ($results['vulnerabilities'] as $package => $details) {
                echo "  - {$package}: {$details['description']}\n";
            }
        }

        return $this;
    }

    /**
     * Get or create security checker service instance
     */
    protected function getSecurityCheckerService(): SecurityChecker
    {
        if (! $this->securityCheckerService) {
            $this->securityCheckerService = app(SecurityChecker::class);
        }

        return $this->securityCheckerService;
    }

    /**
     * Get package base path
     *
     * @param  string  $path  Optional path to append
     */
    abstract protected function packageBasePath(string $path = ''): string;
}
