<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Concerns\Package;

use Simtabi\Laranail\PackageScaffolder\Services\Development\GitService;

/**
 * HasGitOperations - Git repository operations
 *
 * Enables Git operations for package development
 */
trait HasGitOperations
{
    protected ?GitService $gitOperationsService = null;

    /**
     * Get Git repository status
     *
     * @return array{branch: string, ahead: int, behind: int, dirty: bool}
     */
    public function getGitStatus(): array
    {
        $git = $this->getGitOperationsService();

        $packagePath = $this->packageBasePath();

        return $git->status($packagePath);
    }

    /**
     * Commit changes
     *
     * @param  string  $message  Commit message
     */
    public function gitCommit(string $message): static
    {
        $git = $this->getGitOperationsService();

        $packagePath = $this->packageBasePath();
        $git->commit($packagePath, $message);

        return $this;
    }

    /**
     * Push changes to remote
     *
     * @param  string  $remote  Remote name
     * @param  string  $branch  Branch name
     */
    public function gitPush(string $remote = 'origin', string $branch = 'main'): static
    {
        $git = $this->getGitOperationsService();

        $packagePath = $this->packageBasePath();
        $git->push($packagePath, $remote, $branch);

        return $this;
    }

    /**
     * Create and push Git tag
     *
     * @param  string  $version  Tag version
     */
    public function gitTag(string $version): static
    {
        $git = $this->getGitOperationsService();

        $packagePath = $this->packageBasePath();
        $git->tag($packagePath, $version);

        return $this;
    }

    /**
     * Check if working directory is clean
     */
    public function isGitClean(): bool
    {
        $git = $this->getGitOperationsService();

        $packagePath = $this->packageBasePath();

        return $git->isClean($packagePath);
    }

    /**
     * Display Git status
     */
    public function displayGitStatus(): static
    {
        $status = $this->getGitStatus();

        echo "Git Status:\n";
        echo "  Branch: {$status['branch']}\n";
        echo "  Ahead: {$status['ahead']} commits\n";
        echo "  Behind: {$status['behind']} commits\n";
        echo '  Clean: '.($status['dirty'] ? 'No' : 'Yes')."\n";

        return $this;
    }

    /**
     * Get or create Git operations service instance
     */
    protected function getGitOperationsService(): GitService
    {
        if (! $this->gitOperationsService) {
            $this->gitOperationsService = app(GitService::class);
        }

        return $this->gitOperationsService;
    }

    /**
     * Get package base path
     *
     * @param  string  $path  Optional path to append
     */
    abstract protected function packageBasePath(string $path = ''): string;
}
