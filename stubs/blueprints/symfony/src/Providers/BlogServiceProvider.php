<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Providers;

use Some\NamespacePath\Blog\Blog;
use Some\NamespacePath\Blog\Repositories\InMemoryPostRepository;

/**
 * The extension's entry service for a Symfony host. laranail/package-management's
 * SymfonyLoaderAdapter instantiates this (no-arg) and registers it into the container
 * under its FQCN — so consumers resolve `BlogServiceProvider::class` and reach the
 * wired {@see Blog} manager via {@see self::blog()}.
 *
 * Swap the in-memory repository for a Doctrine-backed one, or register richer services
 * with a compiler pass, as your app grows.
 */
final class BlogServiceProvider
{
    private readonly Blog $blog;

    public function __construct()
    {
        $this->blog = new Blog(new InMemoryPostRepository);
    }

    public function blog(): Blog
    {
        return $this->blog;
    }
}
