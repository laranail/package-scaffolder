<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Some\NamespacePath\Blog\Models\Category;

class CategorySaved
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Category $category,
    ) {}
}
