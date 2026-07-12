<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
// @artifact:start plugin-filament
use Some\NamespacePath\Blog\Providers\Integrations\FilamentBlogServiceProvider;
// @artifact:end plugin-filament
// @artifact:start plugin-nova
use Some\NamespacePath\Blog\Providers\Integrations\NovaBlogServiceProvider;
// @artifact:end plugin-nova
use Some\NamespacePath\Blog\Tests\TestCase;

/**
 * The package must stay headless: with neither Filament nor Nova installed, the
 * guarded integration providers register but do nothing, and the app boots fine.
 */
class PanelsTest extends TestCase
{
    #[Test]
    public function the_integration_providers_are_registered(): void
    {
        $loaded = $this->app->getLoadedProviders();

        // @artifact:start plugin-filament
        $this->assertArrayHasKey(FilamentBlogServiceProvider::class, $loaded);
        // @artifact:end plugin-filament
        // @artifact:start plugin-nova
        $this->assertArrayHasKey(NovaBlogServiceProvider::class, $loaded);
        // @artifact:end plugin-nova
    }

    #[Test]
    public function the_package_is_headless_without_the_panels(): void
    {
        // Neither panel is installed in CI; the guards keep everything a no-op,
        // and the booted application (this suite running) proves it.
        $this->assertFalse(class_exists('Filament\\Panel'));
        $this->assertFalse(class_exists('Laravel\\Nova\\Nova'));
    }
}
