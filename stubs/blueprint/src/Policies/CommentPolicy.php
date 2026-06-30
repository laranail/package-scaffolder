<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Some\NamespacePath\Blog\Models\Comment;

class CommentPolicy
{
    public function create(?Authenticatable $user): bool
    {
        return (bool) config('modules.blog.comments.allow_guests', true) || $user !== null;
    }

    public function approve(Authenticatable $user, Comment $comment): bool
    {
        return method_exists($user, 'hasRole') && $user->hasRole('admin');
    }

    public function delete(Authenticatable $user, Comment $comment): bool
    {
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return true;
        }

        return (int) $user->getAuthIdentifier() === (int) $comment->author_id;
    }
}
