# Changelog

All notable changes to the Blog package are documented here. The format is based
on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Three asset bundles** — Tailwind v4 (CSS-first `@theme`), Bootstrap 5 (`_variables.scss` overrides)
  and a framework-agnostic **vanilla** CSS bundle, all selectable via `config('modules.blog.ui.framework')`.
- **Validation rule classes** — `NotSubmittedTooQuickly`, `ValidTagList`, `NotReservedSlug`, plus shared
  `ProvidesPostRules`/`ProvidesCategoryRules` traits; all field limits live in `config('modules.blog.validation')`.
- **Runtime extensibility** — reshape the package from a consumer provider with no fork:
  `Macroable` `Blog` manager + fluent DSL (`pipe()`, `extendSearch()`, `searchUsing()`); a
  model-layer **body-processing pipeline** (`BodyProcessor` + stages, `blog.body.stages` tag);
  predictable container **decoration** of services/repository; a pluggable **search `Manager`**
  (`database`/`scout`/custom drivers); a full set of **lifecycle events** (post/comment/category/
  tag created/updated/deleted + published/unpublished/approved); `Blog::spy()` as the test seam.
<!-- @artifact:start plugins -->
- **Optional admin panels** — guarded **Filament** (`BlogPlugin` + resources) and **Nova**
  (resources + tool) adapters over the same core; headless when absent.
<!-- @artifact:end plugins -->
- **Useful features** — opt-in **Markdown** rendering on display (source-preserving), **view
  counts** + `popular_by`, **featured/pinned** posts, and an opt-in event-busted **caching** repository.
- **Multipurpose template** — usable as a module, package or plugin; a full
  design/architecture specification (`docs/architecture.md`).
- **Standard features**: SEO meta + `<x-modules-blog::meta>` head component (OpenGraph/JSON-LD),
  featured image, reading time, related posts, RSS/Atom feed + sitemap, and tags (model + pivot).
- **Restylable + classless components**: every component forwards `class`/`id`/`style`;
  classless `.blade.php` components resolve under the prefix; standalone section components
  (`posts/post/comments/comment-form`); auth components (`auth-links`/`login-prompt`);
  composer-backed sidebar partials; a configurable parent layout (`config('modules.blog.ui.layout')`).
- **API hardening**: opt-in Sanctum token abilities and write-time HTML sanitization.
- Layered blog domain: `Post`, `Category`, `Comment` with Actions, the `Blog`
  manager, a repository + contract, and the `Blog` facade.
- `PostStatus` enum, `PostObserver` (single source of `PostPublished`), policies
  and authorization gates.
- **Full REST API** under `/api/v1` (posts with filtering + publish/unpublish/
  restore/force, categories, nested comments, ping) with config-driven middleware.
- **Full Artisan CLI** built on `laranail/console` (install, post/category/comment
  management, stats, scheduled publishing).
- Namespaced **Blade components** (`<x-modules-blog::…>`) and optional **Livewire 4**
  components (`<livewire:modules-blog.post-list />`, `<livewire:modules-blog.comment-form />`).
- Security hardening: policy-backed Form Requests, order-by allow-list, honeypot
  + rate-limited comments, server-side authorship, field-whitelisted resources.
- Package-tools doctor check and an `about` section.
- Tooling (Pint, PHPStan + larastan, Rector), CI workflows and a `docs/` set
  with an OpenAPI spec.

### Changed
- **BREAKING — views & translations vendor-namespaced.** The view/translation namespace is now
  `modules/blog::` (was `blog::`): `view('modules/blog::posts.index')`, `__('modules/blog::blog.posts')`,
  `@include('modules/blog::partials.…')`, and `config('modules.blog.ui.layout')` defaults to
  `modules/blog::layouts.master`. The old `blog::` alias is removed.
- **BREAKING — component prefix is now `modules-blog`.** Blade/Livewire tags are `<x-modules-blog::…>` /
  `<livewire:modules-blog.…>` (was `some-namespace-path-blog`). Still overridable via
  `config('modules.blog.components.prefix')`.
- **BREAKING — comments & tags are polymorphic.** Comments use a `commentable` morph
  (`commentable_id`/`commentable_type`, replacing `post_id`); tags use a polymorphic `blog_taggables`
  pivot (replacing `blog_post_tag`). A **morph map** stores stable aliases (`blog_post`, …) instead of
  the placeholder FQCN — the package's own models are aliased in code (`Blog::morphMap()`) and always
  registered (config-independent); `config('modules.blog.morph_map')` is for the host's own models.
  The comment API resource now exposes `commentable_id`/`commentable_type` instead of `post_id`. A
  force-deleted post cleans up its comments/tags via `PostObserver::forceDeleted` (morphs have no FK cascade).
- **BREAKING — config namespaced.** Config is now read as `config('modules.blog.*')` (was bare
  `config('blog.*')`) and publishes to `config/modules/blog.php`. `env('BLOG_*')` names are unchanged.
- **BREAKING — `Post` accessors are now attributes.** `$post->readingTime()` → `$post->reading_time`,
  `$post->renderedBody()` → `$post->rendered_body` (Laravel 13 `Attribute` style). `isPublished()`
  stays a method.
- Caching now negatively-caches missing slugs (short `cache.miss_ttl`); the Scout driver gates results
  through the database (`published()->whereKey($ids)`), so future-dated posts never leak.
- Validation extracted to Rule classes + shared traits; no length literals remain in the requests.
- `docs/` reorganised into the laranail two-tier layout (guides at top level, tool pages under `docs/tools/`).
- Re-platformed onto `laranail/package-tools` + `laranail/console`
  (**Laravel 13 / PHP 8.4** floor; Testbench 11).
- Source moved from `app/` to `src/`; config renamed to `config/blog.php`.
- **Root namespace** is now `Some\NamespacePath\Blog` (find-replaceable template token).
- **Layered services architecture**: Services own CRUD/data, Actions call the
  specific service method they need, and the `Blog` manager aggregates ("secretary").
  `BlogService` removed.
- **Component prefix** is now unique, configurable (`config('modules.blog.components.prefix')`),
  normalized, and decoupled from the package's own views.
- **Tailwind CSS v4 + Bootstrap 5** ship as separate Vite bundles, loaded by an
  `<x-…::assets />` component and chosen via `config('modules.blog.ui.framework')`.
- Body sanitization moved from `PostService` to the **model layer** (`BodyProcessor` stage in the
  `saving` observer), so it applies to every writer (facade/API/CLI, admin panels, raw Eloquent);
  it now also strips inline event handlers and `javascript:`/`data:` URLs.
- The `<x-modules-blog::post>` component now renders the (sanitized) body as HTML via `renderedBody()`
  instead of escaping it as plain text — bodies are treated as rich content.
- Services (`Post/Comment/Category/TagService`) are now bound singletons so they can be decorated
  predictably via `app->extend()`.
- Comments now store an optional `email` (gravatar/notify); `?sort=views` and `?featured=1` are
  accepted on the feed.

### Fixed
- **Security — HTML sanitizer hardened.** The save-time body sanitizer now resists obfuscated XSS that
  the previous whitespace-only / literal-string filter missed: `/`-separated event handlers
  (`<a/onclick=…>`), and entity/whitespace-encoded URL schemes (`href="java&#115;cript:…"`). Attributes
  are parsed by name (so a URL value containing `/online=` is no longer mangled).
- **Security — Livewire comment form now matches the HTTP path.** It enforces `comments.allow_guests`
  and the `blog-comments` rate limit (previously a guest could comment when disabled, with no throttle).
- **Security — token-ability gate no longer false-denies.** `EnsureApiAbility` only enforces when the
  request is actually token-authenticated, so a session/web-guard user is no longer wrongly 403'd when an
  ability is configured.
- A **scheduled** post now requires a future `published_at` — previously it could be saved with none and
  was then never published *and* hidden (stranded).
- `featured_image` column widened to match the `url` validation max (was `varchar(255)` vs `max:2048` →
  truncation/`1406` on long CDN URLs); added a composite `(status, published_at)` index for the feed.
- Fixed an N+1 on the post listing (the feed/search now eager-load `category`).
- `popularPosts` ranks/exposes **approved** comment counts only (pending/spam no longer inflate it).
- `PostService::findByKey()` is Postgres-safe (no `id = 'a-slug'` integer cast).
- `blog:comment:approve --all` is now invocable (the `comment` arg was wrongly required) and fires
  `CommentApproved` per comment (busting the cache + running listeners) instead of a silent mass update.
- The publish notification links via the configurable named route, not a hardcoded `/blog/{slug}` path.
- Translations resolve under the vendor namespace `__('modules/blog::blog.…')` — previously the
  bare keys returned untranslated.
- Web routes split into public reads / guest comments vs authenticated author writes
  (`routes.web.middleware` is now the public base; `routes.web.auth_middleware` guards writes).
- **`?category=<id>` / `?tag=<id>` returned the entire table** — an ungrouped `orWhere` inside
  `whereHas` made the id branch uncorrelated. Now grouped and numeric-guarded (Postgres-safe).
- **API token-ability gate was a silent no-op** — `EnsureApiAbility` read `blog.api.abilities.*`
  but the config lives at `blog.routes.api.abilities.*`. Now reads the correct key.
- **`PostUnpublished` never fired** — the observer compared a cast enum against a raw string.
- **`recordView` fired `PostUpdated` and bumped `updated_at`** on every view (busting the cache and
  polluting sitemap `lastmod`); now a plain counter update with no model events.
- **`is_featured` couldn't be set via web/API** — wired through `PostData` and the form requests.
- JSON-LD now uses `JSON_HEX_TAG` (prevents `</script>` breakout); consumer `pipe()` stages can no
  longer reintroduce unsanitized HTML (sanitize runs last); category/tag changes bust the cache;
  the Scout driver guard also checks for the `Searchable` trait.

### Removed
- Global helper functions (`blog_config`, `blog_table`) — use `config('modules.blog.*')`
  and explicit table names.
- The per-module table prefix mechanism — tables are named `blog_posts`,
  `blog_categories`, `blog_comments`.

See [UPGRADING.md](UPGRADING.md) for migration notes.
