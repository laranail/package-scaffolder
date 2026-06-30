<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Search;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Manager;
use RuntimeException;
use Some\NamespacePath\Blog\Contracts\PostRepositoryInterface;
use Some\NamespacePath\Blog\Models\Post;
use Some\NamespacePath\Blog\Search\Drivers\DatabaseSearchDriver;
use Some\NamespacePath\Blog\Search\Drivers\ScoutSearchDriver;

/**
 * Driver registry for post search (Laravel's {@see Manager}). The default
 * `database` driver wraps the existing repository search (zero behaviour change);
 * an optional `scout` driver layers full-text on top. Consumers add drivers via
 * the inherited `extend()` seam (exposed as `Blog::extendSearch()`).
 */
class SearchManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return (string) $this->config->get('modules.blog.search.driver', 'database');
    }

    /**
     * Search via the active driver. Explicit (rather than Manager's __call proxy)
     * so the return type is known without resolving the driver.
     *
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters, int $perPage = 15, bool $onlyPublished = true): LengthAwarePaginator
    {
        $driver = $this->driver();

        if (! $driver instanceof SearchDriver) {
            throw new RuntimeException('The configured blog search driver must implement '.SearchDriver::class.'.');
        }

        return $driver->search($filters, $perPage, $onlyPublished);
    }

    protected function createDatabaseDriver(): SearchDriver
    {
        return new DatabaseSearchDriver($this->container->make(PostRepositoryInterface::class));
    }

    protected function createScoutDriver(): SearchDriver
    {
        // Scout is an optional dependency (string-guarded, never hard-referenced),
        // and the Post model must carry the Searchable trait (adds the search() method).
        if (! class_exists('Laravel\\Scout\\EngineManager') || ! method_exists(Post::class, 'search')) {
            throw new RuntimeException(
                "modules.blog.search.driver='scout' requires laravel/scout and the Searchable trait on the Post model.",
            );
        }

        return new ScoutSearchDriver($this->container->make(PostRepositoryInterface::class));
    }
}
