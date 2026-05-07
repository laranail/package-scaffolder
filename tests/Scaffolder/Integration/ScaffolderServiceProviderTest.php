<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PackageScaffolder\Tests\Integration;

use Illuminate\Contracts\Console\Kernel;
use Simtabi\Laranail\PackageScaffolder\Providers\ScaffolderServiceProvider;
use Simtabi\Laranail\PackageScaffolder\Tests\TestCase;

final class ScaffolderServiceProviderTest extends TestCase
{
    public function test_provider_is_registered_in_test_app(): void
    {
        $providers = $this->app->getLoadedProviders();

        self::assertArrayHasKey(
            ScaffolderServiceProvider::class,
            $providers,
            'ScaffolderServiceProvider should be auto-registered via TestCase::getPackageProviders().',
        );
    }

    public function test_artisan_kernel_lists_scaffolder_commands(): void
    {
        // Smoke test: at least one of the scaffolder commands should be on the
        // registered list. We don't assert which because the command set
        // evolves; just that something Scaffolder-namespaced is present.
        $kernel = $this->app->make(Kernel::class);
        $registered = array_keys($kernel->all());

        $hasScaffolderCommand = false;
        foreach ($registered as $signature) {
            if (str_starts_with($signature, 'package:') || str_starts_with($signature, 'make:package')) {
                $hasScaffolderCommand = true;
                break;
            }
        }

        self::assertTrue(
            $hasScaffolderCommand,
            'No package:* or make:package command found — ScaffolderServiceProvider may not be wiring commands.',
        );
    }
}
