<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Observers;

use Some\NamespacePath\Blog\Events\CommentApproved;
use Some\NamespacePath\Blog\Models\Comment;
use Some\NamespacePath\Blog\Providers\BlogServiceProvider;

/**
 * Single source of the {@see CommentApproved} event — fired when a comment
 * enters the "approved" state, whichever writer made the change (service,
 * an admin panel, raw Eloquent). Registered in {@see BlogServiceProvider}.
 */
class CommentObserver
{
    public function created(Comment $comment): void
    {
        // A comment created already approved (auto-approve) announces itself too.
        if ($comment->approved === true) {
            CommentApproved::dispatch($comment);
        }
    }

    public function updated(Comment $comment): void
    {
        if ($comment->wasChanged('approved') && $comment->approved === true) {
            CommentApproved::dispatch($comment);
        }
    }
}
