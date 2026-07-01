<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Actions;

use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Services\PostService;

/**
 * Use-case: revert a published post back to a draft via {@see PostService::unpublish()}.
 */
class UnpublishPostAction
{
    public function __construct(
        private readonly PostService $posts,
    ) {}

    public function __invoke(Post $post): Post
    {
        return $this->posts->unpublish($post);
    }

    public function handle(Post $post): Post
    {
        return $this($post);
    }
}
