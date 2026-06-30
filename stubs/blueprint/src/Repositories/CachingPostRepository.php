<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Repositories;

use Closure;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Some\NamespacePath\Blog\Contracts\PostRepositoryInterface;
use Some\NamespacePath\Blog\DataTransferObjects\PostData;
use Some\NamespacePath\Blog\Listeners\FlushBlogCache;
use Some\NamespacePath\Blog\Models\Post;

/**
 * Opt-in caching decorator for {@see PostRepositoryInterface} (worked example of
 * container `extend()` decoration + a genuine perf win). Caches single-post and
 * published-feed reads (a missing slug is negatively cached under a short TTL, so
 * a flood of bogus slugs can't repeatedly hit the DB); writes pass straight through.
 * Invalidation is **event-driven** (see {@see FlushBlogCache}), not tied to these
 * write methods — so writes from any source (Filament/Nova/raw Eloquent) still bust
 * the cache. Keys are namespaced by a bumped version, portable across all cache
 * stores (no tag support required).
 *
 * Enable with config('modules.blog.cache.enabled'); wired in the service provider.
 */
class CachingPostRepository implements PostRepositoryInterface
{
    /** Sentinel cached for a "miss" so absence is cached too (anti cache-penetration). */
    private const MISS = '__blog_miss__';

    public function __construct(
        private readonly PostRepositoryInterface $inner,
        private readonly CacheRepository $cache,
    ) {}

    public function findBySlug(string $slug): ?Post
    {
        // Nullable read: a missing slug is negatively cached (short TTL) via a
        // sentinel, so a flood of bogus slugs can't repeatedly hit the database.
        return $this->rememberNullable("slug:{$slug}", fn () => $this->inner->findBySlug($slug));
    }

    public function paginatePublished(int $perPage = 15): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage();

        return $this->remember("published:{$perPage}:{$page}", fn () => $this->inner->paginatePublished($perPage));
    }

    public function all(): Collection
    {
        return $this->inner->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters, int $perPage = 15, bool $onlyPublished = true): LengthAwarePaginator
    {
        return $this->inner->search($filters, $perPage, $onlyPublished);
    }

    public function findOrFail(int $id): Post
    {
        return $this->inner->findOrFail($id);
    }

    public function create(PostData $data): Post
    {
        return $this->inner->create($data);
    }

    public function update(Post $post, PostData $data): Post
    {
        return $this->inner->update($post, $data);
    }

    public function delete(Post $post): bool
    {
        return $this->inner->delete($post);
    }

    public function restore(Post $post): bool
    {
        return $this->inner->restore($post);
    }

    public function forceDelete(Post $post): bool
    {
        return $this->inner->forceDelete($post);
    }

    private function remember(string $key, Closure $callback): mixed
    {
        $ttl = (int) config('modules.blog.cache.ttl', 3600);

        return $this->cache->remember($this->key($key), $ttl, $callback);
    }

    /**
     * Like remember(), but caches a sentinel for null results (which Cache::remember
     * would otherwise treat as a miss and never store) under a shorter negative TTL.
     */
    private function rememberNullable(string $key, Closure $callback): mixed
    {
        $ttl = (int) config('modules.blog.cache.ttl', 3600);
        $missTtl = (int) config('modules.blog.cache.miss_ttl', 60);
        $cacheKey = $this->key($key);

        $value = $this->cache->get($cacheKey, fn () => null);

        if ($value === null) {
            $value = $callback() ?? self::MISS;
            $this->cache->put($cacheKey, $value, $value === self::MISS ? $missTtl : $ttl);
        }

        return $value === self::MISS ? null : $value;
    }

    private function key(string $key): string
    {
        $version = (int) $this->cache->get('blog:cache:version', 1);

        return "blog:v{$version}:{$key}";
    }
}
