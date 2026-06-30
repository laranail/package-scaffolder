<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Some\NamespacePath\Blog\Models\Post;

class PostCard extends Component
{
    public function __construct(
        public Post $post,
    ) {}

    public function render(): View
    {
        return view('modules/blog::components.post-card');
    }
}
