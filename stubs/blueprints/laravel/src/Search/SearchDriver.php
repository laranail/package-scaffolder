<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Search;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * A pluggable post-search backend. The package ships a `database` driver (the
 * default LIKE/scope search) and an optional `scout` driver; consumers register
 * their own with `Blog::extendSearch('name', fn ($app) => new MyDriver(...))`.
 */
interface SearchDriver
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters, int $perPage = 15, bool $onlyPublished = true): LengthAwarePaginator;
}
