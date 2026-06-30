<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Listeners;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Some\NamespacePath\Blog\Repositories\CachingPostRepository;

/**
 * Busts the blog read cache on any post/comment lifecycle event by bumping a
 * version key embedded in every cache key (see {@see CachingPostRepository}).
 * Event-driven so writes from ANY source invalidate — including admin panels/raw
 * Eloquent, which bypass the repository decorator. Wired only when caching is on.
 */
class FlushBlogCache
{
    public function __construct(
        private readonly CacheRepository $cache,
    ) {}

    public function handle(object $event): void
    {
        // Atomic bump where the store supports it; seed the key first so stores
        // that require an existing key can increment it.
        $this->cache->add('blog:cache:version', 1);

        if ($this->cache->increment('blog:cache:version') === false) {
            // Fallback for non-atomic stores.
            $this->cache->forever('blog:cache:version', (int) $this->cache->get('blog:cache:version', 1) + 1);
        }
    }
}
