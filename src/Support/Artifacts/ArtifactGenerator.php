<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Support\Artifacts;

use Illuminate\Filesystem\Filesystem;
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
    private const BINARY = ['png', 'jpg', 'jpeg', 'gif', 'ico', 'webp', 'woff', 'woff2', 'ttf', 'eot', 'lock'];

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

            if (str_contains($content, '@artifact:')) {
                $content = MarkerProcessor::process($content, $markers);
            }

            $this->files->put($file->getPathname(), $content);
        }

        // Delete first (prune map uses the blueprint's original file names), then
        // rename the surviving files to the artifact identity.
        $this->deleteDisabledPaths($request, $targetPath);
        $this->renamePaths($request, $targetPath);
        $this->repairComposer($request, $targetPath);
        $this->runPint($targetPath);

        return $targetPath;
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
     * Rename files whose basename carries a placeholder identifier
     * (BlogServiceProvider.php → {Studly}ServiceProvider.php, blog.php → {lower}.php).
     */
    private function renamePaths(GenerationRequest $request, string $targetPath): void
    {
        $map = [
            TokenReplacer::PLACEHOLDER_STUDLY => $request->studly(),
            TokenReplacer::PLACEHOLDER_LOWER => $request->lower(),
        ];

        foreach ($this->files->allFiles($targetPath) as $file) {
            $renamed = strtr($file->getFilename(), $map);

            if ($renamed !== $file->getFilename()) {
                $this->files->move($file->getPathname(), $file->getPath().'/'.$renamed);
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
            fn (string $f) => $f !== 'livewire',
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

        if (isset($composer['extra']['laravel']['providers'])) {
            $composer['extra']['laravel']['providers'] = array_values(array_filter(
                $composer['extra']['laravel']['providers'],
                static fn (string $provider): bool => ! self::matchesAny($provider, $providerNeedles),
            ));
        }

        foreach (['require', 'require-dev', 'suggest'] as $section) {
            if (! isset($composer[$section]) || ! is_array($composer[$section])) {
                continue;
            }

            $composer[$section] = array_filter(
                $composer[$section],
                static fn (string $pkg): bool => ! self::matchesAny($pkg, $depNeedles),
                ARRAY_FILTER_USE_KEY,
            );
        }

        $this->files->put(
            $path,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL,
        );
    }

    /**
     * @param  list<string>  $needles
     */
    private static function matchesAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
