<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Support\Artifacts;

use FilesystemIterator;
use Illuminate\Filesystem\Filesystem;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Generates an artifact from the vendored blueprint template:
 * copy → token-replace → strip disabled feature/plugin %marker% blocks →
 * delete disabled feature/plugin files → repair composer provider list.
 *
 * The folder it writes into is a location only; the PHP root namespace comes
 * from {@see GenerationRequest::$namespaceBase} and never from the container.
 * (Dependency trimming, the Pint pass and host composer.json wiring are layered
 * on in later steps.)
 */
final class ArtifactGenerator
{
    /** Extensions copied verbatim (never token-/marker-processed). */
    private const array BINARY = ['png', 'jpg', 'jpeg', 'gif', 'ico', 'webp', 'woff', 'woff2', 'ttf', 'eot', 'lock'];

    public function __construct(
        private readonly Filesystem $files,
        private readonly array $config,
        private readonly ?string $pintBinary = null,
    ) {}

    /**
     * @return string the absolute path the artifact was written to
     */
    public function generate(GenerationRequest $request, string $sourcePath, string $targetPath): string
    {
        if (! $this->files->isDirectory($sourcePath)) {
            throw new RuntimeException("Blueprint template not found at [{$sourcePath}].");
        }

        if ($this->files->exists($targetPath) && ! $request->force) {
            throw new RuntimeException("Target [{$targetPath}] already exists. Use --force to overwrite.");
        }

        $this->files->deleteDirectory($targetPath);
        $this->files->ensureDirectoryExists($targetPath);
        $this->files->copyDirectory($sourcePath, $targetPath);

        $tokens = $request->tokens();
        $markers = $this->markerSet($request);

        foreach ($this->files->allFiles($targetPath) as $file) {
            if (in_array(strtolower($file->getExtension()), self::BINARY, true)) {
                continue;
            }

            $content = $this->files->get($file->getPathname());
            $content = TokenReplacer::replace($content, $tokens);

            if (str_contains($content, '@artifact:') || str_contains($content, '[[')) {
                $content = MarkerProcessor::process($content, $markers);
            }

            $this->files->put($file->getPathname(), $content);
        }

        // Delete first (prune map uses the blueprint's original file names), then
        // rename the surviving files to the artifact identity.
        $this->deleteDisabledPaths($request, $targetPath);
        $this->deleteUnsupportedManifests($request, $targetPath);
        $this->renamePaths($request, $targetPath);
        $this->repairComposer($request, $targetPath);
        $this->runPint($targetPath);

        return $targetPath;
    }

    /**
     * Remove manifests the selected flavor does not support (e.g. vanilla keeps
     * composer.json only, dropping module.json/plugin.json). Driven by the config
     * flavors[flavor].manifests list + the manifest_files map. Inert for a flavor
     * that supports all manifests (laravel).
     */
    private function deleteUnsupportedManifests(GenerationRequest $request, string $targetPath): void
    {
        $supported = (array) ($this->config['flavors'][$request->flavor]['manifests'] ?? ['composer', 'module', 'plugin']);

        foreach ((array) ($this->config['manifest_files'] ?? []) as $manifest => $files) {
            if (in_array($manifest, $supported, true)) {
                continue;
            }

            foreach ((array) $files as $relative) {
                $path = $targetPath.'/'.$relative;
                if ($this->files->exists($path)) {
                    $this->files->delete($path);
                }
            }
        }
    }

    /**
     * Best-effort Pint pass over the generated artifact. Pint is purely syntactic
     * (no autoloading needed), so its `no_unused_imports` fixer strips the `use`
     * statements orphaned by stripped feature wiring, and it normalises formatting
     * to the gold-standard style. Silently skipped if no Pint binary is available.
     */
    private function runPint(string $target): void
    {
        if ($this->pintBinary === null || ! is_file($this->pintBinary)) {
            return;
        }

        $process = new Process([$this->pintBinary, $target]);
        $process->setTimeout(120);
        $process->run();
    }

    /**
     * Rename files AND directories whose basename carries a placeholder identifier,
     * so the on-disk names match the tokenized references in code:
     *   - class files: PostController.php → {Entity}Controller.php (PSR-4)
     *   - view dirs/files: resources/views/posts/ → {entityPlural}/,
     *     livewire/post-list.blade.php → {entityLower}-list.blade.php,
     *     components/posts.blade.php → {entityPlural}.blade.php
     *   - config/lang: blog.php → {lower}.php
     *
     * Studly/plural-before-singular and longest-match (strtr) keep `Posts`→{Plural}
     * and `posts`→{plural} from clobbering `Post`/`post`. Walks deepest-first so a
     * renamed child's parent dir is still renamed correctly afterwards.
     */
    private function renamePaths(GenerationRequest $request, string $targetPath): void
    {
        $tokens = $request->tokens();

        $map = [
            'Posts' => $tokens['entityStudlyPlural'],
            'Post' => $tokens['entityStudly'],
            'posts' => $tokens['entityPlural'],
            'post' => $tokens['entityLower'],
            TokenReplacer::PLACEHOLDER_STUDLY => $request->studly(),  // Blog
            TokenReplacer::PLACEHOLDER_LOWER => $request->lower(),    // blog
        ];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($targetPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            $name = $item->getFilename();
            $renamed = strtr($name, $map);

            if ($renamed !== $name) {
                $destination = $item->getPath().'/'.$renamed;
                if (! $this->files->move($item->getPathname(), $destination)) {
                    throw new RuntimeException("Failed to rename [{$item->getPathname()}] to [{$destination}].");
                }
            }
        }
    }

    /**
     * The set of marker keys to KEEP. Toggleable features that are on, the
     * livewire sub-toggle only when its parent web-ui is on, and the active
     * plugin's marker. Everything else is stripped.
     *
     * @return list<string>
     */
    private function markerSet(GenerationRequest $request): array
    {
        $set = array_values(array_filter(
            $request->features,
            fn (string $f): bool => $f !== 'livewire',
        ));

        if (in_array('web-ui', $set, true) && in_array('livewire', $request->features, true)) {
            $set[] = 'livewire';
        }

        if ($request->plugin === 'filament') {
            $set[] = 'plugin-filament';
        } elseif ($request->plugin === 'nova') {
            $set[] = 'plugin-nova';
        }

        if ($request->plugin !== 'none') {
            $set[] = 'plugins'; // umbrella for prose that applies to any panel
        }

        return $set;
    }

    private function deleteDisabledPaths(GenerationRequest $request, string $targetPath): void
    {
        $featureFiles = (array) ($this->config['feature_files'] ?? []);

        foreach ($featureFiles as $feature => $paths) {
            if (in_array($feature, $request->features, true)) {
                continue; // feature kept
            }

            $this->deletePaths($targetPath, (array) $paths);
        }

        $pluginFiles = (array) ($this->config['plugin_files'] ?? []);

        foreach (['filament', 'nova'] as $type) {
            if ($request->plugin !== $type) {
                $this->deletePaths($targetPath, (array) ($pluginFiles[$type] ?? []));
            }
        }

        if ($request->plugin === 'none') {
            $this->deletePaths($targetPath, (array) ($pluginFiles['shared'] ?? []));
        }
    }

    /**
     * @param  list<string>  $paths
     */
    private function deletePaths(string $targetPath, array $paths): void
    {
        foreach ($paths as $relative) {
            $full = $targetPath.'/'.rtrim($relative, '/');

            if (str_ends_with($relative, '/')) {
                $this->files->deleteDirectory($full);
            } elseif ($this->files->exists($full)) {
                $this->files->delete($full);
            }
        }
    }

    /**
     * Repair composer.json for inactive plugins so the artifact boots and carries
     * no Nova/Filament footprint: drop the integration provider entries (whose
     * classes were deleted) from extra.laravel.providers, and drop the plugin's
     * deps from require/require-dev/suggest.
     */
    private function repairComposer(GenerationRequest $request, string $targetPath): void
    {
        $path = $targetPath.'/composer.json';

        if (! $this->files->exists($path)) {
            return;
        }

        $composer = json_decode($this->files->get($path), true);

        if (! is_array($composer)) {
            return;
        }

        $providerNeedles = [];
        $depNeedles = [];
        if ($request->plugin !== 'filament') {
            $providerNeedles[] = 'Integrations\\Filament';
            $depNeedles[] = 'filament/';
        }
        if ($request->plugin !== 'nova') {
            $providerNeedles[] = 'Integrations\\Nova';
            $depNeedles[] = 'laravel/nova';
        }

        // Deps owned by a disabled feature.
        foreach ((array) ($this->config['feature_deps'] ?? []) as $feature => $packages) {
            if (! in_array($feature, $request->features, true)) {
                $depNeedles = [...$depNeedles, ...(array) $packages];
            }
        }

        if (isset($composer['extra']['laravel']['providers'])) {
            $composer['extra']['laravel']['providers'] = array_values(array_filter(
                $composer['extra']['laravel']['providers'],
                static fn (string $provider): bool => ! self::matchesAny($provider, $providerNeedles),
            ));
        }

        foreach (['require', 'require-dev', 'suggest'] as $section) {
            if (! isset($composer[$section])) {
                continue;
            }
            if (! is_array($composer[$section])) {
                continue;
            }
            $composer[$section] = array_filter(
                $composer[$section],
                static fn (string $pkg): bool => ! self::matchesAny($pkg, $depNeedles),
                ARRAY_FILTER_USE_KEY,
            );
        }

        $encoded = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new RuntimeException("Failed to encode repaired composer.json at [{$path}].");
        }

        $this->atomicPut($path, $encoded.PHP_EOL);
    }

    /**
     * Write via a temp file + rename so an interrupted write never leaves a
     * half-written file. rename() is atomic on the same filesystem.
     */
    private function atomicPut(string $path, string $content): void
    {
        $tmp = $path.'.tmp'.getmypid();
        $this->files->put($tmp, $content);
        $this->files->move($tmp, $path);
    }

    /**
     * @param  list<string>  $needles
     */
    private static function matchesAny(string $haystack, array $needles): bool
    {
        return array_any($needles, fn (string $needle): bool => str_contains($haystack, $needle));
    }
}
