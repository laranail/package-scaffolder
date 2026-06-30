<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Actions;

use Some\NamespacePath\Blog\Events\PostPublished;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Observers\PostObserver;
use Some\NamespacePath\Blog\Services\PostService;

/**
 * Use-case: publish a post via {@see PostService::publish()}. The
 * {@see PostPublished} event is emitted by {@see PostObserver} on the status
 * change, so it fires exactly once however a post becomes published.
 */
class PublishPostAction
{
    public function __construct(
        private readonly PostService $posts,
    ) {}

    public function __invoke(Post $post): Post
    {
        return $this->posts->publish($post);
    }

    public function handle(Post $post): Post
    {
        return $this($post);
    }
}
