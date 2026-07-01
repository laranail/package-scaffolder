<?php

declare(strict_types=1);

namespace Some\NamespacePath\Blog\Tests\Fixtures;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Some\NamespacePath\Blog\Search\SearchDriver;

/**
 * A custom search driver used in tests to prove the SearchManager extend() seam.
 * Returns a sentinel total (42) so a test can confirm it was the one used.
 */
class InMemorySearchDriver implements SearchDriver
{
    public function search(array $filters, int $perPage = 15, bool $onlyPublished = true): LengthAwarePaginator
    {
        return new Paginator([], 42, $perPage);
    }
}
