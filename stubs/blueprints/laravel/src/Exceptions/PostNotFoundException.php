<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Exceptions;

class PostNotFoundException extends BlogException
{
    public static function withSlug(string $slug): self
    {
        return new self("No published post found for slug [{$slug}].");
    }
}
