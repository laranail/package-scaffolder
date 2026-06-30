<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Some\NamespacePath\Blog\Enums\PostStatus;

class StatusBadge extends Component
{
    public PostStatus $status;

    public function __construct(PostStatus|string $status)
    {
        $this->status = $status instanceof PostStatus ? $status : PostStatus::from($status);
    }

    public function render(): View
    {
        return view('modules/blog::components.status-badge');
    }
}
