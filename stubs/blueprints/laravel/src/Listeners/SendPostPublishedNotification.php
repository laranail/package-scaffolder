<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Some\NamespacePath\Blog\Events\PostPublished;
use Some\NamespacePath\Blog\Notifications\PostPublishedNotification;

/**
 * Notifies the post's author when their post goes live. Queued so the request
 * is not blocked on delivery.
 */
class SendPostPublishedNotification implements ShouldQueue
{
    /**
     * If the post is deleted between dispatch and processing, drop the queued job
     * instead of failing on a ModelNotFoundException during deserialization.
     */
    public bool $deleteWhenMissingModels = true;

    public function handle(PostPublished $event): void
    {
        $author = $event->post->author;

        $author?->notify(new PostPublishedNotification($event->post));
    }
}
