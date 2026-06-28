<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Simtabi\Laranail\Package\Tools\Concerns\Package\ManagesComposer;
use Symfony\Component\Process\Process;

/**
 * PackageStatusCommand - Show package status and information
 *
 * Displays detailed information about a package including enabled/disabled state,
 * version, path, and Git status.
 */
class PackageStatusCommand extends Command
{
    use ManagesComposer;

    protected $signature = 'packager:status
                            {vendor : The package vendor name}
                            {package : The package name}
                            {--git : Show git status information}
                            {--json : Output as JSON}';

    protected $description = 'Show package status and information';

    /**
     * Execute the console command
     */
    public function handle(): int
    {
        $vendor = $this->argument('vendor');
        $package = $this->argument('package');
        $showGit = $this->option('git');
        $asJson = $this->option('json');

        $vendorKebab = Str::kebab($vendor);
        $packageKebab = Str::kebab($package);
        $composerName = "{$vendorKebab}/{$packageKebab}";

        // Gather package information
        $info = $this->gatherPackageInfo($vendor, $package, $showGit);

        if ($info['status'] === 'not_found') {
            if ($asJson) {
                $this->line(json_encode($info, JSON_PRETTY_PRINT));
            } else {
                $this->error("Package '{$composerName}' not found");
            }

            return 1;
        }

        // Output as JSON or formatted
        if ($asJson) {
            $this->line(json_encode($info, JSON_PRETTY_PRINT));
        } else {
            $this->displayPackageInfo($info);
        }

        return 0;
    }

    /**
     * Gather comprehensive package information
     */
    protected function gatherPackageInfo(string $vendor, string $package, bool $includeGit = false): array
    {
        $vendorKebab = Str::kebab($vendor);
        $packageKebab = Str::kebab($package);
        $composerName = "{$vendorKebab}/{$packageKebab}";

        // Check package status
        $status = $this->getPackageStatus($vendor, $package);

        $info = [
            'name' => $composerName,
            'vendor' => $vendorKebab,
            'package' => $packageKebab,
            'status' => $status,
        ];

        if ($status === 'not_found') {
            return $info;
        }

        // Get composer.json info
        $composerPath = base_path('composer.json');
        if (File::exists($composerPath)) {
            $composer = json_decode(File::get($composerPath), true);

            if (isset($composer['require'][$composerName])) {
                $info['version'] = $composer['require'][$composerName];
            }

            // Check if it's a path repository
            if (isset($composer['repositories'])) {
                foreach ($composer['repositories'] as $repo) {
                    if (isset($repo['type'], $repo['url']) && $repo['type'] === 'path') {
                        $repoPath = base_path($repo['url']);
                        if (File::exists($repoPath.'/composer.json')) {
                            $pkgComposer = json_decode(File::get($repoPath.'/composer.json'), true);
                            if (isset($pkgComposer['name']) && $pkgComposer['name'] === $composerName) {
                                $info['type'] = 'local';
                                $info['path'] = $repo['url'];
                                $info['absolute_path'] = $repoPath;
                                break;
                            }
                        }
                    }
                }
            }
        }

        // Set default type if not found
        $info['type'] = $info['type'] ?? 'packagist';

        // Get package path in vendor
        $vendorPath = base_path("vendor/{$composerName}");
        if (File::isDirectory($vendorPath)) {
            $info['installed'] = true;
            $info['vendor_path'] = "vendor/{$composerName}";

            // Check for service provider
            if (File::exists($vendorPath.'/composer.json')) {
                $pkgComposer = json_decode(File::get($vendorPath.'/composer.json'), true);

                if (isset($pkgComposer['extra']['laravel']['providers'])) {
                    $info['providers'] = $pkgComposer['extra']['laravel']['providers'];
                }

                if (isset($pkgComposer['description'])) {
                    $info['description'] = $pkgComposer['description'];
                }
            }
        } else {
            $info['installed'] = false;
        }

        // Get Git information if requested and package is local
        if ($includeGit && isset($info['absolute_path']) && File::isDirectory($info['absolute_path'].'/.git')) {
            $info['git'] = $this->getGitInfo($info['absolute_path']);
        }

        return $info;
    }

    /**
     * Get Git information for a package
     */
    protected function getGitInfo(string $path): array
    {
        $gitInfo = [];

        // Get current branch
        $process = new Process(['git', 'branch', '--show-current'], $path);
        $process->run();
        if ($process->isSuccessful()) {
            $gitInfo['branch'] = trim($process->getOutput());
        }

        // Get latest commit
        $process = new Process(['git', 'log', '-1', '--pretty=%h - %s (%ar)'], $path);
        $process->run();
        if ($process->isSuccessful()) {
            $gitInfo['latest_commit'] = trim($process->getOutput());
        }

        // Check if there are uncommitted changes
        $process = new Process(['git', 'status', '--porcelain'], $path);
        $process->run();
        if ($process->isSuccessful()) {
            $output = trim($process->getOutput());
            $gitInfo['has_changes'] = ! empty($output);
            $gitInfo['changes_count'] = empty($output) ? 0 : count(explode("\n", $output));
        }

        // Check commits behind/ahead of remote
        $process = new Process(['git', 'rev-list', '--left-right', '--count', 'HEAD...@{u}'], $path);
        $process->run();
        if ($process->isSuccessful()) {
            $output = trim($process->getOutput());
            if (preg_match('/(\d+)\s+(\d+)/', $output, $matches)) {
                $gitInfo['ahead'] = (int) $matches[1];
                $gitInfo['behind'] = (int) $matches[2];
            }
        }

        return $gitInfo;
    }

    /**
     * Display package information in formatted output
     */
    protected function displayPackageInfo(array $info): void
    {
        $this->output->newLine();
        $this->line('╔══════════════════════════════════════════════════════════════╗');
        $this->line('║                     PACKAGE STATUS                           ║');
        $this->line('╚══════════════════════════════════════════════════════════════╝');
        $this->output->newLine();

        // Basic info
        $this->line("<fg=cyan>Package:</> {$info['name']}");

        $statusColor = $info['status'] === 'enabled' ? 'green' : 'yellow';
        $statusText = ucfirst($info['status']);
        $this->line("<fg=cyan>Status:</> <fg={$statusColor}>{$statusText}</>");

        if (isset($info['version'])) {
            $this->line("<fg=cyan>Version:</> {$info['version']}");
        }

        if (isset($info['type'])) {
            $this->line("<fg=cyan>Type:</> {$info['type']}");
        }

        if (isset($info['description'])) {
            $this->line("<fg=cyan>Description:</> {$info['description']}");
        }

        // Installation info
        $this->output->newLine();
        $installedText = isset($info['installed']) && $info['installed'] ? 'Yes' : 'No';
        $installedColor = isset($info['installed']) && $info['installed'] ? 'green' : 'red';
        $this->line("<fg=cyan>Installed:</> <fg={$installedColor}>{$installedText}</>");

        if (isset($info['vendor_path'])) {
            $this->line("<fg=cyan>Vendor Path:</> {$info['vendor_path']}");
        }

        if (isset($info['path'])) {
            $this->line("<fg=cyan>Local Path:</> {$info['path']}");
        }

        // Service providers
        if (isset($info['providers']) && ! empty($info['providers'])) {
            $this->output->newLine();
            $this->line('<fg=cyan>Service Providers:</>');
            foreach ($info['providers'] as $provider) {
                $this->line("  • {$provider}");
            }
        }

        // Git info
        if (isset($info['git'])) {
            $git = $info['git'];
            $this->output->newLine();
            $this->line('<fg=cyan>Git Information:</>');

            if (isset($git['branch'])) {
                $this->line("  Branch: {$git['branch']}");
            }

            if (isset($git['latest_commit'])) {
                $this->line("  Latest: {$git['latest_commit']}");
            }

            if (isset($git['has_changes'])) {
                $changesText = $git['has_changes']
                    ? "<fg=yellow>Yes ({$git['changes_count']} files)</>"
                    : '<fg=green>No</>';
                $this->line("  Uncommitted: {$changesText}");
            }

            if (isset($git['ahead'], $git['behind'])) {
                if ($git['ahead'] > 0 || $git['behind'] > 0) {
                    $this->line("  Sync: <fg=yellow>{$git['ahead']} ahead, {$git['behind']} behind</>");
                } else {
                    $this->line('  Sync: <fg=green>Up to date</>');
                }
            }
        }

        $this->output->newLine();
    }
}
