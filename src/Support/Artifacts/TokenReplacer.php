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
 * The blueprint also has a distinct PRIMARY ENTITY (`Post` inside `Blog`). It is
 * tokenized separately to {Entity}: studly `Post`/`Posts`, lowercase `post`/`posts`
 * (variables, route params, snake morph aliases, camelCase props). The entity pass
 * deliberately PROTECTS framework API that merely shares the substring — `Route::post`
 * / `->post(` (HTTP verb call), `->postJson`, and English words (`Postgres`,
 * `compost`, `posted`) — so it never renames those. Comment/Category/Tag stay as the
 * generic supporting layer (not tokenized).
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
    public const string PLACEHOLDER_NAMESPACE = 'Some\\NamespacePath\\Blog';

    public const string PLACEHOLDER_STUDLY = 'Blog';

    public const string PLACEHOLDER_LOWER = 'blog';

    public const string PLACEHOLDER_UPPER = 'BLOG';

    public const string PLACEHOLDER_VENDOR = 'modules';

    /**
     * @param  array{namespaceBase:string, studly:string, lower:string, vendor:string, upper?:string, entityStudly?:string, entityStudlyPlural?:string, entityLower?:string, entityPlural?:string}  $target
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
            // SCREAMING_SNAKE env-var prefix (`BLOG_USER_MODEL` → `{UPPER}_USER_MODEL`).
            self::PLACEHOLDER_UPPER => $target['upper'] ?? self::PLACEHOLDER_UPPER,
            // Bare identifiers (studly before lower is irrelevant — case-sensitive).
            self::PLACEHOLDER_STUDLY => $target['studly'],
            self::PLACEHOLDER_LOWER => $target['lower'],
        ];

        return self::replaceEntity(strtr($content, $pairs), $target);
    }

    /**
     * Tokenize the primary entity (`Post` → {Entity}), protecting framework API and
     * English words that share the substring. Defaults to the `Post` identity, so a
     * target without entity keys is a no-op.
     *
     * @param  array<string, string>  $target
     */
    private static function replaceEntity(string $content, array $target): string
    {
        $studly = $target['entityStudly'] ?? 'Post';
        $studlyPlural = $target['entityStudlyPlural'] ?? 'Posts';
        $lower = $target['entityLower'] ?? 'post';
        $plural = $target['entityPlural'] ?? 'posts';

        // preg_replace_callback keeps the replacement LITERAL — an entity name
        // containing `$1`/`\1`/`$` can never be interpreted as a backreference
        // (defence-in-depth; GenerationRequest already Str::studly-sanitises entities).

        // Studly: plural before singular. `(?![a-z])` protects `Postgres`/`PostgreSQL`
        // while still matching `PostController`, `PostStatus`, `recentPosts`, `Post::class`.
        $content = preg_replace_callback('/Posts(?![a-z])/', static fn (): string => $studlyPlural, $content) ?? $content;
        $content = preg_replace_callback('/Post(?![a-z])/', static fn (): string => $studly, $content) ?? $content;

        // Lowercase plural: tokens/relations/tables/views (`blog_posts`, `->posts`,
        // `posts.index`); guarded so it isn't a substring of a longer word (`composts`).
        $content = preg_replace_callback('/(?<![a-zA-Z])posts(?![a-z])/', static fn (): string => $plural, $content) ?? $content;

        // Lowercase singular: `$post`, `{post}`, `post_created`, `_post`, `postService`.
        // `(?![a-z(]|Json)` protects the HTTP verb (`post(`, `Route::post(`), `postJson`,
        // and English words (`posted`, `postpone`); `(?<![a-zA-Z])` protects `compost`.
        $content = preg_replace_callback('/(?<![a-zA-Z])post(?![a-z(]|Json)/', static fn (): string => $lower, $content) ?? $content;

        return $content;
    }

    private static function jsonEscape(string $value): string
    {
        return str_replace('\\', '\\\\', $value);
    }
}
