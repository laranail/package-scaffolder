# Extending & decorating at runtime

Reshape the package from **your own service provider** — no fork, no edits to package
source. Everything below is driven through the `Blog` facade or the container.

## A one-chain example

```php
// A consumer's AppServiceProvider::boot()
use Some\NamespacePath\Blog\Facades\Blog;
use App\Blog\RedactSecretsStage;
use App\Blog\MeilisearchDriver;

Blog::macro('trending', fn (int $n = 5) => $this->popularPosts($n)) // add API methods
    ->pipe(RedactSecretsStage::class)                                // add a body stage
    ->extendSearch('meili', fn ($app) => new MeilisearchDriver($app->make('meili')))
    ->searchUsing('meili');
```

## The seams

### 1. Macros / mixins — add methods to the manager

```php
Blog::macro('trending', fn (int $limit = 5) => $this->popularPosts($limit));
Blog::trending(10);

Blog::mixin(new \Some\NamespacePath\Blog\Mixins\BlogMixin()); // several at once
```

`$this` inside a macro is the manager, so macros reuse its services. (Macros are
invisible to IDEs — ship `_ide_helper`/PHPStan stubs if you expose them widely.)

### 2. Body-processing pipeline — transform bodies on save

Bodies pass through ordered stages (an `Illuminate\Pipeline`) at the **model layer**, so
**every** writer (facade, admin panels, raw Eloquent) runs them. The default stage
sanitizes HTML. Add your own three ways:

```php
Blog::pipe(\App\Blog\RedactSecretsStage::class);                 // runtime (closures ok)
// or the container tag:
$this->app->tag([\App\Blog\RedactSecretsStage::class], 'blog.body.stages');
// or config (class-strings only, so config:cache stays serializable):
config(['modules.blog.processing.stages' => [\App\Blog\RedactSecretsStage::class, SanitizeHtmlStage::class]]);
```

A stage is `handle(string $body, Closure $next): string`; one that doesn't call `$next`
short-circuits (veto/replace later stages).

### 3. Container decoration — wrap a service

Services (`PostService`, `CommentService`, `CategoryService`, `TagService`) and the
`PostRepositoryInterface` are bound singletons, so you can decorate them:

```php
$this->app->extend(PostService::class, fn ($service, $app) => new LoggingPostService($service));
```

**Wrap order:** extenders run in registration order — the **last** `extend()` is the
**outermost** wrapper. The shipped `CachingPostRepository` (enable `modules.blog.cache.enabled`)
is the worked example; its cache is busted by the lifecycle events below.

### 4. Search drivers (Manager) — swap the backend

```php
Blog::extendSearch('algolia', fn ($app) => new \App\Blog\AlgoliaDriver($app->make('algolia')));
config(['modules.blog.search.driver' => 'algolia']); // or Blog::searchUsing('algolia')
```

A driver implements `Some\NamespacePath\Blog\Search\SearchDriver`. See [search.md](search.md).

### 5. Lifecycle events — react without decorating

| Event | Fires when |
| --- | --- |
| `PostCreated` / `PostUpdated` / `PostDeleted` / `PostRestored` / `PostForceDeleted` | the matching Eloquent write (any writer) |
| `PostPublished` / `PostUnpublished` | a post enters / leaves the published state |
| `CommentCreated` / `CommentDeleted` | the matching write |
| `CommentApproved` | a comment becomes approved (also fires once on create if auto-approved) |
| `CategorySaved` / `CategoryDeleted`, `TagSaved` / `TagDeleted` | the matching write (`Saved` = created **or** updated) |

```php
Event::listen(\Some\NamespacePath\Blog\Events\PostPublished::class, fn ($e) => Search::index($e->post));
```

## Testing against the package

Use the framework's built-in facade test helpers (no bespoke fake needed) — they swap the
container binding, so both facade calls and injected `Blog $blog` are intercepted:

```php
$spy = Blog::spy();
$this->post('/blog', [...]);
$spy->shouldHaveReceived('create');

// or a full stub:
Blog::partialMock()->shouldReceive('feed')->andReturn(collect());
```

## Notes & anti-patterns

- **Register extensions from a provider's `boot()`** (once), not per-request. Macros and the
  `BodyProcessor`'s runtime stages are process-global — registering them per request leaks/duplicates
  under Octane. (Tests should `Blog::flushMacros()` in `tearDown`.)
- Don't put closures in `config('modules.blog.processing.stages')` — they break `config:cache`. Use
  `Blog::pipe(Closure)` for closures; config takes class-strings only.
- Don't assume a concrete type back from a decorated binding — the last `extend()` wins.
- Don't subclass the manager to add methods — macro it.
- Caching is opt-in and **version-key** based (portable across all stores); missing slugs are
  negatively cached under a short `cache.miss_ttl`, so a flood of bogus slugs can't repeatedly hit the DB.
