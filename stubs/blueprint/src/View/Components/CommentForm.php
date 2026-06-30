<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Some\NamespacePath\Blog\Models\Post;

/**
 * A plain (non-Livewire) comment form, auth-aware: shows the form when the user
 * may comment, otherwise a login prompt. Posts to the configured web route.
 * <x-{prefix}::comment-form :post="$post" />.
 */
class CommentForm extends Component
{
    public bool $canComment;

    public function __construct(public Post $post)
    {
        $this->canComment = auth()->check() || (bool) config('modules.blog.comments.allow_guests', true);
    }

    public function render(): View
    {
        return view('modules/blog::components.comment-form');
    }
}
