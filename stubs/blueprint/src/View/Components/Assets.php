<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use Throwable;

/**
 * Emits the <link>/<script> tags for the configured CSS framework bundle.
 *
 * Two modes (config 'modules.blog.ui.assets.live'):
 *  - compiled (default): load the already-built assets as plain tags, resolving the
 *    hashed filenames once from the Vite manifest. No Vite runtime, no HMR.
 *  - live: drive it through Laravel's Vite handler — hashed URLs + modulepreload,
 *    and hot-module reload when the package's Vite dev server is running.
 *
 * The framework → entry-point mapping is internal build wiring (it mirrors
 * vite.config.js), so it lives here in code, NOT in host config. Each JS entry
 * imports its own CSS, so one entry per framework is the complete bundle.
 */
class Assets extends Component
{
    /**
     * Framework → its Vite entry source path(s). Keep in sync with vite.config.js
     * `input`. "none" (and any unknown value) emits nothing.
     *
     * @var array<string, array<int, string>>
     */
    private const BUNDLES = [
        'tailwind' => ['resources/assets/scripts/tailwind.js'],
        'bootstrap' => ['resources/assets/scripts/bootstrap.js'],
        'vanilla' => ['resources/assets/scripts/blog.js'],
    ];

    public string $tags = '';

    public function __construct(?string $framework = null)
    {
        $framework ??= (string) config('modules.blog.ui.framework', 'tailwind');

        $entries = self::BUNDLES[$framework] ?? null;

        if ($entries === null) {
            return; // 'none' or an unknown framework → emit nothing
        }

        $this->tags = config('modules.blog.ui.assets.live', false)
            ? $this->liveTags($entries)
            : $this->compiledTags($entries);
    }

    /**
     * Live mode — resolve to tags via a dedicated Vite instance pointed at the
     * package's build directory (modulepreload + HMR support).
     *
     * @param  array<int, string>  $entries
     */
    private function liveTags(array $entries): string
    {
        $buildDir = $this->buildDirectory();

        try {
            $vite = (new Vite)
                ->useBuildDirectory($buildDir)
                ->useManifestFilename('.vite/manifest.json') // Vite 5 manifest location
                ->useHotFile(public_path($buildDir.'/hot'));  // enables HMR in dev

            return (string) $vite($entries);
        } catch (Throwable) {
            return ''; // assets not built/published yet — emit nothing, never 500
        }
    }

    /**
     * Compiled mode — read the built manifest once and emit plain <link>/<script>
     * tags for the framework's hashed files. The hash is the cache-buster, so no
     * Vite runtime (or dev server) is involved.
     *
     * @param  array<int, string>  $entries
     */
    private function compiledTags(array $entries): string
    {
        $buildDir = $this->buildDirectory();
        $manifest = $this->manifest($buildDir);

        if ($manifest === []) {
            return '';
        }

        $styles = [];
        $scripts = [];

        foreach ($entries as $entry) {
            $chunk = $manifest[$entry] ?? null;

            if (! is_array($chunk)) {
                continue;
            }

            foreach ((array) ($chunk['css'] ?? []) as $css) {
                $styles[] = asset("{$buildDir}/{$css}");
            }

            $file = $chunk['file'] ?? null;

            if (is_string($file)) {
                Str::endsWith($file, '.css')
                    ? $styles[] = asset("{$buildDir}/{$file}")
                    : $scripts[] = asset("{$buildDir}/{$file}");
            }
        }

        return $this->tagsFor(array_unique($styles), array_unique($scripts));
    }

    /**
     * @param  array<int, string>  $styles
     * @param  array<int, string>  $scripts
     */
    private function tagsFor(array $styles, array $scripts): string
    {
        $out = '';

        foreach ($styles as $href) {
            $out .= '<link rel="stylesheet" href="'.e($href).'">'."\n";
        }

        foreach ($scripts as $src) {
            $out .= '<script type="module" src="'.e($src).'"></script>'."\n";
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function manifest(string $buildDir): array
    {
        $path = public_path($buildDir.'/.vite/manifest.json');

        if (! File::exists($path)) {
            return [];
        }

        return (array) File::json($path); // (array) coerces a malformed manifest (null) to []
    }

    /**
     * The published build directory (relative to public/), falling back to the
     * package-local `build` when the published one is absent — so the component
     * also works during package development before assets are published.
     */
    private function buildDirectory(): string
    {
        $configured = trim((string) config('modules.blog.ui.assets.build_directory', 'vendor/blog/build'), '/');

        if (! $this->buildExists($configured) && $this->buildExists('build')) {
            return 'build';
        }

        return $configured;
    }

    private function buildExists(string $dir): bool
    {
        return File::exists(public_path($dir.'/.vite/manifest.json'))
            || File::exists(public_path($dir.'/hot'));
    }

    public function render(): View
    {
        return view('modules/blog::components.assets');
    }
}
