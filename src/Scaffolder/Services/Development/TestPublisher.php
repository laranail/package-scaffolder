<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Services\Development;

use Illuminate\Support\Facades\File;
use Simtabi\Laranail\Package\Tools\Support\PathResolver;

/**
 * TestPublisher - Test file publishing
 *
 * Publishes package tests to main application test directory
 */
class TestPublisher
{
    protected array $published = [];

    /**
     * Publish all tests from package to application
     *
     * @param  string  $packagePath  Package root path
     * @param  string  $targetPath  Target test directory path
     */
    public function publish(string $packagePath, string $targetPath): void
    {
        $testSource = PathResolver::joinPaths($packagePath, 'tests');

        if (! File::isDirectory($testSource)) {
            return;
        }

        $this->copyTests($testSource, $targetPath);
    }

    /**
     * Publish specific test suite
     *
     * @param  string  $suite  Suite name (Unit, Feature, Integration)
     * @param  string  $packagePath  Package root path
     */
    public function publishSuite(string $suite, string $packagePath): void
    {
        $testSource = PathResolver::joinPaths($packagePath, 'tests', $suite);
        $targetPath = base_path("tests/packages/{$suite}");

        if (! File::isDirectory($testSource)) {
            return;
        }

        $this->copyTests($testSource, $targetPath);
    }

    /**
     * Copy test files from source to target
     *
     * @param  string  $source  Source directory
     * @param  string  $target  Target directory
     */
    public function copyTests(string $source, string $target): void
    {
        if (! File::isDirectory($source)) {
            return;
        }

        // Create target directory if it doesn't exist
        if (! File::isDirectory($target)) {
            File::makeDirectory($target, 0755, true);
        }

        // Copy all test files
        $files = File::allFiles($source);

        foreach ($files as $file) {
            $relativePath = $file->getRelativePathname();
            $targetFile = PathResolver::joinPaths($target, $relativePath);

            // Create subdirectories if needed
            $targetDir = dirname($targetFile);
            if (! File::isDirectory($targetDir)) {
                File::makeDirectory($targetDir, 0755, true);
            }

            // Copy file
            File::copy($file->getPathname(), $targetFile);
            $this->published[] = $targetFile;
        }
    }

    /**
     * Update PHPUnit configuration to include package tests
     *
     * @param  string  $suite  Suite name
     */
    public function updatePhpUnitConfig(string $suite): void
    {
        $phpunitPath = base_path('phpunit.xml');

        if (! File::exists($phpunitPath)) {
            return;
        }

        // This would parse phpunit.xml and add the testsuite
        // Implementation depends on XML parsing requirements
    }

    /**
     * Get list of published test files
     *
     * @return array<string>
     */
    public function getPublished(): array
    {
        return $this->published;
    }
}
