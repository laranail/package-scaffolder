# Feature catalog

Authoritative catalog of the capabilities the scaffolder can generate, derived by
reading the gold-standard blueprint on disk at `/Users/imanimanyara/Downloads/Modules/Blog/`
(service provider, `PostService`/`PostObserver`, models, policies, `config/blog.php`, routes,
migrations, the `Blog` manager/DSL, every feature class, tests, `composer.json`, docs).

The catalog is **data**, not hard-coded in the command: it lives in `config/artifacts.php`
(`core`, `features` with `default`/`requires`/`description`/`sub`, `feature_files`, `feature_deps`,
`plugin_types`, `plugin_files`). The CLI/TUI prompts and the `--features=` flag read from it, so
prompts and flags can't drift from the catalog. Generation prunes a disabled feature's files +
`%marker%` wiring + config keys + deps with no dangling references (verified by the matrix).

## Naming model

- **Artifact** = the package/module/plugin (`Blog` → `{Artifact}`).
- **Primary entity** = the main record (`Post` → `{Entity}`, prompted via `--entity`, default = a
  distinct generic `Item` that must differ from the artifact name — the manager is named after the
  artifact and the model after the entity, so identical names collide).
- **Supporting entities** = `Comment` (one-to-many child), `Category` (grouping), `Tag`
  (many-to-many label). Kept verbatim in every artifact as the three canonical relationship shapes;
  not tokenized, not prompted, not pruned.

## Always-on core (not toggleable)

The runtime-extensibility substrate the blueprint is built on. Removing any of it would require
rewriting `PostService`/`PostObserver`/the models, so it is **core**, not optional.

| Capability | Why core | Key files |
|---|---|---|
| Lifecycle events | `$dispatchesEvents` on models + observers; everything hangs off them | `src/Events/*`, `src/Observers/*` |
| Search manager | `Illuminate\Support\Manager` backing `Blog::search()`/`extendSearch()` | `src/Search/*` |
| Body pipeline | model `saving` runs the `BodyProcessor` stage pipeline | `src/Processing/*` |
| Macroable manager + DSL | `Blog` manager (`Macroable`) + `pipe()`/`extendSearch()`/`searchUsing()` | `src/Blog.php`, `src/Mixins/*` |
| Spy/test seam | facade accessor == the singleton manager (`Blog::spy()`) | `src/Facades/*` |
| Domain model | `Post` + `Comment`/`Category`/`Tag`, actions, repository+contract, policies, DTO, enum | `src/Models`, `src/Actions`, `src/Repositories/Eloquent*`, `src/Policies` (incl. `TagPolicy`), `src/DataTransferObjects`, `src/Enums` |

## Optional features (toggleable; `config('artifacts.features')`)

All default **on** — a plain `make:artifact` reproduces the full gold-standard blueprint, and you
opt **out** via `--features=` (the selected subset). `livewire` is the one dependency edge
(`requires: web-ui`) and pulls `web-ui` in automatically if selected alone.

| Key | Default | Requires | Classification | Description |
|---|---|---|---|---|
| `web-ui` | on | — | optional-default-on | Blade components, views, web controllers + routes |
| `livewire` (sub of web-ui) | on | `web-ui` | optional-default-on | Livewire components (post list, comment form) |
| `rest-api` | on | — | optional-default-on | JSON API controllers, resources, ability middleware, `routes/api.php` |
| `caching` | on | — | optional-default-on | Caching repository decorator + event-driven invalidation |
| `feeds` | on | — | optional-default-on | RSS/Atom feed + XML sitemap |
| `scheduling` | on | — | optional-default-on | Scheduled-publish command + job |
| `asset-pipeline` | on | — | optional-default-on | Vite (tailwind/bootstrap/vanilla) build pipeline + `<x-…::assets>` |
| `notifications` | on | — | optional-default-on | Published-post notification listener (a sub-toggle of core events) |

### Per-feature footprint (pruned when off)

From `config/artifacts.php` `feature_files` + `feature_deps` + the `%marker%` wiring:

- **web-ui** — `src/Http/Controllers/{Blog,Comment}Controller.php`, `src/View/Components/`,
  `resources/views/{components,layouts,partials,posts}/`, web tests, components/partials docs; provider
  `hasViews`-adjacent component/composer wiring; web route group.
- **livewire** — `src/Livewire/`, `resources/views/livewire/`, `LivewireTest`, livewire docs; provider
  livewire registration block; `livewire/livewire` dep.
- **rest-api** — `src/Http/Controllers/Api/`, `src/Http/Resources/`, `EnsureApiAbility`,
  `IndexPostRequest`, `routes/api.php`, api tests, `rest-api`/`openapi` docs; provider `hasRoute('api')`
  + `blog.ability` alias; `config blog.routes.api`; `laravel/sanctum` suggest.
- **caching** — `CachingPostRepository`, `FlushBlogCache`, `CachingTest`; provider cache-decoration
  block; `config blog.cache`.
- **feeds** — `FeedController`, `resources/views/feed/`, `PostFeedTest`; feed routes; `config blog.features`.
- **scheduling** — `PublishScheduledPostsCommand`, `PublishScheduledPosts` job, `SchedulingTest`;
  provider command + `registerSchedule()`; `config blog.scheduling`.
- **asset-pipeline** — `vite.config.js`, `tailwind.config.js`, `package.json`, `resources/assets/`,
  `AssetsComponentTest`, assets docs; provider `hasAssets`/`publishAssets`; `config blog.ui.framework`+`ui.assets`.
  (The `Assets` component + `assets.blade.php` stay as a graceful no-op so web-ui views don't dangle.)
- **notifications** — `SendPostPublishedNotification`, `PostPublishedNotification`; provider
  `Event::listen(PostPublished…)`; `config blog.notifications`.

## Panel dimension (separate, mutually exclusive)

`nova` · `filament` · `none` — a single mutually-exclusive choice for **any** shape (`--plugin=`),
default `none`. `nova`/`filament` scaffold only that adapter (guarded by `class_exists`) in its own
provider + dep; `none` emits **zero** Nova/Filament code, stubs, deps, providers, or panel docs/tests,
and — per D1 — strips even the panel-named comments. Files: `plugin_files` in `config/artifacts.php`.

## Recently changed by the latest blueprint refactor

- **Asset pipeline overhaul**: `Assets.php` rewritten (compiled vs `live`/HMR modes); `config ui.assets`
  is now `build_directory` + `live` (removed `base`/`manifest`/`bundles`); added `tailwind.config.js`;
  base/vanilla entry renamed `app.{js,scss}` → `blog.{js,scss}` (tokenized to `{artifact}.{js,scss}`).
- Refinements (no toggle change): `PostStatus` (dependency-light `array_reduce`/`array_column`),
  `SanitizeHtmlStage`, `NotReservedSlug`, `ValidTagList`, `PostData`, `TagPolicy`, `Post` (Str helpers).

---

[← Docs index](README.md#documentation)
