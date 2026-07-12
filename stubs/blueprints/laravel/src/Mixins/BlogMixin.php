<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Mixins;

use Closure;
use Some\NamespacePath\Blog\Blog;

/**
 * Example macro mixin. Register from a consumer provider's boot() to bolt
 * several methods onto the Blog manager at once, no subclassing:
 *
 *     Blog::mixin(new \Some\NamespacePath\Blog\Mixins\BlogMixin());
 *     Blog::trending();
 *
 * Each method returns a Closure and the method name becomes the macro name.
 * When invoked, `$this` inside the closure is bound to the Blog manager — here
 * we resolve it explicitly so the example stays statically analysable.
 */
class BlogMixin
{
    public function trending(): Closure
    {
        return fn (int $limit = 5) => app(Blog::class)->popularPosts($limit);
    }
}
