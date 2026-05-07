<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Simtabi\Laranail\PackageTools\Concerns\Package\ManagesComposer;

/**
 * EnablePackageCommand - Enable a disabled package
 *
 * Re-enables a previously disabled package by adding it back to composer require.
 */
class EnablePackageCommand extends Command
{
    use ManagesComposer;

    protected $signature = 'packager:enable
                            {vendor : The package vendor name}
                            {package : The package name}
                            {--version=*@dev : Package version constraint}';

    protected $description = 'Enable a disabled package';

    /**
     * Execute the console command
     */
    public function handle(): int
    {
        $vendor = $this->argument('vendor');
        $package = $this->argument('package');
        $version = $this->option('version');

        $vendorKebab = Str::kebab($vendor);
        $packageKebab = Str::kebab($package);
        $composerName = "{$vendorKebab}/{$packageKebab}";

        $this->info("Enabling package: {$composerName}");

        // Check if package is currently disabled
        $status = $this->getPackageStatus($vendor, $package);

        if ($status === 'not_found') {
            $this->error("Package '{$composerName}' not found in composer.json");
            $this->line('');
            $this->line('Available packages:');
            $this->listAvailablePackages();

            return 1;
        }

        if ($status === 'enabled') {
            $this->info("✓ Package '{$composerName}' is already enabled");

            return 0;
        }

        // Enable the package
        $this->line('Enabling package in composer.json...');
        $this->withComposerErrors(true);

        if (! $this->enablePackage($vendor, $package)) {
            $this->error('Failed to enable package');

            return 1;
        }

        $this->info('✓ Package enabled in composer.json');

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
        $this->info("✓ Package '{$composerName}' enabled successfully!");
        $this->line('');
        $this->line('Next steps:');
        $this->line('  • Run: php artisan config:clear');
        $this->line('  • Run: php artisan cache:clear');

        return 0;
    }

    /**
     * List available packages from composer.json
     */
    protected function listAvailablePackages(): void
    {
        $composerPath = base_path('composer.json');

        if (! file_exists($composerPath)) {
            return;
        }

        $composer = json_decode(file_get_contents($composerPath), true);

        if (! isset($composer['require'])) {
            return;
        }

        $packages = [];
        foreach ($composer['require'] as $name => $version) {
            if (strpos($name, '/') !== false) {
                $packages[] = $name;
            }
        }

        if (empty($packages)) {
            $this->line('  (No packages found)');

            return;
        }

        foreach ($packages as $pkg) {
            $this->line("  • {$pkg}");
        }
    }
}
