# Structure refactor — PLAN (awaiting approval)

Behavior-preserving reorg to "every class type in its own folder = its namespace segment."
Per the audit, the package is already ~94% type-grouped; this plan moves the **8 root-level
classes** into type folders and asks for a decision on the **framework-variant dirs**. No file
moves until this is approved.

## Proposed moves — before → after

### Group A — unambiguous root utilities (recommended: move)

| Old path | Old namespace/FQCN | New path | New FQCN |
|---|---|---|---|
| `src/Collection.php` | `…\Collection` | `src/Support/Collection.php` | `…\Support\Collection` |
| `src/Json.php` | `…\Json` | `src/Support/Json.php` | `…\Support\Json` |
| `src/ModuleManifest.php` | `…\ModuleManifest` | `src/Support/ModuleManifest.php` | `…\Support\ModuleManifest` |
| `src/FileRepository.php` | `…\FileRepository` | `src/Repositories/FileRepository.php` | `…\Repositories\FileRepository` |

`Repositories/` is a **new** type folder (created only because these classes exist).

### Group B — service providers → `Providers/` (recommended: move; the brief mandates grouping all providers)

| Old path | Old FQCN | New path | New FQCN |
|---|---|---|---|
| `src/ModulesServiceProvider.php` | `…\ModulesServiceProvider` | `src/Providers/ModulesServiceProvider.php` | `…\Providers\ModulesServiceProvider` |
| `src/LaravelModulesServiceProvider.php` | `…\LaravelModulesServiceProvider` | `src/Providers/LaravelModulesServiceProvider.php` | `…\Providers\LaravelModulesServiceProvider` |
| `src/LumenModulesServiceProvider.php` | `…\LumenModulesServiceProvider` | `src/Providers/LumenModulesServiceProvider.php` | `…\Providers\LumenModulesServiceProvider` |

Requires updating `composer.json` `extra.laravel.providers` (the package's Laravel auto-discovery
entry) to `…\Providers\LaravelModulesServiceProvider`, plus the Lumen bootstrap reference.

### Group C — DECISIONS NEEDED (not moving without sign-off)

1. **`src/Module.php` (`…\Module`, abstract, imported 16×, has `Laravel\Module`/`Lumen\Module`
   subclasses).** It's the package's central entity. Options:
   - **(rec) keep at `src/` root** — it's the root domain abstraction (like a root Model); moving it
     forces the framework subclasses + a `Module` type-folder naming clash for little gain.
   - move to `src/Support/Module.php` (`…\Support\Module`) — uniform, but heavy breakage + odd for a
     domain entity.
   - move to a new `src/Modules/Module.php` (`…\Modules\Module`).
2. **Framework-variant dirs `src/Laravel/`, `src/Lumen/`.** They group by framework, not type:
   - `Laravel/LaravelFileRepository.php`, `Laravel/Module.php` (`…\Laravel\Module`)
   - `Lumen/LumenFileRepository.php`, `Lumen/Module.php` (`…\Lumen\Module`)
   - **(rec) keep them as framework dirs** — splitting by type sends the `*FileRepository`s to
     `Repositories/` and the two `Module` variants into a `Module` folder, colliding with the root
     `Module` and forcing **renames** (`Laravel\Module` → `LaravelModule`, etc.). That's rename +
     extra breakage the brief says to avoid unless a collision forces it.
   - alternative (full-aggressive): `Laravel/LaravelFileRepository` → `Repositories/LaravelFileRepository`;
     `Laravel/Module` → `Modules/LaravelModule` (**rename**, flagged); same for Lumen.

## Breaking-change map (for CHANGELOG / upgrade notes)

Every Group A/B move (and any Group C move you approve) changes a **public, importable** FQCN — a
breaking change for consumers. The old → new map above is the upgrade table. Consumers update their
`use` imports accordingly. `Module` (16 intra-package uses + external) is the highest-impact if moved.

## Not moving (already correct)

`Commands/` (72, with Actions/Database/Make/Publish subfolders), `Support/` (12), `Traits/`,
`Exceptions/`, `Contracts/`, `Publishing/`, `Process/`, `Generators/`, `Providers/` (existing 2),
`Routing/`, `Migrations/`, `Facades/`, `Constants/`, `Activators/`. The `Facades\Module` alias and
`helpers/helpers.php` (procedural, stays a function file) are unchanged.

## Execution plan (Phase 3, after approval)

1. `git mv` per group; update `namespace` + every `use` import.
2. Update `composer.json` (`extra.laravel.providers`; psr-4 unchanged — base still `src`), then
   `composer dump-autoload`.
3. Update FQCN strings: provider registration, container bindings, event/listener maps,
   `publishes()` paths, config references, the Lumen bootstrap, any `::class` string.
4. Update tests + any scaffolder stubs/templates referencing these FQCNs. (Note: the blueprint
   template under `src/Commands/stubs/blueprint/` uses `Some\NamespacePath\Blog\*` — it does NOT
   reference the package's own classes, so it is unaffected.)
5. Small commits, one type-group each, lowercase messages.
6. Docs (Phase 4): README/CONTRIBUTING/CHANGELOG upgrade map + `docs/ARCHITECTURE.md`.
7. Verify (Phase 5): `composer dump-autoload`, full suite (460), phpstan, pint, and re-audit →
   `docs/refactor/AFTER.md`; every namespace matches its path, nothing orphaned.

## Trade-off to weigh before approving

This is a **fork of nwidart/laravel-modules**; the root classes match upstream's layout. Relocating
them **diverges from upstream** (harder future merges) and is a **breaking change** for every
consumer. The type-purity gain is real but modest (the package is already ~94% grouped). Options:
- **Approve Groups A+B, keep C as recommended (Module at root, framework dirs kept)** — clean, low
  collision/rename risk, still "every provider in Providers/ + every utility in a type folder."
- **Approve full-aggressive (A+B+C moves + framework split + renames)** — maximum type-purity,
  maximum breakage + upstream divergence.

**Stopping here for your decision** on: (1) proceed at all vs keep upstream layout; (2) Group C —
`Module` placement; (3) framework-variant dirs (keep vs split-with-renames).
