<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Support;

use Illuminate\Support\Facades\File;
use Simtabi\Laranail\PackageTools\Exceptions\InvalidPath;
use Simtabi\Laranail\PackageTools\Support\PathResolver;

/**
 * StubGenerator - Generates files from stub templates
 *
 * Handles stub file discovery, processing, and generation with placeholder replacement.
 */
class StubGenerator
{
    protected string $stubsPath;

    protected string $destinationPath;

    protected PlaceholderResolver $resolver;

    protected array $generatedFiles = [];

    public function __construct(
        string $stubsPath,
        string $destinationPath,
        PlaceholderResolver $resolver
    ) {
        $this->stubsPath = $stubsPath;
        $this->destinationPath = $destinationPath;
        $this->resolver = $resolver;
    }

    /**
     * Generate a single file from stub
     *
     * @param  string  $stubPath  Relative path to stub file (from stubs directory)
     * @param  string  $destinationPath  Relative path for generated file (from destination)
     * @param  bool  $overwrite  Whether to overwrite existing files
     * @return bool Success status
     *
     * @throws InvalidPath
     */
    public function generateFile(
        string $stubPath,
        string $destinationPath,
        bool $overwrite = false
    ): bool {
        $fullStubPath = PathResolver::joinPaths($this->stubsPath, $stubPath);
        $fullDestinationPath = PathResolver::joinPaths($this->destinationPath, $destinationPath);

        // Validate stub exists
        if (! File::exists($fullStubPath)) {
            throw InvalidPath::custom("Stub file not found: {$stubPath}", $fullStubPath);
        }

        // Check if destination exists and overwrite is false
        if (! $overwrite && File::exists($fullDestinationPath)) {
            return false;
        }

        // Read stub content
        $content = File::get($fullStubPath);

        // Replace placeholders
        $content = $this->resolver->replace($content);

        // Ensure destination directory exists
        $directory = dirname($fullDestinationPath);
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Write file
        $success = File::put($fullDestinationPath, $content) !== false;

        if ($success) {
            $this->generatedFiles[] = $fullDestinationPath;
        }

        return $success;
    }

    /**
     * Generate multiple files from stub mapping
     *
     * @param  array  $mapping  Stub path => Destination path mapping
     * @param  bool  $overwrite  Whether to overwrite existing files
     * @return array Generated file paths
     */
    public function generateFiles(array $mapping, bool $overwrite = false): array
    {
        $generated = [];

        foreach ($mapping as $stubPath => $destinationPath) {
            // Apply placeholder replacement to destination path as well
            $destinationPath = $this->resolver->replace($destinationPath);

            if ($this->generateFile($stubPath, $destinationPath, $overwrite)) {
                $generated[] = PathResolver::joinPaths($this->destinationPath, $destinationPath);
            }
        }

        return $generated;
    }

    /**
     * Generate directory structure
     *
     * @param  array  $directories  List of directory paths to create
     * @return array Created directory paths
     */
    public function generateDirectories(array $directories): array
    {
        $created = [];

        foreach ($directories as $directory) {
            // Apply placeholder replacement
            $directory = $this->resolver->replace($directory);

            $fullPath = PathResolver::joinPaths($this->destinationPath, $directory);

            if (! File::isDirectory($fullPath)) {
                File::makeDirectory($fullPath, 0755, true);
                $created[] = $fullPath;
            }
        }

        return $created;
    }

    /**
     * Discover all stub files recursively
     *
     * @return array List of stub files relative to stubs path
     */
    public function discoverStubs(): array
    {
        if (! File::isDirectory($this->stubsPath)) {
            return [];
        }

        $stubs = [];
        $files = File::allFiles($this->stubsPath);

        foreach ($files as $file) {
            $relativePath = $file->getRelativePathname();
            $stubs[] = $relativePath;
        }

        return $stubs;
    }

    /**
     * Generate entire package structure from stubs
     *
     * @param  array  $structure  Directory structure
     * @param  array  $fileMapping  Stub to destination file mapping
     * @param  bool  $overwrite  Whether to overwrite existing files
     * @return array Statistics about generation
     */
    public function generatePackage(
        array $structure,
        array $fileMapping,
        bool $overwrite = false
    ): array {
        $stats = [
            'directories_created' => 0,
            'files_generated' => 0,
            'files_skipped' => 0,
            'errors' => [],
        ];

        // Create directory structure
        try {
            $created = $this->generateDirectories($structure);
            $stats['directories_created'] = count($created);
        } catch (\Exception $e) {
            $stats['errors'][] = "Directory creation failed: {$e->getMessage()}";
        }

        // Generate files
        foreach ($fileMapping as $stubPath => $destinationPath) {
            try {
                // Apply placeholder replacement to destination
                $resolvedDestination = $this->resolver->replace($destinationPath);

                if ($this->generateFile($stubPath, $resolvedDestination, $overwrite)) {
                    $stats['files_generated']++;
                } else {
                    $stats['files_skipped']++;
                }
            } catch (\Exception $e) {
                $stats['errors'][] = "Failed to generate {$destinationPath}: {$e->getMessage()}";
                $stats['files_skipped']++;
            }
        }

        return $stats;
    }

    /**
     * Get list of generated files
     */
    public function getGeneratedFiles(): array
    {
        return $this->generatedFiles;
    }

    /**
     * Clear generated files list
     */
    public function clearGeneratedFiles(): void
    {
        $this->generatedFiles = [];
    }

    /**
     * Validate stub path exists
     */
    public function stubExists(string $stubPath): bool
    {
        $fullPath = PathResolver::joinPaths($this->stubsPath, $stubPath);

        return File::exists($fullPath);
    }

    /**
     * Get full stub path
     */
    public function getFullStubPath(string $stubPath): string
    {
        return PathResolver::joinPaths($this->stubsPath, $stubPath);
    }

    /**
     * Get full destination path
     */
    public function getFullDestinationPath(string $destinationPath): string
    {
        return PathResolver::joinPaths($this->destinationPath, $destinationPath);
    }

    /**
     * Static factory method
     */
    public static function make(
        string $stubsPath,
        string $destinationPath,
        PlaceholderResolver $resolver
    ): static {
        return new static($stubsPath, $destinationPath, $resolver);
    }
}
