<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Commands;

use Illuminate\Console\Command;
use Simtabi\Laranail\PackageScaffolder\Services\Development\TestPublisher;

/**
 * PackagePublishTestsCommand - Publish package tests
 *
 * Publishes package tests to application test directory
 */
class PackagePublishTestsCommand extends Command
{
    protected $signature = 'packager:publish-tests
                            {path? : Path to package (defaults to current directory)}
                            {--suite= : Test suite to publish (Unit, Feature, Integration)}
                            {--target= : Target directory (defaults to tests/packages)}';

    protected $description = 'Publish package tests to application test directory';

    public function __construct(
        protected TestPublisher $testPublisher
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $path = $this->argument('path') ?: getcwd();
        $suite = $this->option('suite');
        $target = $this->option('target') ?: 'tests/packages';

        $this->info('Publishing package tests...');
        $this->newLine();

        try {
            if ($suite) {
                $this->publishSuite($suite, $path);
            } else {
                $this->publishAll($path, $target);
            }

            $this->info('✓ Tests published successfully');
            $this->newLine();

            $published = $this->testPublisher->getPublished();
            $this->table(
                ['Source', 'Target'],
                collect($published)->map(fn ($target, $source) => [$source, $target])->toArray()
            );

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to publish tests: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    protected function publishSuite(string $suite, string $path): void
    {
        $this->line("Publishing {$suite} tests...");
        $this->testPublisher->publishSuite($suite, $path);
    }

    protected function publishAll(string $path, string $target): void
    {
        $this->line('Publishing all tests...');
        $targetPath = base_path($target);
        $this->testPublisher->publish($path, $targetPath);
    }
}
