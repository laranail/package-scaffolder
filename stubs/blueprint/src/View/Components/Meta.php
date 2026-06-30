<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Component;
use Some\NamespacePath\Blog\Models\Post;

/**
 * SEO head tags for a post: <title>, meta description, canonical, OpenGraph,
 * Twitter cards and JSON-LD Article. Place in <head>:
 * <x-{prefix}::meta :post="$post" />.
 */
class Meta extends Component
{
    public string $title;

    public ?string $description;

    public ?string $image;

    public ?string $url;

    public function __construct(public Post $post)
    {
        $this->title = $post->meta_title ?: $post->title;
        $this->description = $post->meta_description ?: $post->excerpt;
        $this->image = $post->featured_image;

        $show = (string) config('modules.blog.ui.routes.show', 'blog.show');
        $this->url = Route::has($show) ? route($show, $post) : null;
    }

    public function render(): View
    {
        return view('modules/blog::components.meta');
    }
}
