<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Some\NamespacePath\Blog\Actions\PublishPostAction;
use Some\NamespacePath\Blog\Console\PublishScheduledPostsCommand;
use Some\NamespacePath\Blog\Models\Post;

/**
 * Promotes every scheduled post whose publish time has arrived. Dispatched by
 * the {@see PublishScheduledPostsCommand} on a schedule.
 */
class PublishScheduledPosts implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(PublishPostAction $publish): void
    {
        Post::query()
            ->due()
            ->each(static fn (Post $post) => $publish->handle($post));
    }
}
