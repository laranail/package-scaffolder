<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Some\NamespacePath\Blog\Models\Tag;

class TagDeleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Tag $tag,
    ) {}
}
