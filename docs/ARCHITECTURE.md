# Architecture — source layout

The package follows a strict **type-folder** layout: every class lives in a folder
whose path mirrors its namespace segment, so any class is locatable by its type.

PSR-4 base: `Simtabi\Laranail\Package\Scaffolder\` → `src/` (`composer.json`). Each
type folder appends one segment to that base.

## Type → folder → namespace

| Type | Folder | Namespace | Notes |
|---|---|---|---|
| Artisan commands | `src/Commands/` (`Actions/`, `Database/`, `Make/`, `Publish/`) | `…\Commands\…` | Command tree. `Commands/stubs/` is template content, **not** package classes (excluded from classmap). |
| Support / utilities | `src/Support/` | `…\Support` | Incl. `Collection`, `Json`, `Module`, `ModuleManifest`, and `Support\Artifacts\*` (the artifact generator). |
| Repositories | `src/Repositories/` | `…\Repositories` | `FileRepository` (the abstract module repository). |
| Service providers | `src/Providers/` | `…\Providers` | ALL providers: `ModulesServiceProvider` (abstract), `LaravelModulesServiceProvider` (the published/auto-discovered one), `LumenModulesServiceProvider`, `ModuleServiceProvider` (abstract base that generated module providers extend), `ConsoleServiceProvider`, `ContractsServiceProvider`. |
| Contracts | `src/Contracts/` | `…\Contracts` | Interfaces (`RepositoryInterface`, `ActivatorInterface`, …). |
| Traits | `src/Traits/` | `…\Traits` | Reusable behaviour. |
| Exceptions | `src/Exceptions/` | `…\Exceptions` | |
| Generators | `src/Generators/` | `…\Generators` | |
| Publishing | `src/Publishing/` | `…\Publishing` | |
| Process | `src/Process/` | `…\Process` | Installer/Updater. |
| Routing | `src/Routing/` | `…\Routing` | |
| Migrations | `src/Migrations/` | `…\Migrations` | |
| Facades | `src/Facades/` | `…\Facades` | `Module` facade (alias `Module`). |
| Constants | `src/Constants/` | `…\Constants` | |
| Activators | `src/Activators/` | `…\Activators` | |

## Framework variants (kept as-is, by design)

`src/Laravel/` (`…\Laravel`) and `src/Lumen/` (`…\Lumen`) group **framework-specific
subclasses** — `LaravelFileRepository`/`LumenFileRepository` (extend
`Repositories\FileRepository`) and `Laravel\Module`/`Lumen\Module` (extend
`Support\Module`). They group by framework rather than by type because splitting them
would collide the three `Module` variants and force renames; the grouping is
intentional.

## Procedural helpers (not namespaced)

`helpers/helpers.php` holds global helper **functions**, loaded via composer's `files`
autoload — not namespaced, by design.

## Invariant

Every class file's declared namespace equals its directory path under the PSR-4 base
(verified in `docs/refactor/AFTER.md`). There are no root-level `src/*.php` classes.

[← Docs index](../README.md#documentation)
