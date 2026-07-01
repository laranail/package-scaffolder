<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Actions;

use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Services\PostService;

class DeletePostAction
{
    public function __construct(
        private readonly PostService $posts,
    ) {}

    public function __invoke(Post $post): bool
    {
        return $this->posts->delete($post);
    }

    public function handle(Post $post): bool
    {
        return $this($post);
    }
}
