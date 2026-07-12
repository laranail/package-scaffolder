<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Models;

/**
 * The primary entity — a plain, framework-agnostic value object.
 */
final class Post
{
    public function __construct(
        public ?int $id,
        public string $title,
        public string $body,
    ) {}
}
