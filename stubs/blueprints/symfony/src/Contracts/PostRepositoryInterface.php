<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Contracts;

use Some\NamespacePath\Blog\Models\Post;

interface PostRepositoryInterface
{
    /** @return list<Post> */
    public function all(): array;

    public function find(int $id): ?Post;

    public function save(Post $post): Post;
}
