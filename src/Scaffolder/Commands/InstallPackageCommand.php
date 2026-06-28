<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Simtabi\Laranail\Package\Tools\Concerns\Package\ManagesComposer;
use Simtabi\Laranail\Package\Tools\Support\PathResolver;

/**
 * InstallPackageCommand - Install a local package via composer
 *
 * Adds the package as a path repository and requires it via composer.
 */
class InstallPackageCommand extends Command
{
    use ManagesComposer;

    protected $signature = 'packager:install
                            {vendor : The package vendor name}
                            {package : The package name}
                            {--path= : Custom package path}
                            {--version=*@dev : Package version constraint}
                            {--no-symlink : Disable symlink option}';

    protected $description = 'Install a local package via composer';

    /**
     * Execute the console command
     */
    public function handle(): int
    {
        $vendor = $this->argument('vendor');
        $package = $this->argument('package');
        $version = $this->option('version');
        $useSymlink = ! $this->option('no-symlink');

        // Determine package path
        $packagePath = $this->getPackagePath($vendor, $package);

        // Validate package exists
        if (! is_dir($packagePath)) {
            $this->error("Package not found at: {$packagePath}");

            return 1;
        }

        $this->info("Installing package: {$vendor}/{$package}");
        $this->info("From path: {$packagePath}");

        // Add repository
        $this->line('Adding composer repository...');
        if (! $this->addComposerRepository($vendor, $package, $packagePath, $useSymlink)) {
            $this->error('Failed to add composer repository');

            return 1;
        }
        $this->info('✓ Repository added');

        // Require package
        $this->line('Requiring package via composer...');
        $this->withComposerErrors(true); // Show errors

        if (! $this->composerRequire($vendor, $package, $version)) {
            $this->error('Failed to require package');
            $this->warn('Rolling back repository addition...');
            $this->removeComposerRepository($vendor, $package);

            return 1;
        }

        $this->info('✓ Package required');

        // Success
        $this->output->newLine();
        $this->info('✓ Package installed successfully!');
        $this->displayUsageInstructions($vendor, $package);

        return 0;
    }

    /**
     * Get package path
     */
    protected function getPackagePath(string $vendor, string $package): string
    {
        if ($customPath = $this->option('path')) {
            return PathResolver::toAbsolutePath($customPath);
        }

        $basePath = base_path(config('packager.packager.paths.packages'));
        $vendorKebab = Str::kebab($vendor);
        $packageKebab = Str::kebab($package);

        return PathResolver::joinPaths($basePath, $vendorKebab, $packageKebab);
    }

    /**
     * Display usage instructions
     */
    protected function displayUsageInstructions(string $vendor, string $package): void
    {
        $vendorKebab = Str::kebab($vendor);
        $packageKebab = Str::kebab($package);
        $composerName = "{$vendorKebab}/{$packageKebab}";

        $this->output->newLine();
        $this->line('You can now use the package in your Laravel application.');
        $this->line("Composer name: {$composerName}");
        $this->output->newLine();
    }
}
