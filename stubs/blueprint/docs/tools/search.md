# Search

Post search is pluggable via an `Illuminate\Support\Manager` (`SearchManager`). The active
driver is `config('modules.blog.search.driver')` (default `database`).

## Drivers

| Driver | Notes |
| --- | --- |
| `database` (default) | The built-in LIKE/scope search via the repository — zero setup, zero behaviour change. |
| `scout` | Full-text via [Laravel Scout](https://laravel.com/docs/scout). Requires `laravel/scout` + the `Searchable` trait on the Post model (and indexing `status`). |
| *custom* | Any `Some\NamespacePath\Blog\Search\SearchDriver` you register. |

A driver implements:

```php
interface SearchDriver
{
    public function search(array $filters, int $perPage = 15, bool $onlyPublished = true): LengthAwarePaginator;
}
```

## Enabling Scout

```bash
composer require laravel/scout
```

```php
use Laravel\Scout\Searchable;

class Post extends Model
{
    use Searchable;

    public function toSearchableArray(): array
    {
        return ['title' => $this->title, 'excerpt' => $this->excerpt, 'status' => $this->status->value];
    }
}
```

```php
config(['modules.blog.search.driver' => 'scout']); // or BLOG_SEARCH_DRIVER=scout
```

> **How the `published_at` gate is enforced:** the Scout driver uses the engine only for
> relevance-ranked *keys*, then re-queries the database (`Post::query()->published()->whereKey($ids)`)
> so the authoritative gate (`status` **and** `published_at <= now`) and the package's
> category/tag/featured filters always apply — a future-dated "published" post never leaks. Scout's
> relevance order is preserved (MySQL `FIELD()`) unless you pass an explicit `sort`; the key set is
> capped (1000) to bound the `IN()` query.

## A custom driver

```php
// consumer provider boot()
Blog::extendSearch('meilisearch', fn ($app) => new \App\Blog\MeilisearchDriver($app->make(MeiliClient::class)));
config(['modules.blog.search.driver' => 'meilisearch']);
```

All searches (`Blog::search(...)`, the REST `GET /api/v1/posts?search=`, the feed) flow through
the active driver.
