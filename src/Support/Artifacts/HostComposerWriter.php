<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Support\Artifacts;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;

/**
 * Idempotently wires the host application's composer.json so generated artifacts
 * under platform/{modules,packages,plugins}/ are autoloaded (composer-merge-plugin)
 * and resolvable as path repositories. Running it twice yields the same result,
 * and it never clobbers the developer's unrelated keys: array sections are
 * unioned, scalars are only set when absent, and allow-plugins is merged with the
 * developer's values winning.
 */
final class HostComposerWriter
{
    private const array CONTAINERS = ['modules', 'packages', 'plugins'];

    public function __construct(private readonly Filesystem $files) {}

    public function wire(string $composerPath): void
    {
        // An absent (or empty) file starts from scratch; a NON-empty file that fails
        // to parse must NOT be silently discarded — refuse rather than clobber the
        // developer's composer.json.
        $raw = $this->files->exists($composerPath) ? trim($this->files->get($composerPath)) : '';
        if ($raw === '') {
            $composer = [];
        } else {
            $decoded = json_decode($raw, true);
            if (! is_array($decoded)) {
                throw new RuntimeException(
                    "Host composer.json at [{$composerPath}] is not valid JSON; refusing to overwrite it.",
                );
            }
            $composer = $decoded;
        }

        // composer-merge-plugin includes (union, deduped).
        $includes = array_map(static fn (string $c): string => "./platform/{$c}/*/composer.json", self::CONTAINERS);
        $composer['extra']['merge-plugin']['include'] = array_values(array_unique(
            [...($composer['extra']['merge-plugin']['include'] ?? []), ...$includes],
        ));
        $composer['extra']['merge-plugin']['recurse'] ??= false;
        $composer['extra']['merge-plugin']['replace'] ??= false;

        // path repositories (union by url).
        $repositories = $composer['repositories'] ?? [];
        foreach (self::CONTAINERS as $container) {
            $url = "./platform/{$container}/*";
            $present = array_any((array) $repositories, fn ($repo) => ($repo['type'] ?? null) === 'path' && ($repo['url'] ?? null) === $url);
            if (! $present) {
                $repositories[] = ['type' => 'path', 'url' => $url];
            }
        }
        $composer['repositories'] = array_values($repositories);

        // config: set scalars only when absent (preserve the developer's choices);
        // merge allow-plugins with the developer's values taking precedence.
        $composer['config']['optimize-autoloader'] ??= true;
        $composer['config']['preferred-install'] ??= 'dist';
        $composer['config']['sort-packages'] ??= true;
        $composer['config']['allow-plugins'] = array_merge(
            ['pestphp/pest-plugin' => true, 'wikimedia/composer-merge-plugin' => true],
            $composer['config']['allow-plugins'] ?? [],
        );

        $composer['minimum-stability'] ??= 'dev';
        $composer['prefer-stable'] ??= true;

        $encoded = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new RuntimeException("Failed to encode composer.json for [{$composerPath}].");
        }

        $this->atomicPut($composerPath, $encoded.PHP_EOL);
    }

    /**
     * Write via a temp file + rename so an interrupted write never leaves a
     * half-written (corrupt) composer.json on disk. rename() is atomic on the
     * same filesystem.
     */
    private function atomicPut(string $path, string $content): void
    {
        $tmp = $path.'.tmp'.getmypid();
        $this->files->put($tmp, $content);
        $this->files->move($tmp, $path);
    }
}
