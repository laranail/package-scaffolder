<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Some\NamespacePath\Blog\DataTransferObjects\PostData;
use Some\NamespacePath\Blog\Models\Post;

/**
 * Abstraction over post persistence so the rest of the package depends on an
 * interface rather than a concrete Eloquent implementation.
 */
interface PostRepositoryInterface
{
    /**
     * @return Collection<int, Post>
     */
    public function all(): Collection;

    public function paginatePublished(int $perPage = 15): LengthAwarePaginator;

    /**
     * Paginate posts applying a whitelisted set of filters/sorting.
     *
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters, int $perPage = 15, bool $onlyPublished = true): LengthAwarePaginator;

    public function findOrFail(int $id): Post;

    public function findBySlug(string $slug): ?Post;

    public function create(PostData $data): Post;

    public function update(Post $post, PostData $data): Post;

    public function delete(Post $post): bool;

    public function restore(Post $post): bool;

    public function forceDelete(Post $post): bool;
}
