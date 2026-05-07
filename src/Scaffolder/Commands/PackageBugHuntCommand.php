<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Simtabi\Laranail\PackageScaffolder\Services\BugHunter\BugHunterService;

/**
 * PackageBugHuntCommand - Hunt for bugs and code issues
 *
 * Comprehensive bug detection including hardcoded values, namespace issues, etc.
 */
class PackageBugHuntCommand extends Command
{
    protected $signature = 'packager:bug-hunt
                            {path? : Path to package (defaults to current directory)}
                            {--format=text : Output format (text, json, html)}
                            {--report= : Save report to file}';

    protected $description = 'Hunt for bugs, hardcoded values, and code issues';

    public function __construct(
        protected BugHunterService $bugHunter
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $path = $this->argument('path') ?: getcwd();
        $format = $this->option('format');
        $reportFile = $this->option('report');

        $this->info('🔍 Hunting for bugs...');
        $this->newLine();

        $results = $this->bugHunter->scanPackage($path);

        // Generate report
        $report = $this->bugHunter->generateReport($results, $format);

        // Save to file if requested
        if ($reportFile) {
            File::put($reportFile, $report);
            $this->info("Report saved to: {$reportFile}");
            $this->newLine();
        }

        // Display results
        if ($format === 'json') {
            $this->line($report);
        } elseif ($format === 'html') {
            if (! $reportFile) {
                $this->line($report);
            }
        } else {
            $this->displayTextResults($results);
        }

        return $results['status'] === 'clean' ? self::SUCCESS : self::FAILURE;
    }

    protected function displayTextResults(array $results): void
    {
        $this->info('Bug Hunter Results');
        $this->info('==================');
        $this->newLine();

        $this->line("Status: <fg={$this->getStatusColor($results['status'])}>{$results['status']}</>");
        $this->line("Total Issues: {$results['total_issues']}");
        $this->line("Scanned: {$results['scanned_at']}");
        $this->newLine();

        foreach ($results['results'] as $category => $issues) {
            $categoryName = $this->formatCategoryName($category);

            if (empty($issues)) {
                $this->line("<fg=green>✓ {$categoryName}: No issues</>");
            } else {
                $this->line("<fg=red>✗ {$categoryName}: ".count($issues).' issues</>');
                foreach ($issues as $issue) {
                    $this->line("  • {$issue}");
                }
            }
            $this->newLine();
        }

        if ($results['total_issues'] > 0) {
            $this->warn('💡 Tip: Review and fix the issues above for better code quality');
        } else {
            $this->info('🎉 Congratulations! No issues found!');
        }
    }

    protected function formatCategoryName(string $category): string
    {
        return ucwords(str_replace('_', ' ', $category));
    }

    protected function getStatusColor(string $status): string
    {
        return match ($status) {
            'clean' => 'green',
            'issues_found' => 'red',
            default => 'yellow',
        };
    }
}
