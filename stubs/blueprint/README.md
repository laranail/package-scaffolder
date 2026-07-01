# Blog

[![Tests](https://img.shields.io/badge/tests-passing-brightgreen.svg)](#local-development)
[![Static analysis](https://img.shields.io/badge/PHPStan-level%205-brightgreen.svg)](#local-development)
[![Style](https://img.shields.io/badge/code%20style-pint-orange.svg)](#local-development)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

> **Multipurpose scaffolding template** — the same codebase runs as a **module · package · plugin**.

A security-first blogging domain for Laravel — posts, categories, comments and tags with a
clean layered architecture (Services → Actions → manager), a full **REST API**, a complete
**Artisan CLI**, **restylable** Blade components (class **and** classless), optional
**Livewire 4**, SEO/RSS/sitemap, and dual **Tailwind/Bootstrap** asset bundles. Built on the
spatie-compatible [`laranail/package-tools`](https://packagist.org/packages/laranail/package-tools)
base and [`laranail/console`](https://packagist.org/packages/laranail/console).

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [What you get](#what-you-get)
- [REST API](#rest-api)
- [Artisan commands](#artisan-commands)
- [Components & partials](#components--partials)
- [Extensibility](#extensibility)
- [Admin panels](#admin-panels)
- [Configuration](#configuration)
- [Security](#security)
- [Architecture](#architecture)
- [Documentation](#documentation)
- [Local development](#local-development)
- [Provenance](#provenance)
- [Sister packages](#sister-packages)
- [Contributing & security](#contributing--security)
- [License](#license)

## Requirements

- PHP `^8.4.1 || ^8.5`
- Laravel `^13.0`

## Installation

Use it in whichever shape your project needs — nothing in the code assumes a mode.

| As a… | How |
| --- | --- |
| **Package** | `composer require modules/blog` → auto-discovered, then `php artisan blog:install` |
| **Module** | Drop under your modules dir; `module.json` + the provider load it (module-manager-compatible) |
| **Plugin** | Register `BlogServiceProvider` manually; everything is config-driven and embeds into your app |

```bash
php artisan blog:install     # publish config/assets + run migrations (interactive)
```

The provider auto-discovers and the `Blog` facade registers automatically. The root namespace
`Some\NamespacePath\Blog` is decoupled from the `src/` path and `modules/blog` composer name —
find-replace it when cloning the template.

## Quick start

```php
use Some\NamespacePath\Blog\Facades\Blog;
use Some\NamespacePath\Blog\DataTransferObjects\PostData;

$post = Blog::create(PostData::fromArray([
    'title' => 'Hello World', 'body' => 'My first post.', 'status' => 'published',
    'tags' => ['Laravel'],
])->withAuthor($userId));

Blog::feed();                                  // published, paginated
Blog::search(['tag' => 'laravel', 'sort' => 'title']);
Blog::related($post);  Blog::recentPosts();    // widgets
```

```blade
{{-- Drop the restylable, layout-agnostic components into your own views --}}
<x-modules-blog::posts />
<x-modules-blog::post :post="$post" />
@include('modules/blog::partials.categories')          {{-- data auto-injected --}}
```

## What you get

- **Domain**: posts, categories, comments, tags — soft deletes, scheduling, slugs, reading time.
  **Polymorphic** comments (`commentable`) + tags (`taggable`) with a config-driven morph map, so they
  attach to posts or your own models without storing the placeholder FQCN.
- **Vendor-namespaced** throughout: `config('modules.blog.*')`, `view('modules/blog::…')`,
  `__('modules/blog::…')`, and `modules-blog` component tags — no global `blog`-keyed state to clash.
- **REST API** under `/api/v1` with filtering, soft-delete actions and opt-in token abilities.
- **CLI**: install, post/category/comment/stats management, scheduled publishing.
- **UI**: restylable class **and** classless components, standalone section components, a
  configurable layout, auth + composer-backed partials, and a SEO `<x-modules-blog::meta>` head component.
- **SEO/feeds**: meta/OpenGraph/JSON-LD, RSS/Atom feed, XML sitemap; optional Markdown bodies.
- **Runtime extensibility**: manager macros, a body-processing pipeline, container decoration,
  pluggable search drivers (Scout-ready), lifecycle events, opt-in caching, view counts, featured posts.
<!-- @artifact:start plugins -->
- **Admin panels**: optional, guarded Filament and Nova adapters over the same core.
<!-- @artifact:end plugins -->
- **Assets**: separate Tailwind v4 and Bootstrap 5 Vite bundles, chosen via config.
- **Quality**: security-first validation, doctor check, `about` section, tests, PHPStan, Pint, CI.

## REST API

Versioned `/api/v1`, `api.blog.*` names; public reads, authed writes (config-driven middleware
+ optional Sanctum token abilities). Posts (`apiResource` + `?status=&category=&tag=&search=&sort=`
+ `publish/unpublish/restore/force`), categories, tags, nested comments, `ping`. See
[docs/tools/rest-api.md](docs/tools/rest-api.md) and [docs/tools/openapi.yaml](docs/tools/openapi.yaml).

## Artisan commands

`blog:install`, `blog:stats`, `blog:post:create|list|publish|unpublish|delete`,
`blog:category:create|list`, `blog:comment:list|approve`, `blog:publish-scheduled`. See
[docs/tools/cli.md](docs/tools/cli.md).

## Components & partials

Restylable (forward `class`/`id`/`style`), under a **configurable unique prefix**, both class
and **classless**:

```blade
<x-modules-blog::posts /> <x-modules-blog::post :post="$post" /> <x-modules-blog::comments :post="$post" />
<x-modules-blog::comment-form :post="$post" /> <x-modules-blog::auth-links /> <x-modules-blog::meta :post="$post" />
@include('modules/blog::partials.recent-posts')   @include('modules/blog::partials.tags')
```

See [docs/tools/components.md](docs/tools/components.md) and [docs/tools/partials.md](docs/tools/partials.md).

## Extensibility

Reshape the package at runtime from your **own** provider — no fork, no package edits:

```php
use Some\NamespacePath\Blog\Facades\Blog;

Blog::macro('trending', fn (int $n = 5) => $this->popularPosts($n)) // add API methods
    ->pipe(\App\Blog\RedactSecretsStage::class)                      // add a save-time body stage
    ->extendSearch('meili', fn ($app) => new \App\Blog\MeiliDriver($app->make('meili')));
```

Seams: **macros/mixins** on the manager, a **body-processing pipeline** (model-layer, so every
writer is covered), container **decoration** of services/repository (e.g. the opt-in caching
repository), pluggable **search drivers** (`Manager`), a full set of **lifecycle events**, and
`Blog::spy()` for tests. See [docs/tools/extending.md](docs/tools/extending.md) and [docs/tools/search.md](docs/tools/search.md).

<!-- @artifact:start plugins -->
## Admin panels

Optional, first-class **Filament** and **Nova** adapters that reuse the `Blog` core, each behind
a `class_exists`-guarded provider (the package stays headless until you install one):

```php
$panel->plugin(\Some\NamespacePath\Blog\Filament\BlogPlugin::make());   // Filament
```

See [docs/tools/panels.md](docs/tools/panels.md).
<!-- @artifact:end plugins -->

## Configuration

Everything is config-driven (`config/blog.php`): user model, component prefix, pagination,
comments, scheduling, **routes** (web/api + abilities), security (HTML sanitization),
**features** (rss/sitemap), and **ui** (layout, route map, auth route names, framework,
assets). See [docs/configuration.md](docs/configuration.md).

## Security

Policy-backed Form Requests on every write, order-by allow-list, honeypot + rate-limited
comments, server-side authorship, field-whitelisted resources, draft hiding, write-time HTML
sanitization, and opt-in Sanctum token abilities. See [docs/security.md](docs/security.md).

## Architecture

Layered: **Services (data) ← Actions ← `Blog` manager (the "secretary") ← Facade/Controllers/
CLI/Livewire**, with the presentation system above it. Full design specification in
[docs/architecture.md](docs/architecture.md).

## Documentation

Full docs live in [`docs/`](docs/) — guides at the top level, tool/feature pages under
[`docs/tools/`](docs/tools/). Project meta: [Changelog](CHANGELOG.md) · [Upgrading](UPGRADING.md) ·
[Contributing](CONTRIBUTING.md) · [Security policy](SECURITY.md).

**Guides**

| Page | What it covers |
| --- | --- |
| [Installation](docs/installation.md) | Install as a package, module or plugin. |
| [Configuration](docs/configuration.md) | Every `config('modules.blog.*')` option. |
| [Usage](docs/usage.md) | The `Blog` manager/facade, events, authorization. |
| [Architecture](docs/architecture.md) | Design spec, layers, extension points. |
| [Security](docs/security.md) | The security model + checklist. |

**Tools & features**

| Page | What it covers |
| --- | --- |
| [REST API](docs/tools/rest-api.md) | Endpoints, filters, abilities ([OpenAPI](docs/tools/openapi.yaml)). |
| [CLI](docs/tools/cli.md) | The `blog:*` Artisan commands. |
| [Components](docs/tools/components.md) | Restylable class + classless Blade components. |
| [Partials](docs/tools/partials.md) | Composer-backed + auth partials. |
| [Livewire](docs/tools/livewire.md) | The optional Livewire 4 components. |
| [Assets](docs/tools/assets.md) | The Tailwind / Bootstrap / vanilla bundles. |
| [Features](docs/tools/features.md) | SEO, markdown, tags, views, featured, feeds. |
| [Extending](docs/tools/extending.md) | Macros, pipeline, decoration, events, drivers. |
| [Search](docs/tools/search.md) | Pluggable search drivers (database/scout/custom). |
<!-- @artifact:start plugins -->
| [Panels](docs/tools/panels.md) | Optional Filament + Nova adapters. |
<!-- @artifact:end plugins -->

## Local development

```bash
composer install
composer test        # PHPUnit + Orchestra Testbench
composer analyse     # PHPStan level 5 (+ larastan baseline)
composer format      # Laravel Pint
npm install && npm run build   # Tailwind + Bootstrap bundles
```

## Provenance

The service provider extends the spatie-compatible
[`laranail/package-tools`](https://packagist.org/packages/laranail/package-tools)
`PackageServiceProvider`; the open API (`hasConfigFile()`, lifecycle hooks, …) mirrors
[`spatie/laravel-package-tools`](https://github.com/spatie/laravel-package-tools) (MIT).

## Sister packages

- [`laranail/package-tools`](https://packagist.org/packages/laranail/package-tools) — the fluent package base.
- [`laranail/console`](https://packagist.org/packages/laranail/console) — the rich console toolkit used by the CLI.

## Contributing & security

See [CONTRIBUTING.md](CONTRIBUTING.md), [SECURITY.md](SECURITY.md),
[CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) and [CHANGELOG.md](CHANGELOG.md).

## License

The MIT License (MIT). See [LICENSE](LICENSE).
