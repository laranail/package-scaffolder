<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Providers\Integrations;

use Illuminate\Support\ServiceProvider;

/**
 * Optional Filament integration. Self-disabling: a no-op unless Filament is
 * installed, so the package stays headless. The plugin itself is opt-in per
 * panel — a host adds it in their panel provider:
 *
 *     $panel->plugin(\Some\NamespacePath\Blog\Filament\BlogPlugin::make());
 *
 * Registered by the main BlogServiceProvider (so package/module/plugin modes all
 * pick it up) and via composer's extra.laravel.providers for package discovery.
 */
class FilamentBlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // String guard so the autoload path never hard-references an absent class.
        if (! class_exists('Filament\\Panel')) {
            return;
        }

        // Nothing to bind: BlogPlugin is resolved per-panel via make()/the container.
    }
}
