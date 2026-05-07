<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Simtabi\Laranail\PackageTools\Concerns\Package\ManagesComposer;

/**
 * UninstallPackageCommand - Uninstall a local package
 *
 * Removes the package via composer and removes the repository entry.
 */
class UninstallPackageCommand extends Command
{
    use ManagesComposer;

    protected $signature = 'packager:uninstall
                            {vendor : The package vendor name}
                            {package : The package name}';

    protected $description = 'Uninstall a local package';

    /**
     * Execute the console command
     */
    public function handle(): int
    {
        $vendor = $this->argument('vendor');
        $package = $this->argument('package');

        $vendorKebab = Str::kebab($vendor);
        $packageKebab = Str::kebab($package);
        $composerName = "{$vendorKebab}/{$packageKebab}";

        $this->info("Uninstalling package: {$composerName}");

        // Remove via composer
        $this->line('Removing package via composer...');
        $this->withComposerErrors(true); // Show errors

        if (! $this->composerRemove($vendor, $package)) {
            $this->error('Failed to remove package');

            return 1;
        }

        $this->info('✓ Package removed');

        // Remove repository
        $this->line('Removing composer repository...');
        if (! $this->removeComposerRepository($vendor, $package)) {
            $this->warn('Failed to remove repository entry');
            $this->warn('You may need to manually remove it from composer.json');
        } else {
            $this->info('✓ Repository removed');
        }

        // Success
        $this->output->newLine();
        $this->info('✓ Package uninstalled successfully!');

        return 0;
    }
}
