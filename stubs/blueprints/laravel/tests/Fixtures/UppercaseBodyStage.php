<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Fixtures;

use Closure;

/**
 * A consumer body-processing stage used in tests to prove Blog::pipe() works.
 */
class UppercaseBodyStage
{
    public function handle(string $body, Closure $next): string
    {
        return $next(strtoupper($body));
    }
}
