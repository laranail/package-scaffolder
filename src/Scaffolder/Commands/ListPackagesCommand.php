<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * ListPackagesCommand - List all local packages
 *
 * Lists all locally installed packages with optional git status information.
 */
class ListPackagesCommand extends Command
{
    protected $signature = 'packager:list 
                            {--git : Show git status information}
                            {--path= : Packages directory path}';

    protected $description = 'List all locally installed packages';

    /**
     * Execute the console command
     */
    public function handle(): int
    {
        $packages = $this->discoverLocalPackages();

        if (empty($packages)) {
            $this->info('No local packages found.');

            return 0;
        }

        if ($this->option('git')) {
            $this->displayWithGitStatus($packages);
        } else {
            $this->displayBasic($packages);
        }

        return 0;
    }

    /**
     * Discover all local packages from composer.json
     */
    protected function discoverLocalPackages(): array
    {
        $composerPath = base_path('composer.json');

        if (! file_exists($composerPath)) {
            return [];
        }

        $composer = json_decode(file_get_contents($composerPath), true);
        $repositories = $composer['repositories'] ?? [];

        $packages = [];

        foreach ($repositories as $name => $info) {
            if (($info['type'] ?? null) !== 'path') {
                continue;
            }

            $url = $info['url'] ?? '';
            $path = base_path($url);

            // Parse vendor/package from name or path
            if (is_string($name) && str_contains($name, '/')) {
                [$vendor, $package] = explode('/', $name, 2);
            } else {
                // Try to extract from path
                $parts = explode('/', trim($url, './'));
                $package = array_pop($parts);
                $vendor = array_pop($parts) ?? 'unknown';
            }

            $packages[] = [
                'vendor' => $vendor,
                'package' => $package,
                'name' => "{$vendor}/{$package}",
                'path' => $path,
                'url' => $url,
            ];
        }

        return $packages;
    }

    /**
     * Display basic package list
     */
    protected function displayBasic(array $packages): void
    {
        $this->info('Local Packages:');
        $this->output->newLine();

        $rows = array_map(function ($package) {
            return [
                $package['vendor'],
                $package['package'],
                $package['url'],
            ];
        }, $packages);

        $this->table(['Vendor', 'Package', 'Path'], $rows);
        $this->info('Total: '.count($packages).' packages');
    }

    /**
     * Display package list with git status
     */
    protected function displayWithGitStatus(array $packages): void
    {
        $this->info('Local Packages (with git status):');
        $this->output->newLine();

        $rows = [];

        foreach ($packages as $package) {
            $gitInfo = $this->getGitInfo($package['path']);

            $rows[] = [
                $package['vendor'],
                $package['package'],
                $package['url'],
                $gitInfo['branch'],
                $gitInfo['behind'],
                $gitInfo['ahead'],
                $gitInfo['status'],
            ];
        }

        $this->table(
            ['Vendor', 'Package', 'Path', 'Branch', 'Behind', 'Ahead', 'Status'],
            $rows
        );

        $this->info('Total: '.count($packages).' packages');
    }

    /**
     * Get git information for a package
     */
    protected function getGitInfo(string $path): array
    {
        if (! is_dir($path.'/.git')) {
            return [
                'branch' => '-',
                'behind' => '-',
                'ahead' => '-',
                'status' => '-',
            ];
        }

        return [
            'branch' => $this->getCurrentBranch($path),
            'behind' => $this->getCommitsBehind($path),
            'ahead' => $this->getCommitsAhead($path),
            'status' => $this->getWorkingTreeStatus($path),
        ];
    }

    /**
     * Get current git branch
     */
    protected function getCurrentBranch(string $path): string
    {
        $process = new Process(['git', 'branch', '--show-current'], $path);
        $process->run();

        if ($process->isSuccessful()) {
            return trim($process->getOutput()) ?: 'detached';
        }

        return 'unknown';
    }

    /**
     * Get commits behind origin
     */
    protected function getCommitsBehind(string $path): string
    {
        // Fetch first
        $fetchProcess = new Process(['git', 'fetch'], $path);
        $fetchProcess->run();

        $process = new Process(['git', 'rev-list', 'HEAD..origin', '--count'], $path);
        $process->run();

        if ($process->isSuccessful()) {
            $count = (int) trim($process->getOutput());

            return $count > 0 ? (string) $count : '0';
        }

        return '-';
    }

    /**
     * Get commits ahead of origin
     */
    protected function getCommitsAhead(string $path): string
    {
        $process = new Process(['git', 'rev-list', 'origin..HEAD', '--count'], $path);
        $process->run();

        if ($process->isSuccessful()) {
            $count = (int) trim($process->getOutput());

            return $count > 0 ? (string) $count : '0';
        }

        return '-';
    }

    /**
     * Get working tree status
     */
    protected function getWorkingTreeStatus(string $path): string
    {
        $process = new Process(['git', 'status', '--porcelain'], $path);
        $process->run();

        if ($process->isSuccessful()) {
            $output = trim($process->getOutput());

            return empty($output) ? 'clean' : 'modified';
        }

        return 'unknown';
    }
}
