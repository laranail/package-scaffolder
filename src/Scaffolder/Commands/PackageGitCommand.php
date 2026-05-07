<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Commands;

use Illuminate\Console\Command;
use Simtabi\Laranail\PackageScaffolder\Services\Development\GitService;

/**
 * PackageGitCommand - Git operations for packages
 *
 * Provides Git operations for package development
 */
class PackageGitCommand extends Command
{
    protected $signature = 'packager:git
                            {action : Git action (status, commit, push, tag)}
                            {path? : Path to package (defaults to current directory)}
                            {--message= : Commit message}
                            {--version= : Tag version}
                            {--remote=origin : Remote name}
                            {--branch=main : Branch name}';

    protected $description = 'Execute Git operations for packages';

    public function __construct(
        protected GitService $gitService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $action = $this->argument('action');
        $path = $this->argument('path') ?: getcwd();

        return match ($action) {
            'status' => $this->showStatus($path),
            'commit' => $this->commit($path),
            'push' => $this->push($path),
            'tag' => $this->createTag($path),
            default => $this->invalidAction($action),
        };
    }

    protected function showStatus(string $path): int
    {
        $status = $this->gitService->status($path);

        $this->info('Git Status');
        $this->info('==========');
        $this->newLine();

        $this->line("Branch: <fg=cyan>{$status['branch']}</>");
        $this->line("Ahead: {$status['ahead']} commits");
        $this->line("Behind: {$status['behind']} commits");
        $this->line('Working Directory: '.($status['dirty'] ? '<fg=red>Dirty</>' : '<fg=green>Clean</>'));

        return self::SUCCESS;
    }

    protected function commit(string $path): int
    {
        $message = $this->option('message');

        if (! $message) {
            $message = $this->ask('Commit message');
        }

        if (! $message) {
            $this->error('Commit message is required');

            return self::FAILURE;
        }

        $this->info('Committing changes...');
        $this->gitService->commit($path, $message);
        $this->info('✓ Changes committed');

        return self::SUCCESS;
    }

    protected function push(string $path): int
    {
        $remote = $this->option('remote');
        $branch = $this->option('branch');

        $this->info("Pushing to {$remote}/{$branch}...");
        $this->gitService->push($path, $remote, $branch);
        $this->info('✓ Changes pushed');

        return self::SUCCESS;
    }

    protected function createTag(string $path): int
    {
        $version = $this->option('version');

        if (! $version) {
            $version = $this->ask('Tag version (e.g., v1.0.0)');
        }

        if (! $version) {
            $this->error('Version is required');

            return self::FAILURE;
        }

        $this->info("Creating tag {$version}...");
        $this->gitService->tag($path, $version);
        $this->info('✓ Tag created and pushed');

        return self::SUCCESS;
    }

    protected function invalidAction(string $action): int
    {
        $this->error("Invalid action: {$action}");
        $this->line('Available actions: status, commit, push, tag');

        return self::FAILURE;
    }
}
