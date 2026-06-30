<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

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

        $countMax = (int) config('modules.blog.validation.tag_count_max', 25);

        if (count($value) > $countMax) {
            $fail('modules/blog::blog.validation.tags_too_many')->translate(['max' => $countMax]);

            return;
        }

        foreach ($value as $tag) {
            if (! is_string($tag) || trim($tag) === '' || Str::length($tag) > $max) {
                $fail('modules/blog::blog.validation.tags_invalid')->translate();

                return;
            }
        }
    }
}
