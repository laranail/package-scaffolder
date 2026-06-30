<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Anti-flood: a `rendered_at` unix timestamp must be at least
 * `comments.min_submit_seconds` old (a bot that posts instantly is rejected).
 * A missing timestamp is tolerated — the honeypot + rate limiter still apply.
 */
class NotSubmittedTooQuickly implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $minSeconds = (int) config('modules.blog.comments.min_submit_seconds', 2);

        if ((now()->timestamp - (int) $value) < $minSeconds) {
            $fail('modules/blog::blog.validation.comment_too_fast')->translate();
        }
    }
}
