<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates the whole `tags` array: every entry must be a non-empty string no
 * longer than `validation.tag_max`. Replaces the split `tags`/`tags.*` rules.
 */
class ValidTagList implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $max = (int) config('modules.blog.validation.tag_max', 50);

        if (! is_array($value)) {
            $fail('modules/blog::blog.validation.tags_invalid')->translate();

            return;
        }

        foreach ($value as $tag) {
            if (! is_string($tag) || trim($tag) === '' || mb_strlen($tag) > $max) {
                $fail('modules/blog::blog.validation.tags_invalid')->translate();

                return;
            }
        }
    }
}
