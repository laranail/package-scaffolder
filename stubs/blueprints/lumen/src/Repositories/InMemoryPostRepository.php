<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Repositories;

use Some\NamespacePath\Blog\Contracts\PostRepositoryInterface;
use Some\NamespacePath\Blog\Models\Post;

/**
 * A dependency-free reference repository. Swap it for a database- or file-backed
 * implementation of {@see PostRepositoryInterface} in a real project.
 */
final class InMemoryPostRepository implements PostRepositoryInterface
{
    /** @var array<int, Post> */
    private array $items = [];

    private int $sequence = 0;

    /** @return list<Post> */
    public function all(): array
    {
        return array_values($this->items);
    }

    public function find(int $id): ?Post
    {
        return $this->items[$id] ?? null;
    }

    public function save(Post $post): Post
    {
        $id = $post->id ?? ++$this->sequence;
        $saved = new Post($id, $post->title, $post->body);
        $this->items[$id] = $saved;

        return $saved;
    }
}
