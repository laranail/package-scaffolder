<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Some\NamespacePath\Blog\Contracts\PostRepositoryInterface;
use Some\NamespacePath\Blog\DataTransferObjects\PostData;
use Some\NamespacePath\Blog\Models\Post;

class EloquentPostRepository implements PostRepositoryInterface
{
    /**
     * @return Collection<int, Post>
     */
    public function all(): Collection
    {
        return Post::query()->latest()->get();
    }

    public function paginatePublished(int $perPage = 15): LengthAwarePaginator
    {
        return Post::query()
            ->with('category') // listing cards render the category — avoid an N+1.
            ->published()
            ->latest('published_at')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters, int $perPage = 15, bool $onlyPublished = true): LengthAwarePaginator
    {
        return Post::query()
            ->with('category') // listing cards render the category — avoid an N+1.
            ->when($onlyPublished, fn (Builder $query) => $query->published())
            ->filter($filters)
            ->paginate($perPage)
            ->appends($filters);
    }

    public function findOrFail(int $id): Post
    {
        return Post::query()->findOrFail($id);
    }

    public function findBySlug(string $slug): ?Post
    {
        return Post::query()->where('slug', $slug)->first();
    }

    public function create(PostData $data): Post
    {
        return Post::query()->create($data->toAttributes());
    }

    public function update(Post $post, PostData $data): Post
    {
        $post->update($data->toAttributes());

        return $post->refresh();
    }

    public function delete(Post $post): bool
    {
        return (bool) $post->delete();
    }

    public function restore(Post $post): bool
    {
        return (bool) $post->restore();
    }

    public function forceDelete(Post $post): bool
    {
        return (bool) $post->forceDelete();
    }
}
