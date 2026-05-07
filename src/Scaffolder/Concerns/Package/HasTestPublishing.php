<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Concerns\Package;

use Simtabi\Laranail\PackageScaffolder\Services\Development\TestPublisher;

/**
 * HasTestPublishing - Test publishing support
 *
 * Enables publishing package tests to application test directory
 */
trait HasTestPublishing
{
    protected ?TestPublisher $testPublisherService = null;

    /**
     * Publish all package tests
     *
     * @param  string  $targetDir  Target test directory
     */
    public function publishTests(string $targetDir = 'tests/packages'): static
    {
        $publisher = $this->getTestPublisherService();

        $packagePath = $this->packageBasePath();
        $targetPath = base_path($targetDir.'/'.$this->shortName());

        $publisher->publish($packagePath, $targetPath);

        return $this;
    }

    /**
     * Publish specific test suite
     *
     * @param  string  $suite  Suite name (Unit, Feature, Integration)
     */
    public function publishTestSuite(string $suite): static
    {
        $publisher = $this->getTestPublisherService();

        $packagePath = $this->packageBasePath();
        $publisher->publishSuite($suite, $packagePath);

        return $this;
    }

    /**
     * Publish unit tests only
     */
    public function publishUnitTests(): static
    {
        return $this->publishTestSuite('Unit');
    }

    /**
     * Publish feature tests only
     */
    public function publishFeatureTests(): static
    {
        return $this->publishTestSuite('Feature');
    }

    /**
     * Publish integration tests only
     */
    public function publishIntegrationTests(): static
    {
        return $this->publishTestSuite('Integration');
    }

    /**
     * Make tests publishable
     */
    public function makeTestsPublishable(): static
    {
        $this->publishes([
            $this->packageBasePath('tests') => base_path('tests/packages/'.$this->shortName()),
        ], $this->shortName().'-tests');

        return $this;
    }

    /**
     * Get or create test publisher service instance
     */
    protected function getTestPublisherService(): TestPublisher
    {
        if (! $this->testPublisherService) {
            $this->testPublisherService = app(TestPublisher::class);
        }

        return $this->testPublisherService;
    }

    /**
     * Get package base path
     *
     * @param  string  $path  Optional path to append
     */
    abstract protected function packageBasePath(string $path = ''): string;

    /**
     * Get package short name
     */
    abstract protected function shortName(): string;

    /**
     * Publishes (from Package class)
     */
    abstract protected function publishes(array $paths, string $tag): void;
}
