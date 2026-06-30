<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Search\Drivers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Some\NamespacePath\Blog\Contracts\PostRepositoryInterface;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Search\SearchDriver;

/**
 * Optional full-text driver backed by Laravel Scout. Requires the host to:
 *  - `composer require laravel/scout` and configure an engine, and
 *  - add the `Laravel\Scout\Searchable` trait to {@see Post}.
 *
 * Excluded from static analysis because Scout/`Post::search()` aren't installed in
 * this package's CI; guarded at construction by {@see SearchManager}.
 *
 * Design: Scout supplies relevance-ranked *keys*, then the DATABASE applies the
 * authoritative gate (the `published()` scope = status + `published_at <= now`) and
 * the package's category/tag/featured filters. So a future-dated "published" post
 * can never leak through the engine, and the other filters behave exactly like the
 * database driver. Scout's relevance order is preserved (MySQL `FIELD()`) unless the
 * caller asked for an explicit `sort`. The key set is capped to bound the IN() query.
 */
class ScoutSearchDriver implements SearchDriver
{
    private const KEY_CAP = 1000;

    public function __construct(
        private readonly PostRepositoryInterface $posts,
    ) {}

    public function search(array $filters, int $perPage = 15, bool $onlyPublished = true): LengthAwarePaginator
    {
        $term = trim((string) ($filters['search'] ?? ''));

        // No free-text term → nothing for the engine to do; defer to the DB driver.
        if ($term === '') {
            return $this->posts->search($filters, $perPage, $onlyPublished);
        }

        // 1) Relevance-ranked keys from the engine (capped).
        $ids = Post::search($term)->take(self::KEY_CAP)->keys()->all();

        // 2) The database owns the gate + filters/sort; the text term is dropped
        //    (the engine already matched it). whereKey([0]) yields an empty result.
        $query = Post::query()
            ->whereKey($ids ?: [0])
            ->when($onlyPublished, fn (Builder $q) => $q->published())
            ->filter([...$filters, 'search' => null]);

        // Keep Scout's relevance order unless an explicit sort was requested.
        if (empty($filters['sort']) && $ids !== []) {
            $order = implode(',', array_map('intval', $ids));
            $query->reorder()->orderByRaw("FIELD(`id`, {$order})");
        }

        return $query->paginate($perPage)->appends($filters);
    }
}
