<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Emits the <link>/<script> tags for the configured CSS framework bundle
 * (Tailwind or Bootstrap) by reading the package's published Vite manifest.
 * Use as <x-{prefix}::assets /> — or pass an explicit :framework.
 */
class Assets extends Component
{
    /** @var array<int, string> */
    public array $styles = [];

    /** @var array<int, string> */
    public array $scripts = [];

    public function __construct(?string $framework = null)
    {
        $framework ??= (string) config('modules.blog.ui.framework', 'tailwind');

        if ($framework === 'none') {
            return;
        }

        $manifest = $this->manifest();
        $base = trim((string) config('modules.blog.ui.assets.base', 'vendor/blog/build'), '/');
        $entries = (array) config("modules.blog.ui.assets.bundles.{$framework}", []);

        foreach ($entries as $entry) {
            $chunk = $manifest[$entry] ?? null;

            if (! is_array($chunk)) {
                continue;
            }

            foreach ((array) ($chunk['css'] ?? []) as $css) {
                $this->styles[] = asset("{$base}/{$css}");
            }

            $file = $chunk['file'] ?? null;

            if (! is_string($file)) {
                continue;
            }

            if (str_ends_with($file, '.css')) {
                $this->styles[] = asset("{$base}/{$file}");
            } else {
                $this->scripts[] = asset("{$base}/{$file}");
            }
        }

        $this->styles = array_values(array_unique($this->styles));
        $this->scripts = array_values(array_unique($this->scripts));
    }

    /**
     * @return array<string, mixed>
     */
    private function manifest(): array
    {
        $path = (string) config('modules.blog.ui.assets.manifest');

        if (! is_file($path)) {
            return [];
        }

        return json_decode((string) file_get_contents($path), true) ?: [];
    }

    public function render(): View
    {
        return view('modules/blog::components.assets');
    }
}
