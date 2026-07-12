<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog;

use Some\NamespacePath\Blog\Contracts\PostRepositoryInterface;
use Some\NamespacePath\Blog\Models\Post;

/**
 * The package manager — a small, framework-agnostic facade over the entity
 * repository. Constructed with any {@see PostRepositoryInterface} implementation.
 */
final class Blog
{
    public function __construct(private readonly PostRepositoryInterface $posts) {}

    /** @return list<Post> */
    public function all(): array
    {
        return $this->posts->all();
    }

    public function find(int $id): ?Post
    {
        return $this->posts->find($id);
    }

    public function create(string $title, string $body): Post
    {
        return $this->posts->save(new Post(null, $title, $body));
    }
}
