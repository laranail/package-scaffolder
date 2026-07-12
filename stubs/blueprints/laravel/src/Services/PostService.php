<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Some\NamespacePath\Blog\Contracts\PostRepositoryInterface;
use Some\NamespacePath\Blog\DataTransferObjects\PostData;
use Some\NamespacePath\Blog\Enums\PostStatus;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Search\SearchManager;

/**
 * Owns all post CRUD and data processing. Persistence is delegated to the
 * repository; lifecycle transitions (publish/unpublish), lookups and widgets
 * live here; search delegates to the {@see SearchManager} driver; body
 * sanitization runs at the model layer (see BodyProcessor). Actions and the Blog
 * manager consume this service — nothing else queries posts directly.
 */
class PostService
{
    public function __construct(
        private readonly PostRepositoryInterface $posts,
        private readonly TagService $tags,
        private readonly SearchManager $search,
    ) {}

    public function paginatePublished(int $perPage = 15): LengthAwarePaginator
    {
        return $this->posts->paginatePublished($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters, int $perPage = 15, bool $onlyPublished = true): LengthAwarePaginator
    {
        // Delegates to the active search driver (database by default).
        return $this->search->search($filters, $perPage, $onlyPublished);
    }

    public function find(int $id): Post
    {
        return $this->posts->findOrFail($id);
    }

    public function findBySlug(string $slug): ?Post
    {
        return $this->posts->findBySlug($slug);
    }

    /**
     * Resolve a post by numeric id or slug — the single source for the CLI.
     */
    public function findByKey(string $key, bool $withTrashed = false): ?Post
    {
        return Post::query()
            ->when($withTrashed, fn (Builder $query) => $query->withTrashed())
            ->where(function (Builder $query) use ($key): void {
                // Match by slug, and only add the numeric id branch for numeric input —
                // Postgres rejects `id = 'a-slug'` with an invalid-integer error.
                $query->where('slug', $key);

                if (is_numeric($key)) {
                    $query->orWhere('id', (int) $key);
                }
            })
            ->first();
    }

    public function create(PostData $data): Post
    {
        // Body sanitization runs at the model layer (PostObserver::saving via the
        // BodyProcessor) so every writer is consistent — see BodyProcessor.
        $post = $this->posts->create($data);

        if ($data->tags !== null) {
            $this->tags->syncForPost($post, $data->tags);
        }

        return $post;
    }

    public function update(Post $post, PostData $data): Post
    {
        $post = $this->posts->update($post, $data);

        if ($data->tags !== null) {
            $this->tags->syncForPost($post, $data->tags);
        }

        return $post;
    }

    public function delete(Post $post): bool
    {
        return $this->posts->delete($post);
    }

    public function restore(Post $post): bool
    {
        return $this->posts->restore($post);
    }

    public function forceDelete(Post $post): bool
    {
        return $this->posts->forceDelete($post);
    }

    public function publish(Post $post): Post
    {
        if ($post->isPublished()) {
            return $post;
        }

        $post->forceFill([
            'status' => PostStatus::Published,
            'published_at' => $post->published_at ?? now(),
        ])->save();

        return $post;
    }

    public function unpublish(Post $post): Post
    {
        $post->forceFill([
            'status' => PostStatus::Draft,
            'published_at' => null,
        ])->save();

        return $post;
    }

    /**
     * Published posts related to the given one (same category, recent, excludes self).
     *
     * @return Collection<int, Post>
     */
    public function related(Post $post, int $limit = 5): Collection
    {
        return Post::query()
            ->published()
            ->whereKeyNot($post->getKey())
            ->when($post->category_id, fn (Builder $query) => $query->where('category_id', $post->category_id))
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Post>
     */
    public function recent(int $limit = 5): Collection
    {
        return Post::query()->published()->latest('published_at')->limit($limit)->get();
    }

    /**
     * Most popular published posts. Ranks by comment count (default) or view
     * count, per config('modules.blog.popular_by').
     *
     * @return Collection<int, Post>
     */
    public function popular(int $limit = 5): Collection
    {
        // Rank/expose only APPROVED comments so pending or spam comments can't inflate
        // popularity or leak a count of unmoderated comments to the public widget.
        $query = Post::query()
            ->published()
            ->withCount(['comments' => fn ($q) => $q->approved()]);

        $order = config('modules.blog.popular_by', 'comments') === 'views' ? 'views' : 'comments_count';

        return $query->orderByDesc($order)->latest('published_at')->limit($limit)->get();
    }

    /**
     * Featured/pinned published posts.
     *
     * @return Collection<int, Post>
     */
    public function featured(int $limit = 5): Collection
    {
        return Post::query()->published()->featured()->latest('published_at')->limit($limit)->get();
    }

    /**
     * Atomically record a view (server-managed counter; never mass-assignable).
     *
     * Uses a query-builder increment, NOT $post->increment(), so it does not fire
     * the model "updated" event — a page view must not dispatch PostUpdated or
     * bust the read cache. The in-memory model is left as-is (refresh to read).
     */
    public function recordView(Post $post): Post
    {
        // toBase() so it's a plain counter UPDATE — no model events (PostUpdated),
        // no updated_at touch (which would pollute the sitemap lastmod).
        Post::query()->whereKey($post->getKey())->toBase()->increment('views');

        return $post;
    }

    /**
     * Month buckets ("2026-06") of published posts with their counts. Grouped in
     * PHP so it stays portable across SQLite/MySQL/Postgres.
     *
     * @return Collection<int, object{period: string, total: int}>
     */
    public function archive(): Collection
    {
        return Post::query()
            ->published()
            ->orderByDesc('published_at')
            ->get(['published_at'])
            ->groupBy(fn (Post $post) => $post->published_at?->format('Y-m'))
            ->map(fn (Collection $group, string $period): object => (object) [
                'period' => $period,
                'total' => $group->count(),
            ])
            ->values();
    }

    /**
     * @return array{posts: int, published: int, scheduled: int}
     */
    public function counts(): array
    {
        return [
            'posts' => Post::query()->count(),
            'published' => Post::query()->published()->count(),
            'scheduled' => Post::query()->where('status', PostStatus::Scheduled)->count(),
        ];
    }
}
