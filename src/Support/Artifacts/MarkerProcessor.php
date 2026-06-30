<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Support\Artifacts;

/**
 * Strips feature-conditional blocks from a generated template file.
 *
 * Blocks are delimited by comment markers so the template stays valid PHP:
 *
 *     // @artifact:start caching
 *     ...code...
 *     // @artifact:end caching
 *
 * Markdown / NEON / XML use the HTML-comment (`<!-- -->`) or hash (`#`) form. A
 * panel-named sentence inside a PHPDoc block is isolated onto its own line(s) and
 * bracketed with docblock-continuation (` * `) markers, so it strips cleanly while
 * leaving a valid docblock:
 *
 *     /**
 *      * Does the thing.
 *
 *      * @artifact:start plugins
 *      * Works for every writer (facade, Filament, Nova, raw Eloquent).
 *
 *      * @artifact:end plugins
 *      *​/
 *
 * For an ENABLED feature the marker comment lines are removed and the inner
 * code kept; for a DISABLED feature the whole block (markers + inner code) is
 * removed. Markers nest (e.g. `livewire` inside `web-ui`): a disabled outer
 * block removes everything inside regardless of inner state. Imports orphaned
 * by stripped code are cleaned by the post-generation Pint pass.
 */
final class MarkerProcessor
{
    /**
     * @param  list<string>  $enabledFeatures
     */
    public static function process(string $content, array $enabledFeatures): string
    {
        $lines = preg_split('/\R/', $content) ?: [];
        $out = [];
        $skipDepth = 0;

        foreach ($lines as $line) {
            if (preg_match('/^\s*(?:\/\/|<!--|#|\*)\s*@artifact:start\s+(\S+?)(?:\s*-->)?\s*$/', $line, $m) === 1) {
                if ($skipDepth > 0) {
                    // Already inside a disabled block — track nesting so the
                    // matching end doesn't close the outer block early.
                    $skipDepth++;
                } elseif (! in_array($m[1], $enabledFeatures, true)) {
                    $skipDepth = 1;
                }

                continue; // the marker line itself is never emitted
            }

            if (preg_match('/^\s*(?:\/\/|<!--|#|\*)\s*@artifact:end\s+\S+?(?:\s*-->)?\s*$/', $line) === 1) {
                if ($skipDepth > 0) {
                    $skipDepth--;
                }

                continue; // the marker line itself is never emitted
            }

            if ($skipDepth === 0) {
                $out[] = $line;
            }
        }

        return implode("\n", $out);
    }
}
