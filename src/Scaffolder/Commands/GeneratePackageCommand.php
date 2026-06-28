<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Simtabi\Laranail\Package\Tools\Support\PathResolver;
use Simtabi\Laranail\Package\Tools\Validation\ValidClassNameRule;
use Simtabi\Laranail\PackageScaffolder\Support\PlaceholderResolver;
use Simtabi\Laranail\PackageScaffolder\Support\StubGenerator;

/**
 * GeneratePackageCommand - Generate a new Laravel package from stubs
 *
 * Creates a complete package structure with all necessary files
 * from stub templates with placeholder replacement.
 */
class GeneratePackageCommand extends Command
{
    protected $signature = 'packager:generate
                            {vendor? : The package vendor name}
                            {package? : The package name}
                            {--i|interactive : Generate package interactively}
                            {--force : Overwrite existing package}
                            {--path= : Custom path for package generation}';

    protected $description = 'Generate a new Laravel package from stubs';

    protected string $vendor;

    protected string $package;

    protected array $additionalData = [];

    /**
     * Execute the console command
     */
    public function handle(): int
    {
        // Get vendor and package names
        if (! $this->gatherPackageInfo()) {
            return 1;
        }

        // Validate names
        if (! $this->validateNames()) {
            return 1;
        }

        // Check if package exists
        $packagePath = $this->getPackagePath();
        if (file_exists($packagePath) && ! $this->option('force')) {
            $this->error("Package already exists at: {$packagePath}");
            $this->info('Use --force to overwrite');

            return 1;
        }

        // Gather additional data if interactive
        if ($this->option('interactive')) {
            $this->gatherAdditionalData();
        }

        // Generate package
        $this->info('Generating package...');

        try {
            $this->generatePackage($packagePath);
            $this->info("✓ Package generated successfully at: {$packagePath}");
            $this->displayNextSteps();

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to generate package: {$e->getMessage()}");

            return 1;
        }
    }

    /**
     * Gather package information from arguments or prompts
     */
    protected function gatherPackageInfo(): bool
    {
        // Get vendor name
        $this->vendor = $this->argument('vendor') ?: $this->ask(
            'Vendor name?',
            config('scaffolder.default_author.name')
        );

        if (empty($this->vendor)) {
            $this->error('Vendor name is required');

            return false;
        }

        // Get package name
        $this->package = $this->argument('package') ?: $this->ask('Package name?');

        if (empty($this->package)) {
            $this->error('Package name is required');

            return false;
        }

        return true;
    }

    /**
     * Validate vendor and package names
     */
    protected function validateNames(): bool
    {
        $validator = Validator::make([
            'vendor' => $this->vendor,
            'package' => $this->package,
        ], [
            'vendor' => ['required', new ValidClassNameRule],
            'package' => ['required', new ValidClassNameRule],
        ]);

        if ($validator->fails()) {
            $this->error('Invalid vendor or package name:');
            foreach ($validator->errors()->all() as $error) {
                $this->error("  • {$error}");
            }

            return false;
        }

        return true;
    }

    /**
     * Gather additional data interactively
     */
    protected function gatherAdditionalData(): void
    {
        $this->info('Gathering additional information...');

        $this->additionalData = [
            'description' => $this->ask(
                'Package description?',
                config('scaffolder.defaults.description')
            ),
            'license' => $this->ask(
                'License?',
                config('scaffolder.defaults.license')
            ),
            'author' => [
                'name' => $this->ask(
                    'Author name?',
                    config('scaffolder.default_author.name')
                ),
                'email' => $this->ask(
                    'Author email?',
                    config('scaffolder.default_author.email')
                ),
                'homepage' => $this->ask(
                    'Author homepage?',
                    config('scaffolder.default_author.homepage')
                ),
            ],
            'route_prefix' => $this->ask(
                'Route prefix?',
                'packages'
            ),
            'singular_route_prefix' => $this->confirm(
                'Use singular route prefix?',
                false
            ),
        ];
    }

    /**
     * Generate the package
     */
    protected function generatePackage(string $packagePath): void
    {
        // Create placeholder resolver
        $resolver = PlaceholderResolver::make(
            $this->vendor,
            $this->package,
            array_merge(
                $this->additionalData,
                config('scaffolder.defaults', [])
            )
        );

        // Create stub generator
        $generator = StubGenerator::make(
            $this->getStubsPath(),
            $packagePath,
            $resolver
        );

        // Show progress
        $this->output->newLine();
        $this->info('Creating package structure...');

        // Parse blueprint to get structure and file mapping
        $blueprint = config('scaffolder.blueprint', []);
        [$directories, $fileMapping] = $this->parseBlueprint($blueprint);

        // Generate directory structure
        $createdDirs = $generator->generateDirectories($directories);
        $this->info('✓ Created '.count($createdDirs).' directories');

        // Generate files from stubs
        $this->info('Generating files from stubs...');
        $stats = $generator->generatePackage($directories, $fileMapping, $this->option('force'));

        // Display stats
        $this->displayGenerationStats($stats);

        // Handle errors
        if (! empty($stats['errors'])) {
            $this->warn('Some files could not be generated:');
            foreach ($stats['errors'] as $error) {
                $this->warn("  • {$error}");
            }
        }
    }

    /**
     * Display generation statistics
     */
    protected function displayGenerationStats(array $stats): void
    {
        $this->info("✓ Generated {$stats['files_generated']} files");

        if ($stats['files_skipped'] > 0) {
            $this->warn("⚠ Skipped {$stats['files_skipped']} existing files");
        }

        if (! empty($stats['errors'])) {
            $this->error('✗ {count: '.count($stats['errors']).'} errors occurred');
        }
    }

    /**
     * Get package path
     */
    protected function getPackagePath(): string
    {
        if ($customPath = $this->option('path')) {
            return PathResolver::toAbsolutePath($customPath);
        }

        $basePath = base_path(config('scaffolder.output_path'));
        $vendorKebab = Str::kebab($this->vendor);
        $packageKebab = Str::kebab($this->package);

        return PathResolver::joinPaths($basePath, $vendorKebab, $packageKebab);
    }

    /**
     * Get stubs path
     */
    protected function getStubsPath(): string
    {
        return config('scaffolder.stubs_path');
    }

    /**
     * Parse blueprint configuration into directories and file mapping
     *
     * @return array [$directories, $fileMapping]
     */
    protected function parseBlueprint(array $blueprint): array
    {
        $directories = [];
        $fileMapping = [];

        foreach ($blueprint as $category => $items) {
            // Skip if category is 'root', files go directly to package root
            $categoryPath = $category === 'root' ? '' : $category;

            foreach ($items as $path => $stub) {
                // Build full path
                $fullPath = $categoryPath ? "$categoryPath/$path" : $path;

                if ($stub === null) {
                    // Directory only (ends with / or is explicitly null)
                    $directories[] = rtrim($fullPath, '/');
                } else {
                    // File with stub
                    $fileMapping[$stub] = $fullPath;

                    // Also ensure parent directory exists
                    $parentDir = dirname($fullPath);
                    if ($parentDir !== '.' && $parentDir !== '') {
                        $directories[] = $parentDir;
                    }
                }
            }
        }

        // Remove duplicates and sort
        $directories = array_values(array_unique($directories));
        sort($directories);

        return [$directories, $fileMapping];
    }

    /**
     * Display next steps
     */
    protected function displayNextSteps(): void
    {
        $packagePath = $this->getPackagePath();
        $vendorKebab = Str::kebab($this->vendor);
        $packageKebab = Str::kebab($this->package);
        $composerName = "{$vendorKebab}/{$packageKebab}";

        $this->output->newLine();
        $this->info('Next steps:');
        $this->line("  1. Review generated files at: {$packagePath}");
        $this->line("  2. Install package: php artisan packager:install {$this->vendor} {$this->package}");
        $this->line('  3. Or manually add to composer.json:');
        $this->line('');
        $this->line('     "repositories": [');
        $this->line('         {');
        $this->line('             "type": "path",');
        $this->line("             \"url\": \"./{$packagePath}\",");
        $this->line('             "options": { "symlink": true }');
        $this->line('         }');
        $this->line('     ]');
        $this->line('');
        $this->line("  4. Then run: composer require {$composerName}:*@dev");
        $this->output->newLine();
    }
}
