<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Simtabi\Laranail\PackageScaffolder\Contracts\ServiceInterface;

/**
 * StubService - Handles loading and processing of stub templates
 *
 * Manages stub files, resolves stub paths, and loads stub content
 */
class StubService implements ServiceInterface
{
    protected array $stubPaths = [];

    public function __construct()
    {
        $this->registerDefaultStubPaths();
    }

    /**
     * Register stub path
     *
     * @param  string  $path  Path to stub directory
     */
    public function registerPath(string $path): self
    {
        if (File::isDirectory($path)) {
            $this->stubPaths[] = $path;
        }

        return $this;
    }

    /**
     * Load stub content
     *
     * @param  string  $stub  Stub name (e.g., 'composer.json', 'src/Providers/PackageServiceProvider')
     * @return string|null Stub content or null if not found
     */
    public function load(string $stub): ?string
    {
        $stubFile = $this->resolveStubPath($stub);

        if (! $stubFile || ! File::exists($stubFile)) {
            return null;
        }

        return File::get($stubFile);
    }

    /**
     * Check if stub exists
     *
     * @param  string  $stub  Stub name
     */
    public function exists(string $stub): bool
    {
        return $this->resolveStubPath($stub) !== null;
    }

    /**
     * Get all available stubs
     *
     * @return array<string> List of stub names
     */
    public function getAllStubs(): array
    {
        $stubs = [];

        foreach ($this->stubPaths as $path) {
            $files = File::allFiles($path);

            foreach ($files as $file) {
                if (Str::endsWith($file->getFilename(), '.stub')) {
                    $relativePath = Str::after($file->getPathname(), $path.'/');
                    $stubs[] = Str::replaceLast('.stub', '', $relativePath);
                }
            }
        }

        return array_unique($stubs);
    }

    /**
     * Resolve stub file path
     *
     * @param  string  $stub  Stub name
     * @return string|null Full path to stub file or null
     */
    protected function resolveStubPath(string $stub): ?string
    {
        // Ensure .stub extension
        $stubFile = Str::endsWith($stub, '.stub') ? $stub : "{$stub}.stub";

        foreach ($this->stubPaths as $path) {
            $fullPath = $path.'/'.$stubFile;

            if (File::exists($fullPath)) {
                return $fullPath;
            }
        }

        return null;
    }

    /**
     * Register default stub paths
     */
    protected function registerDefaultStubPaths(): void
    {
        // 1. Custom stubs path from config (highest priority)
        $customPath = config('generator.stubs_path');
        if ($customPath && File::isDirectory($customPath)) {
            $this->registerPath($customPath);
        }

        // 2. Package stubs directory (main stubs location)
        $packageStubs = __DIR__.'/../../../stubs';
        if (File::isDirectory($packageStubs)) {
            $this->registerPath($packageStubs);
        }

        // 3. Published stubs in application
        $publishedStubs = resource_path('stubs/package-scaffolder');
        if (File::isDirectory($publishedStubs)) {
            $this->registerPath($publishedStubs);
        }

        // 4. Vendor stubs (for installed packages)
        $vendorStubs = base_path('vendor/laranail/package-scaffolder/stubs');
        if (File::isDirectory($vendorStubs)) {
            $this->registerPath($vendorStubs);
        }
    }

    /**
     * Validate stub content
     *
     * @param  string  $stub  Stub name
     */
    public function validate(string $stub): bool
    {
        $content = $this->load($stub);

        if (! $content) {
            return false;
        }

        // Check if stub has proper structure
        return ! empty($content);
    }
}
