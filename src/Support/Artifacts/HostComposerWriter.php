<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Support\Artifacts;

use Illuminate\Filesystem\Filesystem;

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
    private const CONTAINERS = ['modules', 'packages', 'plugins'];

    public function __construct(private readonly Filesystem $files) {}

    public function wire(string $composerPath): void
    {
        $composer = $this->files->exists($composerPath)
            ? (json_decode($this->files->get($composerPath), true) ?: [])
            : [];

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
            $present = false;
            foreach ((array) $repositories as $repo) {
                if (($repo['type'] ?? null) === 'path' && ($repo['url'] ?? null) === $url) {
                    $present = true;
                    break;
                }
            }
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

        $this->files->put(
            $composerPath,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL,
        );
    }
}
