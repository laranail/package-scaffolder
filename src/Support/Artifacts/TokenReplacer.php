<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Package\Scaffolder\Support\Artifacts;

/**
 * Rewrites the blueprint's find-replace placeholders to a target artifact's
 * identity. The blueprint intentionally decouples four identifiers, each with
 * its own placeholder token:
 *
 *   - PSR-4 root namespace : `Some\NamespacePath\Blog`  → {namespaceBase}\{Studly}
 *   - composer/package name: `modules/blog`             → {vendor}/{lower}
 *   - component slug        : `modules-blog`             → {vendor}-{lower}
 *   - config/view/trans key : `modules.blog`            → {vendor}.{lower}
 *   - studly identifier     : `Blog`                     → {Studly}
 *   - lower identifier      : `blog`                     → {lower}
 *
 * Replacements run most-specific first so a later, broader token never clobbers
 * a more specific one. JSON files escape the namespace backslashes, handled by
 * the escaped variant.
 *
 * Note: the studly/lower passes are blanket (the blueprint is a find-replace
 * template), so do not name an artifact with "Blog"/"blog" as a substring.
 */
final class TokenReplacer
{
    public const PLACEHOLDER_NAMESPACE = 'Some\\NamespacePath\\Blog';

    public const PLACEHOLDER_STUDLY = 'Blog';

    public const PLACEHOLDER_LOWER = 'blog';

    public const PLACEHOLDER_VENDOR = 'modules';

    /**
     * @param  array{namespaceBase:string, studly:string, lower:string, vendor:string}  $target
     */
    public static function replace(string $content, array $target): string
    {
        $rootSingle = $target['namespaceBase'].'\\'.$target['studly'];

        $pairs = [
            // JSON-escaped namespace (composer.json) first.
            self::jsonEscape(self::PLACEHOLDER_NAMESPACE) => self::jsonEscape($rootSingle),
            // PHP namespace + use statements.
            self::PLACEHOLDER_NAMESPACE => $rootSingle,
            // Composite name forms before the broad lower pass.
            self::PLACEHOLDER_VENDOR.'/'.self::PLACEHOLDER_LOWER => $target['vendor'].'/'.$target['lower'],
            self::PLACEHOLDER_VENDOR.'-'.self::PLACEHOLDER_LOWER => $target['vendor'].'-'.$target['lower'],
            self::PLACEHOLDER_VENDOR.'.'.self::PLACEHOLDER_LOWER => $target['vendor'].'.'.$target['lower'],
            // Bare identifiers (studly before lower is irrelevant — case-sensitive).
            self::PLACEHOLDER_STUDLY => $target['studly'],
            self::PLACEHOLDER_LOWER => $target['lower'],
        ];

        return strtr($content, $pairs);
    }

    private static function jsonEscape(string $value): string
    {
        return str_replace('\\', '\\\\', $value);
    }
}
