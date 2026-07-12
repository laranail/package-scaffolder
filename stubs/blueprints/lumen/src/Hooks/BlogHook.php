<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Hooks;

/**
 * Lifecycle hook for the Blog extension, invoked by laranail/package-management when
 * this artifact is loaded as a module/plugin (referenced by the `hook` field in
 * module.json / plugin.json).
 *
 * **Decoupled by design:** this is a plain class with **no dependency on the loader**
 * — the loader duck-types it, calling whichever of these methods exist. `$extension`
 * is the loader's Extension value object at runtime (`$extension->id`, `->version`,
 * `->settings`, …). So a standalone Composer install of this package (the "package"
 * role) carries no runtime dependency on the loader.
 *
 * If you already depend on laranail/package-management, you may instead implement its
 * `Contracts\LifecycleHook` / `Contracts\InstallHook` for full type-safety.
 */
final class BlogHook
{
    public function activated(object $extension): void
    {
        // Activated. Warm caches, register schedules, prime feature flags, …
    }

    public function deactivated(object $extension): void
    {
        // Deactivated. Flush caches/queues owned by this extension, …
    }

    public function installed(object $extension): void
    {
        // Installed (after migrations + asset publishing). Seed reference data, …
    }

    public function removed(object $extension): void
    {
        // Removed. Clean up published files / external resources. The loader preserves
        // your database tables — drop them here only if that's genuinely intended.
    }
}
