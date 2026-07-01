<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Providers;

use Illuminate\Support\ServiceProvider;
use Some\NamespacePath\Blog\Blog;
use Some\NamespacePath\Blog\Contracts\PostRepositoryInterface;
use Some\NamespacePath\Blog\Repositories\InMemoryPostRepository;

/**
 * Registers the package with a Lumen (or Laravel) container. In Lumen, register it
 * in bootstrap/app.php via `$app->register(...)` (no package auto-discovery).
 */
final class BlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PostRepositoryInterface::class, InMemoryPostRepository::class);

        $this->app->singleton(Blog::class, static fn ($app): Blog => new Blog(
            $app->make(PostRepositoryInterface::class),
        ));
    }
}
