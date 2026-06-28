<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Simtabi\Laranail\Package\Tools\Concerns\Package\ManagesComposer;
use Simtabi\Laranail\Package\Tools\Support\PathResolver;

/**
 * RemovePackageCommand - Remove a local package completely
 *
 * Uninstalls the package and optionally deletes the package directory.
 */
class RemovePackageCommand extends Command
{
    use ManagesComposer;

    protected $signature = 'packager:remove
                            {vendor : The package vendor name}
                            {package : The package name}
                            {--keep-files : Keep package files after uninstalling}
                            {--force : Skip confirmation}';

    protected $description = 'Remove a local package completely';

    /**
     * Execute the console command
     */
    public function handle(): int
    {
        $vendor = $this->argument('vendor');
        $package = $this->argument('package');
        $keepFiles = $this->option('keep-files');
        $force = $this->option('force');

        $vendorKebab = Str::kebab($vendor);
        $packageKebab = Str::kebab($package);
        $composerName = "{$vendorKebab}/{$packageKebab}";

        // Confirm deletion
        if (! $force) {
            if (! $keepFiles) {
                $this->warn("This will uninstall '{$composerName}' and DELETE all package files.");
            } else {
                $this->warn("This will uninstall '{$composerName}' but keep the package files.");
            }

            if (! $this->confirm('Do you want to continue?')) {
                $this->info('Operation cancelled.');

                return 0;
            }
        }

        // Get package path before uninstalling
        $packagePath = $this->getPackagePath($vendor, $package);

        // Uninstall via composer
        $this->info('Uninstalling package...');
        $this->withComposerErrors(true);

        if (! $this->uninstallLocalPackage($vendor, $package)) {
            $this->error('Failed to uninstall package via composer');

            return 1;
        }

        $this->info('✓ Package uninstalled');

        // Delete files if requested
        if (! $keepFiles && File::isDirectory($packagePath)) {
            $this->line('Deleting package files...');

            if (File::deleteDirectory($packagePath)) {
                $this->info("✓ Package files deleted from: {$packagePath}");
            } else {
                $this->error("Failed to delete package files at: {$packagePath}");
                $this->warn('You may need to delete them manually');

                return 1;
            }
        }

        // Success
        $this->output->newLine();
        $this->info('✓ Package removed successfully!');

        if ($keepFiles) {
            $this->line("Package files are still available at: {$packagePath}");
        }

        return 0;
    }

    /**
     * Get package path
     */
    protected function getPackagePath(string $vendor, string $package): string
    {
        $basePath = base_path(config('packager.packager.paths.packages'));
        $vendorKebab = Str::kebab($vendor);
        $packageKebab = Str::kebab($package);

        return PathResolver::joinPaths($basePath, $vendorKebab, $packageKebab);
    }
}
