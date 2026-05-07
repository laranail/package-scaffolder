<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Commands;

use Illuminate\Console\Command;
use Simtabi\Laranail\PackageScaffolder\Services\Development\SecurityChecker;

/**
 * PackageSecurityCheckCommand - Security vulnerability scanning command
 *
 * Scans package for security vulnerabilities and issues
 */
class PackageSecurityCheckCommand extends Command
{
    protected $signature = 'packager:security-check
                            {path? : Path to package (defaults to current directory)}
                            {--format=text : Output format (text, json, html)}';

    protected $description = 'Scan package for security vulnerabilities';

    public function __construct(
        protected SecurityChecker $securityChecker
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $path = $this->argument('path') ?: getcwd();
        $format = $this->option('format');

        $this->info('Running security check...');
        $this->newLine();

        $results = $this->securityChecker->check($path);

        if ($format === 'json') {
            $this->line(json_encode($results, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        if ($format === 'html') {
            // Output HTML format
            $this->line('<html><body>');
            $this->displayResults($results);
            $this->line('</body></html>');

            return self::SUCCESS;
        }

        // Text format
        $this->displayResults($results);

        return $results['status'] === 'secure' ? self::SUCCESS : self::FAILURE;
    }

    protected function displayResults(array $results): void
    {
        $this->info('Security Check Results');
        $this->info('=====================');
        $this->newLine();

        $this->line("Status: <fg={$this->getStatusColor($results['status'])}>{$results['status']}</>");
        $this->line("Checked: {$results['checked_at']}");
        $this->newLine();

        if (empty($results['vulnerabilities']) && empty($results['issues'])) {
            $this->info('✓ No security issues detected');

            return;
        }

        if (! empty($results['vulnerabilities'])) {
            $this->error('Vulnerabilities Found:');
            foreach ($results['vulnerabilities'] as $package => $details) {
                $this->line("  ✗ {$package}");
                $this->line("    Version: {$details['version']}");
                $this->line("    Severity: {$details['severity']}");
                $this->line("    Description: {$details['description']}");
                $this->newLine();
            }
        }

        if (! empty($results['issues'])) {
            $this->warn('Code Issues Found:');
            foreach ($results['issues'] as $issue) {
                $this->line("  ⚠ {$issue}");
            }
        }
    }

    protected function getStatusColor(string $status): string
    {
        return match ($status) {
            'secure' => 'green',
            'vulnerable' => 'red',
            'warning' => 'yellow',
            default => 'white',
        };
    }
}
