<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Simtabi\Laranail\PackageTools\Concerns\Package\ManagesComposer;

/**
 * DisablePackageCommand - Disable a package without removing it
 *
 * Disables a package by commenting it out in composer.json.
 * This allows quick toggling for testing without full uninstall/reinstall.
 */
class DisablePackageCommand extends Command
{
    use ManagesComposer;

    protected $signature = 'packager:disable
                            {vendor : The package vendor name}
                            {package : The package name}
                            {--keep-files : Keep package files (don\'t remove from vendor)}';

    protected $description = 'Disable a package without removing it';

    /**
     * Execute the console command
     */
    public function handle(): int
    {
        $vendor = $this->argument('vendor');
        $package = $this->argument('package');
        $keepFiles = $this->option('keep-files');

        $vendorKebab = Str::kebab($vendor);
        $packageKebab = Str::kebab($package);
        $composerName = "{$vendorKebab}/{$packageKebab}";

        $this->info("Disabling package: {$composerName}");

        // Check if package exists
        $status = $this->getPackageStatus($vendor, $package);

        if ($status === 'not_found') {
            $this->error("Package '{$composerName}' not found in composer.json");

            return 1;
        }

        if ($status === 'disabled') {
            $this->info("✓ Package '{$composerName}' is already disabled");

            return 0;
        }

        // Confirm action
        if (! $this->confirm("Are you sure you want to disable '{$composerName}'?", true)) {
            $this->info('Operation cancelled');

            return 0;
        }

        // Disable the package
        $this->line('Disabling package in composer.json...');
        $this->withComposerErrors(true);

        if (! $this->disablePackage($vendor, $package)) {
            $this->error('Failed to disable package');

            return 1;
        }

        $this->info('✓ Package disabled in composer.json');

        // Optionally remove from vendor
        if (! $keepFiles) {
            $this->line('Removing package files...');
            if (! $this->composerRemove($vendor, $package, removeRepository: false)) {
                $this->warn('Failed to remove package files');
                $this->warn('Files may still exist in vendor/ directory');
            } else {
                $this->info('✓ Package files removed');
            }
        } else {
            $this->line('Package files kept (use --keep-files=false to remove)');
        }

        // Run composer dump-autoload
        $this->line('Running composer dump-autoload...');
        if (! $this->composerDumpAutoload()) {
            $this->warn('Failed to run composer dump-autoload');
            $this->warn('You may need to run it manually');
        } else {
            $this->info('✓ Autoload files regenerated');
        }

        // Success
        $this->output->newLine();
        $this->info("✓ Package '{$composerName}' disabled successfully!");
        $this->line('');
        $this->line('The package is now disabled and will not be loaded.');
        $this->line('To re-enable: php artisan packager:enable '.$vendor.' '.$package);

        return 0;
    }
}
