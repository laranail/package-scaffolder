---
name: laranail-modules-development
description: "Use for any task involving laranail/package-scaffolder (the laranail module/package/plugin scaffolder). Activate when the user mentions make:artifact, laranail::package-scaffolder, the blueprint, generating a module/package/plugin, the feature catalog, modules, Modules/ directory, module:make, module:enable/disable/migrate, module.json, the Module facade, or modular Laravel architecture. Covers: blueprint-based generation via make:artifact (artifact types, the nova|filament|none panel, the config-driven feature catalog, the artifact-vs-entity naming model), all module:make-* per-file generators, module management commands, per-module migrations/seeding, publishing, namespaces/view references, service-provider registration, Blade directives, Inertia support, auto-discovery, inter-module events, and testing. Do not use for non-modular Laravel features or unrelated package development."
license: MIT
metadata:
  author: Simtabi LLC
---

# Laravel Modules Development

laranail/package-scaffolder organises a large Laravel application into self-contained feature bundles (modules) under a `Modules/` directory. Each module has its own controllers, models, migrations, routes, views, and service providers — like a mini Laravel package inside your app.

Best practices for laranail/package-scaffolder, prioritised by impact. For exact API syntax, use `search-docs`.

## Consistency First

Before applying any pattern, check what the application already does. If modules exist in the codebase, follow their structure. Don't invent a second convention.

This is the **laranail/package-scaffolder** fork: Artisan commands are namespaced
`laranail::package-scaffolder.*` with the upstream `module:*` names kept as aliases. Beyond the
upstream per-file generators it adds **blueprint-based generation** — `make:artifact` — which
scaffolds a complete, opinionated artifact from a gold-standard template.

## Quick Reference

### 0. Blueprint generation (`make:artifact`) → `rules/artifacts.md`

- `php artisan make:artifact Blog --type=package|module|plugin` (alias of `laranail::package-scaffolder.new`)
  generates a **full `laranail/package-tools` package** (manager/DSL, services/actions, repository+
  contract, search manager, body pipeline, lifecycle events, policies, REST API, web UI, …) — not an
  empty shell. Use `module:make-*` to add classes into an existing artifact.
- Output: `platform/{packages,modules,plugins}/{Name}`; the folder is location-only, the PSR-4 root is
  `--namespace`. Artifact name (`Blog`→`{Name}`) is distinct from the primary entity (`Post`→`--entity`,
  default = distinct generic `Item`, must differ from the artifact); `Comment`/`Category`/`Tag` are fixed supporting entities.
- Panel: `--plugin=nova|filament|none` (mutually exclusive; default `none` = zero footprint).
- Features are config-driven (`config('artifacts.features')` / `FEATURE_CATALOG.md`), opt-in/opt-out via
  `--features=`; off ⇒ not generated; dependencies auto-resolved (`livewire` requires `web-ui`).

### 1. Creating & Structuring Modules → `rules/architecture.md`

- Scaffold: `php artisan module:make Blog`
- Standard structure: `app/`, `config/`, `database/`, `resources/`, `routes/`, `tests/`, `module.json`, `composer.json`
- `module.json` controls name, alias, active state, load order, and which service providers to register
- Namespaces: `Modules\{StudlyName}` — views: `{lower}::{path}` — config: `{lower}.{key}` — lang: `{lower}::{file}.{key}`
- Two service providers per module: `ModuleServiceProvider` (boot) and `RouteServiceProvider` (routes)
- Providers auto-registered only when the module is **enabled** (`active: 1`)

### 2. All Generators → `rules/generators.md`

- Controllers, models, migrations, requests, resources, policies
- Events, listeners, observers, jobs, mail, notifications
- Commands, providers, middleware, factories, seeders, tests
- Services, repositories, actions, interfaces, traits, enums, casts
- Inertia pages and components

All generators follow: `php artisan module:make-{type} {Name} {ModuleName}`

### 3. Configuration → `rules/configuration.md`

- `config/modules.php`: namespace, paths, stubs, auto-discover, activators, Inertia frontend
- `auto-discover.migrations`: auto-register module migration paths (default: true)
- `auto-discover.translations`: auto-register module lang namespaces (default: false)
- Activator stores enabled/disabled state in `modules_statuses.json`

### 4. Architecture & Inter-Module Communication → `rules/architecture.md`

- Never import classes from another module directly — use events/listeners
- Shared cross-cutting code belongs in a `Core` or `Shared` module
- `@module('Blog')` ... `@endmodule` Blade directive — renders only when the named module is enabled
- `Module` facade: `Module::all()`, `Module::find()`, `Module::isEnabled()`
- Helpers: `module_path('Blog', 'app/')`, `module_vite('Blog', 'resources/js/app.js')`

### 5. Testing → `rules/testing.md`

- Tests live inside `Modules/{Name}/tests/Feature/` and `tests/Unit/`
- Generate: `php artisan module:make-test PostTest Blog`
- Run module tests: `php artisan test --compact Modules/Blog/tests/`
- Use standard Pest syntax with `RefreshDatabase` trait

## Creating a Module

```bash
php artisan module:make Blog
php artisan module:make Blog --plain
php artisan module:make Blog --api
php artisan module:make Blog --inertia
php artisan module:make Blog User Shop
```

## Module Management

```bash
php artisan module:list
php artisan module:enable Blog
php artisan module:disable Blog
php artisan module:delete Blog
php artisan module:use Blog
php artisan module:unuse
```

## Database Operations

```bash
php artisan module:migrate Blog
php artisan module:migrate-fresh Blog
php artisan module:migrate-rollback Blog
php artisan module:migrate-refresh Blog
php artisan module:migrate-reset Blog
php artisan module:migrate-status Blog
php artisan module:seed Blog
```

## Publishing

```bash
php artisan module:publish Blog
php artisan module:publish-config Blog
php artisan module:publish-migration Blog
php artisan module:publish-translation Blog
php artisan module:publish-inertia
```

## Naming Conventions

| Resource | Pattern | Example |
|---|---|---|
| PHP Namespace | `Modules\{Studly}` | `Modules\Blog\Http\Controllers\PostController` |
| View reference | `{lower}::{path}` | `view('blog::posts.index')` |
| Config key | `{lower}.{key}` | `config('blog.per_page')` |
| Translation key | `{lower}::{file}.{key}` | `__('blog::messages.welcome')` |
| Route name (convention) | `{lower}.{name}` | `route('blog.posts.index')` |
| Asset path | `modules/{lower}/` | `/modules/blog/js/app.js` |

## module.json

```json
{
    "name": "Blog",
    "alias": "blog",
    "description": "",
    "active": 1,
    "order": 1,
    "providers": [
        "Modules\\Blog\\Providers\\BlogServiceProvider",
        "Modules\\Blog\\Providers\\RouteServiceProvider"
    ],
    "aliases": {},
    "files": []
}
```

- `active`: 1 = enabled, 0 = disabled — disabled modules' providers are never registered
- `order`: load priority (lower loads first); use when one module depends on another
- `providers`: **must** list every service provider to register, or routes/bindings silently won't load
- `files`: additional PHP files to autoload on boot

## Service Providers

```php
// Modules/Blog/app/Providers/BlogServiceProvider.php
public function boot(): void
{
    $this->loadMigrationsFrom(module_path('Blog', 'database/migrations'));
    $this->loadViewsFrom(module_path('Blog', 'resources/views'), 'blog');
    $this->loadTranslationsFrom(module_path('Blog', 'resources/lang'), 'blog');
    $this->mergeConfigFrom(module_path('Blog', 'config/config.php'), 'blog');
}
```

When `auto-discover.migrations` is `true` in config, `loadMigrationsFrom()` is optional.

## Module Facade & Helpers

```php
use Simtabi\Laranail\Package\Scaffolder\Facades\Module;

Module::all();                    // Collection of all modules
Module::allEnabled();             // Only enabled modules
Module::find('Blog');             // Module instance or null
Module::findOrFail('Blog');       // Module instance or exception
Module::isEnabled('Blog');        // bool
Module::getModulePath('Blog');    // Absolute path to Modules/Blog/

// Helpers
module_path('Blog', 'app/Http/Controllers');  // Full path inside module
module_vite('Blog', 'resources/js/app.js');   // Vite asset helper
```

## Blade Directives

```blade
@module('Blog')
    <a href="{{ route('blog.posts.index') }}">Blog</a>
@endmodule
```

## Inter-Module Communication

Never import classes from another module directly — this creates hard coupling that breaks when a module is disabled.

```php
// Fire from the Blog module
event(new \Modules\Blog\Events\PostPublished($post));

// Listen in another module's EventServiceProvider
protected $listen = [
    \Modules\Blog\Events\PostPublished::class => [
        \Modules\Notifications\Listeners\NotifySubscribers::class,
    ],
];
```

## Common Pitfalls

- **Provider missing from module.json**: Routes, bindings, and migrations silently don't load. Always list every provider under `providers`.
- **Disabled module 404**: Routes from a disabled module return 404. Run `module:enable` first.
- **Auto-discovery off**: If `auto-discover.migrations` is `false`, you must call `loadMigrationsFrom()` manually.
- **Wrong namespace casing**: Must be StudlyCase — `Modules\Blog`, not `Modules\blog` or `modules\Blog`.
- **Cross-module class imports**: Importing `Modules\OtherModule\...` directly causes fatal errors when that module is disabled. Use events.
- **Stale module manifest**: After adding/removing modules, run `php artisan optimize:clear`.
- **Publishing conflicts**: `module:publish-migration` copies to `database/migrations/` — check for timestamp clashes.

## How to Apply

1. Identify the task type and read the relevant reference file (generators, configuration, architecture, or testing)
2. Check existing modules in the codebase for established patterns — follow those first
3. Use `search-docs` for version-specific API details
