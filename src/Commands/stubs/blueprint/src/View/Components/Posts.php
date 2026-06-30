<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Some\NamespacePath\Blog\Blog;

/**
 * Embeddable post list/feed. Drop into any layout: <x-{prefix}::posts />.
 * Pass :posts to render a specific collection, else the published feed is used.
 */
class Posts extends Component
{
    public mixed $posts;

    public function __construct(mixed $posts = null, ?int $perPage = null)
    {
        $this->posts = $posts ?? app(Blog::class)->feed($perPage);
    }

    public function render(): View
    {
        return view('modules/blog::components.posts');
    }
}
