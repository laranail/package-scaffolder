# Structure refactor — BEFORE audit

Structural (behavior-preserving) reorg toward "every class type in its own folder that
mirrors its namespace segment." This is the **before** state. No files moved yet.

## PSR-4 / autoload (from `composer.json`)

| Key | Value |
|---|---|
| psr-4 base | `Simtabi\Laranail\Package\Scaffolder\` → `src` |
| files autoload | `helpers/helpers.php` (procedural — stays a function file, not namespaced) |
| exclude-from-classmap | `stubs/blueprints/laravel/` (the vendored artifact **template** — NOT package source; excluded from this refactor) |
| `extra.laravel.providers` | `Simtabi\Laranail\Package\Scaffolder\LaravelModulesServiceProvider` |
| `extra.laravel.aliases` | `Module` → `Simtabi\Laranail\Package\Scaffolder\Facades\Module` |
| PHP | `^8.4.1 || ^8.5` |

Scope note: `stubs/blueprints/laravel/` (240 template files, placeholder namespace
`Some\NamespacePath\Blog`) and `stubs/*.stub` are **template content**, not
package classes — excluded from every count and move below. The tree largely follows classic Laravel module-package conventions.

## Inventory — 129 package classes by top-level group

| Group | Count | Already a type folder? |
|---|---:|---|
| `Commands/` (+ Actions, Database, Make, Publish subfolders) | 72 | yes |
| `Support/` | 12 | yes |
| **(root, no folder)** | **8** | **NO — the reorg target** |
| `Traits/` | 6 | yes |
| `Exceptions/` | 5 | yes |
| `Contracts/` | 5 | yes |
| `Publishing/` | 4 | yes |
| `Process/` | 3 | yes |
| `Generators/` | 3 | yes |
| `Providers/` | 2 | yes |
| `Laravel/` | 2 | framework-variant (not a type) |
| `Lumen/` | 2 | framework-variant (not a type) |
| `Routing/`, `Migrations/`, `Facades/`, `Constants/`, `Activators/` | 1 each | yes |

So the package is already ~94% type-grouped. The refactor is **not** a 129-file shuffle; it is
mainly (a) the 8 root classes and (b) a decision on the framework-variant dirs.

## Problem 1 — root-level classes (namespace = base, no type folder)

Each declares `namespace Simtabi\Laranail\Package\Scaffolder;` and sits at `src/` root:

| File | Kind | Suggested type |
|---|---|---|
| `src/Collection.php` | `class Collection extends BaseCollection` | Support (or Collections) |
| `src/FileRepository.php` | `abstract class FileRepository implements RepositoryInterface` | Repositories |
| `src/Json.php` | `class Json` | Support |
| `src/Module.php` | `abstract class Module` | ambiguous (core entity) — needs decision |
| `src/ModuleManifest.php` | `class ModuleManifest` | Support |
| `src/ModulesServiceProvider.php` | `abstract class ModulesServiceProvider extends ServiceProvider` | Providers |
| `src/LaravelModulesServiceProvider.php` | `class …extends ModulesServiceProvider` | Providers |
| `src/LumenModulesServiceProvider.php` | `class …extends ModulesServiceProvider` | Providers |

## Problem 2 — framework-variant dirs (`Laravel/`, `Lumen/`) are not "types"

- `src/Laravel/LaravelFileRepository.php`, `src/Laravel/Module.php`
- `src/Lumen/LumenFileRepository.php`, `src/Lumen/Module.php`

These group by *framework*, not by *type*. Under the aggressive-type philosophy they'd split
into `Repositories/` (the FileRepository variants) and a Module type folder — but that mixes a
`Module` type folder with three `Module` classes (root abstract + Laravel + Lumen), a naming
collision risk. **Needs a decision** (keep framework dirs, or split by type — see PLAN.md).

## Consumer-facing (breaking) surface

These root classes are **public, importable API**; changing their namespace breaks consumers.
Intra-package import counts (a floor, excludes external consumers):

| Class | intra-package `use` count |
|---|---:|
| `Module` | 16 |
| `FileRepository` | 3 |
| `Collection` | 1 |
| `Json` | 1 |
| `ModuleManifest` | 0 |
| service providers | referenced in `composer.json extra.laravel.providers` + bootstrap |

Also `Module` (abstract) has framework subclasses `Laravel\Module` / `Lumen\Module`, and the
container binds the repository/module contracts — every FQCN string, binding, and the provider
entry in `composer.json` must be updated in lockstep (Phase 3).

## Tooling baseline (must stay green after the move)

- Tests: `vendor/bin/phpunit --no-coverage` → **460 tests** green at audit time.
- Static analysis: `vendor/bin/phpstan analyse` → clean (baseline present).
- Style: `vendor/bin/pint` (laravel preset; `exclude: ["stubs"]`).
- Generated-artifact build matrix: `scripts/verify-artifacts.sh` (network-gated).

## Upstream-divergence caveat

The root classes (`Module`, `FileRepository`,
`Collection`, `Json`, the service providers) match upstream's layout. Relocating them **diverges
from upstream**, making future upstream merges harder. This is a real trade-off to weigh in the
plan/approval, independent of the consumer-breaking-change concern.

---

[← Docs index](../../README.md#documentation)
