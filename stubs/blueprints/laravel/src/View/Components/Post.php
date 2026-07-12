<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Some\NamespacePath\Blog\Blog;
use Some\NamespacePath\Blog\Models\Post as PostModel;

/**
 * Embeddable single-post view (title, meta, featured image, body, tags, related,
 * comments). Drop into any layout: <x-{prefix}::post :post="$post" />.
 */
class Post extends Component
{
    /** @var Collection<int, PostModel> */
    public Collection $related;

    public function __construct(public PostModel $post)
    {
        $this->related = app(Blog::class)->related($post);
    }

    public function render(): View
    {
        return view('modules/blog::components.post');
    }
}
