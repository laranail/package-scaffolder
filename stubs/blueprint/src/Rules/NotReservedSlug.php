<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects a slug that would shadow a reserved web path (e.g. a post slug of
 * "create" colliding with /blog/create). Reserved segments come from
 * config('modules.blog.validation.reserved_slugs').
 */
class NotReservedSlug implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $reserved = (array) config('modules.blog.validation.reserved_slugs', ['create', 'edit', 'feed', 'sitemap', 'sitemap.xml']);

        if (in_array(strtolower($value), array_map('strtolower', $reserved), true)) {
            $fail('modules/blog::blog.validation.slug_reserved')->translate();
        }
    }
}
