<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Search\Drivers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Some\NamespacePath\Blog\Contracts\PostRepositoryInterface;
use Some\NamespacePath\Blog\Search\SearchDriver;

/**
 * The default driver: delegates to the repository's LIKE/scope-based search.
 * No behaviour change from before search became pluggable.
 */
class DatabaseSearchDriver implements SearchDriver
{
    public function __construct(
        private readonly PostRepositoryInterface $posts,
    ) {}

    public function search(array $filters, int $perPage = 15, bool $onlyPublished = true): LengthAwarePaginator
    {
        return $this->posts->search($filters, $perPage, $onlyPublished);
    }
}
