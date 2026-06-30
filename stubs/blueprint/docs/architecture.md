# Architecture & Design Specification

> A **multipurpose scaffolding template**: the same codebase runs as a Composer
> **package**, an nWidart **module**, or a drop-in **plugin** — chosen by the host,
> not the code.

## 1. Purpose & principles

A security-first blogging domain you scaffold from, not a black box. Design tenets:

- **DRY** — one source of truth per concern: markup lives in components (pages reuse
  them); data lives in services (HTTP/CLI/Livewire all go through the manager).
- **SOLID** — thin HTTP/CLI layers; single-responsibility Actions; services own data;
  the manager only aggregates; persistence behind a contract for posts.
- **KISS** — no upload pipelines, no needless abstraction; opt-in hardening; minimal
  config that degrades to sane defaults.
- **Embeddable** — every UI piece is a restylable, layout-agnostic component/partial.
- **Decoupled** — the PHP namespace, folder path, component prefix, layout, routes and
  auth route names are all independent and configurable.

## 2. Usage modes (module · package · plugin)

| Mode | Discovery | Notes |
| --- | --- | --- |
| **Package** | `composer require` → Laravel auto-discovery (`extra.laravel.providers` + `aliases`) | Standard install; `php artisan blog:install`. |
| **Module** | Dropped under a host modules dir; `module.json` + the same provider | nWidart-compatible; loaded by the module manager. |
| **Plugin** | Manual `BlogServiceProvider` registration | Everything (routes/views/layout/prefix/auth/assets) is config-driven, so it embeds without owning the app. |

Nothing in the code assumes a mode: migrations/routes/views/config load identically.
The root **PHP namespace (`Some\NamespacePath\Blog`)** is decoupled from the lowercase
**path (`src/`)** and the **composer name (`modules/blog`)** via PSR-4 — rename the
namespace with one find-replace when cloning.

## 3. Layered architecture

```
HTTP / CLI / Livewire
        │  (thin — validate + authorize, no business logic)
        ▼
   Blog  manager  ── the "secretary": the single public entry (facade target)
        │  writes ─→ Actions ─→ Services ─→ Repository ─→ Model
        │  reads  ──────────────→ Services ─→ Repository ─→ Model
        ▼
   PostObserver → PostPublished (event, single source) → Listener → Notification
```

| Layer | Responsibility |
| --- | --- |
| **Repository** (`Contracts\PostRepositoryInterface` → `EloquentPostRepository`) | Raw post persistence behind a contract. |
| **Services** (`PostService`, `CommentService`, `CategoryService`, `TagService`) | All CRUD + data processing, widgets, write-time HTML sanitization, tag sync. |
| **Actions** (`Create/Update/Delete/Publish/UnpublishPostAction`, `CreateCommentAction`) | One use-case each; call the specific service method inside a transaction. |
| **Manager** (`Blog`) | Aggregates the public surface; routes writes→Actions, reads→Services; no logic of its own. |
| **Facade** (`Facades\Blog`) | Static sugar over the manager singleton. |
| **HTTP** | Web (`BlogController`, `CommentController`, `FeedController`) + REST (`Api\*`); Form Requests own validation/authorization; API Resources whitelist output. |
| **CLI** | `blog:*` commands (laranail/console) delegate to the manager. |
| **Observers / Policies / Gates / Events / Listeners / Notifications** | Lifecycle invariants, authorization, and the single `PostPublished` fan-out. |
| **DTO** (`PostData`) | Immutable, partial-update-friendly transport between layers. |
| **Middleware** | `blog.published` (hide drafts → 404), `blog.ability` (opt-in token-ability gate). |
| **Doctor** (`BlogDoctorCheck`) | Health surfaced by `php artisan laranail::package-tools.doctor`. |

**Write path:** `Request → PostData → Blog::create → CreatePostAction → PostService::create
(sanitize + tag sync) → Repository → Model`. **Read path:** `Blog::feed/search → PostService
→ Repository`. The `PostPublished` event is emitted **only** by the observer, so it fires
exactly once however a post becomes published.

### Polymorphism & a morph map

Two relationships are **polymorphic**, so the package's data can attach to a post *or* any host model:

- **Comments** are `commentable` (`commentable_id` + `commentable_type`) — `Post::comments()` is a
  `morphMany`, `Comment::commentable()` a `morphTo`.
- **Tags** are `taggable` via the `blog_taggables` pivot — `Post::tags()` is a `morphToMany`,
  `Tag::posts()` a `morphedByMany`.

A **morph map** stores stable aliases (`blog_post`, `blog_comment`, …) in the DB instead of the FQCN —
essential because the root namespace `Some\NamespacePath\Blog` is a find-replaceable placeholder. The
package's own aliases are **code-canonical** (`Blog::morphMap()`) and registered *last* in
`Relation::morphMap`, so they hold regardless of config — a host can't make the package store a
placeholder FQCN by clearing or overriding the map. `config('modules.blog.morph_map')` is purely for the
host's **own** commentable/taggable models (non-enforcing — an unregistered host model simply stores its
own, already-stable, FQCN). Morph columns have no FK cascade, so a force-deleted post cleans up its
comments/tags in `PostObserver::forceDeleted`.

### Vendor namespacing

Config (`config('modules.blog.*')`), views/translations (`view('modules/blog::…')`,
`__('modules/blog::blog.…')`) and the component prefix (the slug `modules-blog`, since Blade/Livewire
tags can't contain `/`) are all namespaced under the `modules/blog` vendor — no global `blog`-keyed state
to clash with the host or other packages.

## 4. Presentation system

- **Components are restylable**: each forwards host `class`/`id`/`style`/attrs via
  `$attributes->merge()` (overridable defaults).
- **Unique, configurable prefix**: `config('modules.blog.components.prefix')` (normalized slug,
  rename-safe) registered via `Blade::componentNamespace` (class) **and**
  `Blade::anonymousComponentPath` (classless) — so `<x-{prefix}::name>` works for both
  class components and plain `.blade.php` files (classes win their names; anonymous is the fallback).
- **Layout-agnostic**: `config('modules.blog.ui.layout')` (host overrides) + standalone **section
  components** (`<x-modules-blog::posts|post|comments|comment-form>`); the package's pages are thin
  wrappers rendering the same components (single source of markup).
- **Shared view helpers**: a `modules/blog::*` view composer injects `$blogComponentPrefix`,
  `$blogLayout` and a `$blogRoute(name, params)` resolver (`Route::has`-guarded), so no view
  hardcodes a prefix, layout or route name.
- **Composer-backed partials**: a declarative map registers `View::composer`s that auto-inject
  data (`categories`, `recent-posts`, `popular-posts`, `archive`, `tags`) — hosts just `@include`.
- **Auth components**: `auth-links`, `login-prompt`, and an auth-aware `comment-form`, using
  configurable host auth route names.
- **Assets**: an `<x-modules-blog::assets />` component reads the Vite manifest and emits the active
  framework bundle (`config('modules.blog.ui.framework')` → tailwind/bootstrap/none).

## 5. Configuration surface

`config/blog.php`: `user_model`, `components.prefix`, `pagination`, `comments` (guests/
honeypot/auto-approve), `notifications`, `scheduling`, `routes` (web/api enable/prefix/
middleware/`auth_middleware`/`abilities`), `rate_limiting`, `security` (`sanitize_html`/
`allowed_tags`), `features` (rss/sitemap/feed_limit/related_limit), `ui` (default_status,
sortable allow-list, framework, assets, **layout**, **routes** map, **auth** route names).
See [configuration.md](configuration.md).

## 6. Security model

Policy-backed Form Requests on every write; order-by allow-list (anti-SQLi); honeypot +
min-submit-time + rate-limited comments; server-side authorship (`author_id`/`approved`
never from input); field-whitelisted API resources; drafts hidden (404); write-time HTML
sanitization; opt-in Sanctum token abilities. See [security.md](security.md).

## 7. Extension points (runtime, no fork)

A consumer reshapes the package from their own provider — see [extending.md](tools/extending.md):

- **Macros / mixins** on the `Blog` manager (it `use`s `Macroable`) — add API methods.
- **Body pipeline** — ordered save-time stages (`Blog::pipe()` / `blog.body.stages` tag /
  config), run at the model layer so every writer is covered.
- **Container decoration** — services + the repository are bound singletons; `app->extend()`
  wraps them (worked example: the event-busted `CachingPostRepository`).
- **Search drivers** — `SearchManager` (Laravel `Manager`): `database` default, optional
  `scout`, or `Blog::extendSearch()` for your own. See [search.md](tools/search.md).
- **Lifecycle events** — a full set (post/comment/category/tag created/updated/deleted +
  published/unpublished/approved transitions), via `$dispatchesEvents` + observers.
- **Admin panels** — optional, guarded Filament + Nova adapters over the same core. See [panels.md](tools/panels.md).
- Still available: swap the repository binding; subclass a Service/Action; add a composer-backed
  partial or a classless component; override layout/prefix/routes/auth via config; add a `DoctorCheck`.

**Testing seam:** the facade's built-in `Blog::spy()` / `Blog::partialMock()` / `Blog::swap()`.

## 8. Directory tree

```
src/{Actions,Console,Contracts,DataTransferObjects,Doctor,Enums,Events,Exceptions,
     Facades,Http/{Controllers,Middleware,Requests,Resources},Jobs,Listeners,Livewire,
     Models,Notifications,Observers,Policies,Providers,Repositories,Services,Traits,
     View/Components}
config/  database/{migrations,factories,seeders}  resources/{assets,lang,views}  routes/  tests/  docs/
```

## 9. Testing strategy

Orchestra Testbench + in-memory SQLite; a test User fixture + configurable guard.
Coverage spans the domain (actions/services/observer single-event), REST API
(CRUD/filters/abilities/codes), CLI, components (restyle/classless/section), partials,
auth, SEO/feeds, sanitization and the configurable prefix/layout. PHPStan (level 5 +
larastan) and Pint enforce quality in CI.
