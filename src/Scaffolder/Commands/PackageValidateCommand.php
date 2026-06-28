<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Simtabi\Laranail\Package\Tools\Services\Package\PackageValidator;

/**
 * PackageValidateCommand - Validate package structure and configuration
 *
 * Validates package structure, naming, and composer.json
 */
class PackageValidateCommand extends Command
{
    protected $signature = 'packager:validate
                            {path? : Path to package (defaults to current directory)}
                            {--fix : Attempt to fix validation errors}';

    protected $description = 'Validate package structure and configuration';

    public function __construct(
        protected PackageValidator $packageValidator
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $path = $this->argument('path') ?: getcwd();
        $fix = $this->option('fix');

        $this->info('Validating package...');
        $this->newLine();

        // Get package data
        $packageData = $this->extractPackageData($path);

        // Validate
        $errors = $this->packageValidator->validate($packageData);

        if (empty($errors)) {
            $this->info('✓ Package validation passed');
            $this->displayValidationSuccess($packageData);

            return self::SUCCESS;
        }

        // Show errors
        $this->error('✗ Package validation failed');
        $this->newLine();

        foreach ($errors as $error) {
            $this->line("  • {$error}");
        }

        if ($fix) {
            $this->newLine();
            $this->info('Attempting to fix errors...');
            $fixed = $this->attemptFix($errors, $path);

            if ($fixed) {
                $this->info('✓ Some errors were fixed. Please run validation again.');

                return self::SUCCESS;
            }
        }

        $this->newLine();
        $this->warn('Run with --fix to attempt automatic fixes');

        return self::FAILURE;
    }

    protected function extractPackageData(string $path): array
    {
        $composerPath = $path.'/composer.json';
        $composer = File::exists($composerPath)
            ? (array) json_decode((string) File::get($composerPath), true)
            : [];

        return [
            'path' => $path,
            'name' => $composer['name'] ?? null,
            'namespace' => $this->extractNamespace($composer),
        ];
    }

    protected function extractNamespace(array $composer): ?string
    {
        $psr4 = $composer['autoload']['psr-4'] ?? [];

        return ! empty($psr4) ? array_key_first($psr4) : null;
    }

    protected function displayValidationSuccess(array $packageData): void
    {
        $this->newLine();
        $this->table(
            ['Property', 'Value'],
            [
                ['Package Name', $packageData['name'] ?? 'N/A'],
                ['Namespace', $packageData['namespace'] ?? 'N/A'],
                ['Path', $packageData['path']],
            ]
        );
    }

    protected function attemptFix(array $errors, string $path): bool
    {
        $fixed = false;

        foreach ($errors as $error) {
            if (str_contains($error, 'composer.json')) {
                // Could add auto-fix logic here
                $this->line("  • Skipping: {$error} (manual fix required)");
            }
        }

        return $fixed;
    }
}
