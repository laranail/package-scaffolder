<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Some\NamespacePath\Blog\Blog;
use Some\NamespacePath\Blog\Models\Post;

/**
 * Embeddable approved-comments list: <x-{prefix}::comments :post="$post" />.
 */
class Comments extends Component
{
    public mixed $comments;

    public function __construct(?Post $post = null, mixed $comments = null)
    {
        $this->comments = $comments ?? ($post !== null ? app(Blog::class)->comments($post) : collect());
    }

    public function render(): View
    {
        return view('modules/blog::components.comments');
    }
}
