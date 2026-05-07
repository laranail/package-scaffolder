<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Services\Development;

use Symfony\Component\Process\Process;

/**
 * GitService - Git repository operations
 *
 * Handles Git operations for packages
 */
class GitService
{
    /**
     * Get Git status for a path
     *
     * @param  string  $path  Repository path
     * @return array{branch: string, ahead: int, behind: int, dirty: bool}
     */
    public function status(string $path): array
    {
        if (! $this->isGitRepository($path)) {
            return [
                'branch' => '',
                'ahead' => 0,
                'behind' => 0,
                'dirty' => false,
            ];
        }

        return [
            'branch' => $this->getCurrentBranch($path),
            'ahead' => $this->getAheadCount($path),
            'behind' => $this->getBehindCount($path),
            'dirty' => ! $this->isClean($path),
        ];
    }

    /**
     * Commit changes
     *
     * @param  string  $path  Repository path
     * @param  string  $message  Commit message
     */
    public function commit(string $path, string $message): void
    {
        $process = new Process(['git', 'commit', '-m', $message], $path);
        $process->run();
    }

    /**
     * Push to remote
     *
     * @param  string  $path  Repository path
     * @param  string  $remote  Remote name
     * @param  string  $branch  Branch name
     */
    public function push(string $path, string $remote = 'origin', string $branch = 'main'): void
    {
        $process = new Process(['git', 'push', $remote, $branch], $path);
        $process->run();
    }

    /**
     * Create and push tag
     *
     * @param  string  $path  Repository path
     * @param  string  $version  Tag version
     */
    public function tag(string $path, string $version): void
    {
        // Create tag
        $createProcess = new Process(['git', 'tag', $version], $path);
        $createProcess->run();

        // Push tag
        $pushProcess = new Process(['git', 'push', 'origin', $version], $path);
        $pushProcess->run();
    }

    /**
     * Check if working directory is clean
     *
     * @param  string  $path  Repository path
     */
    public function isClean(string $path): bool
    {
        $process = new Process(['git', 'status', '--porcelain'], $path);
        $process->run();

        return empty(trim($process->getOutput()));
    }

    /**
     * Check if path is a Git repository
     *
     * @param  string  $path  Path to check
     */
    protected function isGitRepository(string $path): bool
    {
        return is_dir($path.'/.git');
    }

    /**
     * Get current branch name
     *
     * @param  string  $path  Repository path
     */
    protected function getCurrentBranch(string $path): string
    {
        $process = new Process(['git', 'rev-parse', '--abbrev-ref', 'HEAD'], $path);
        $process->run();

        return trim($process->getOutput());
    }

    /**
     * Get commits ahead of remote
     *
     * @param  string  $path  Repository path
     */
    protected function getAheadCount(string $path): int
    {
        $process = new Process(['git', 'rev-list', '--count', 'HEAD', '@{u}..HEAD'], $path);
        $process->run();

        return (int) trim($process->getOutput());
    }

    /**
     * Get commits behind remote
     *
     * @param  string  $path  Repository path
     */
    protected function getBehindCount(string $path): int
    {
        $process = new Process(['git', 'rev-list', '--count', 'HEAD..@{u}'], $path);
        $process->run();

        return (int) trim($process->getOutput());
    }
}
