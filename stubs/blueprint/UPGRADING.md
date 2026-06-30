# Upgrading

## Vendor namespacing, polymorphism, config & accessors (latest)

Breaking changes in the refactor pass:

- **Views, translations & components are vendor-namespaced.** The view/translation namespace is now
  `modules/blog::` (was `blog::`) — update `view('modules/blog::…')`, `@include('modules/blog::…')` and
  `__('modules/blog::blog.…')`. The Blade/Livewire component prefix is now **`modules-blog`** (was
  `some-namespace-path-blog`): `<x-modules-blog::post>`, `<livewire:modules-blog.post-list>`. If you
  published the package views or referenced the old namespace/prefix, update those references (the prefix
  is still overridable via `config('modules.blog.components.prefix')`).
- **Comments & tags are now polymorphic.** Comments use a `commentable` morph (`commentable_id` +
  `commentable_type`, replacing `post_id`); tags use a polymorphic `blog_taggables` pivot (replacing
  `blog_post_tag`). A **morph map** stores stable aliases (`blog_post`, …) in the DB instead of the
  placeholder FQCN; the package's own models are aliased in code (`Blog::morphMap()`) and always
  registered, so you only use `config('modules.blog.morph_map')` to register your **own** commentable/
  taggable models. The comment API resource now returns `commentable_id`/`commentable_type` instead of
  `post_id`. On a fresh template just re-run migrations; on an existing install, migrate `post_id` →
  the `commentable_*` columns and `blog_post_tag` → `blog_taggables`.
- **Config is namespaced.** Every lookup is now `config('modules.blog.*')` (was bare `config('blog.*')`),
  and the published file lives at `config/modules/blog.php`. **`env('BLOG_*')` variable names are
  unchanged**, so `.env`-based configuration keeps working as-is.
- **`Post` reading-time / rendered-body are now attributes.** Replace method calls with property access:
  `$post->readingTime()` → `$post->reading_time`, and `$post->renderedBody()` → `$post->rendered_body`.
  (`$post->isPublished()` remains a method.)

Validation limits moved to `config('modules.blog.validation')`, and the `docs/` folder was reorganised
(tool pages now live under `docs/tools/`).

## To the laranail-based release (Laravel 13 / PHP 8.4)

This release re-platforms the package onto
[`laranail/package-tools`](https://packagist.org/packages/laranail/package-tools)
and [`laranail/console`](https://packagist.org/packages/laranail/console).

### Requirements

- **PHP** `^8.4.1 || ^8.5` (was `^8.2`)
- **Laravel** `^13.0` (was `^11`/`^12`)

### Breaking changes

- **Root namespace changed** from `Modules\Blog` to `Some\NamespacePath\Blog`
  (PSR-4 → `src/`). Update your imports accordingly; the lowercase folder path
  and the `modules/blog` composer name are unchanged.
- **Source moved from `app/` to `src/`.**
- **Global helpers removed.** `blog_config()` and `blog_table()` no longer exist.
  Use `config('modules.blog.*')` and the model's own table names instead.
- **Per-module table prefix removed.** Tables are now literally named
  `blog_posts`, `blog_categories` and `blog_comments` (the `database.table_prefix`
  config key is gone). Existing installs should rename their tables or run the
  shipped migrations on a fresh schema.
- **Config file renamed** `config/config.php` → `config/blog.php`, and the key is now
  namespaced — read it as `config('modules.blog.*')` (see the latest section above).
- **`BlogService` was removed.** Use `Some\NamespacePath\Blog\Blog` (the `Blog`
  facade) — the manager that aggregates the new domain services
  (`PostService`/`CommentService`/`CategoryService`).
- The standalone `Route`/`Event`/`Auth` service providers were consolidated into
  `BlogServiceProvider`.
- **Component prefix is now configurable.** Blade/Livewire components use
  `config('modules.blog.components.prefix')` (default `modules-blog`) instead of `blog`.

### New

- A layered service architecture (Services → Actions → `Blog` manager), a full
  REST API under `/api/v1`, a complete Artisan CLI, uniquely-namespaced Blade
  components, and optional Livewire 4 components.
- Standard features (SEO meta, featured image, reading time, related posts, RSS/sitemap,
  tags), restylable + classless components, section components, a configurable layout,
  composer-backed + auth partials, and opt-in API token abilities + HTML sanitization.

### Web routes split (action needed if you customised them)

The web routes are now **public reads + guest comments** vs **authenticated author writes**.
`config('modules.blog.routes.web.middleware')` is the **public base** (default `['web']`); author
writes use the new `routes.web.auth_middleware` (default `['auth']`). If you previously set
`routes.web.middleware` to `['web','auth']`, move `auth` to `auth_middleware`.

### Translation fix

Package translations resolve under the vendor namespace `__('modules/blog::blog.…')`. No action
needed unless you copied the old namespace.

### Runtime extensibility, features & panels (additive)

The runtime-extensibility layer (manager macros, body pipeline, container decoration, search
drivers, lifecycle events), the new features (Markdown, view counts, featured posts, caching),
and the optional Filament/Nova adapters are **additive and off by default** — existing setups
behave exactly as before. See [docs/tools/extending.md](docs/tools/extending.md). Two behaviour notes:

- **Body rendering**: the `<x-modules-blog::post>` component now renders the sanitized body as HTML
  (`renderedBody()`) rather than escaping it as plain text. If you relied on plain-text rendering,
  override the component view. New columns: `views` + `is_featured` on `blog_posts`, and `email`
  (nullable) on `blog_comments` (re-run migrations on a fresh template; add them via a migration on
  existing installs).
- **API ability config key**: the token-ability gate reads `modules.blog.routes.api.abilities.write|moderate`
  (it previously, by mistake, read a non-existent `…api.abilities.*` and was a no-op). If you set
  `BLOG_API_WRITE_ABILITY`/`BLOG_API_MODERATE_ABILITY`, the gate now actually enforces it.
- **Optional deps**: Markdown needs `league/commonmark`, Scout search needs `laravel/scout`,
  and the panels need `filament/filament` / `laravel/nova` — all `suggest`-only and guarded.
