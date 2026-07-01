# Structure refactor — AFTER audit

Post-move state. Compare with `BEFORE.md`; plan in `PLAN.md`. Approved scope:
full-aggressive (root utilities → `Support`/`Repositories`, all providers →
`Providers`), `Module` → `Support\Module`, framework dirs `Laravel/`/`Lumen/` kept.

## Invariant checks — PASS

- **Namespace ↔ path mismatches:** `0` (every `src/**/*.php`, excluding the
  `src/Commands/stubs/blueprint/` template, declares a namespace equal to its dir).
- **Root-level `src/*.php` classes:** `0` — every class is in a type folder.
- **Type ↔ folder (kind/name vs folder):** clean — every `interface`→`Contracts/`,
  `trait`→`Traits/`, `enum`→`Enums/`, `*Exception`→`Exceptions/`, and
  `*ServiceProvider`→`Providers/` (no provider left in `Support/`).

## Inventory after — top-level `src/` groups

| Group | Count | Δ from before |
|---|---:|---|
| `Commands/` | 72 | — |
| `Support/` | 15 | +3 (Collection, Json, Module, ModuleManifest; −1 ModuleServiceProvider→Providers) |
| `Traits/` | 6 | — |
| `Providers/` | 6 | +4 (Modules/Laravel/Lumen ServiceProvider + ModuleServiceProvider) |
| `Exceptions/` | 5 | — |
| `Contracts/` | 5 | — |
| `Publishing/` | 4 | — |
| `Process/` | 3 | — |
| `Generators/` | 3 | — |
| `Repositories/` | 1 | +1 (new folder — FileRepository) |
| `Laravel/`, `Lumen/` | 2 each | — (kept, imports of moved classes updated) |
| `Routing/`, `Migrations/`, `Facades/`, `Constants/`, `Activators/` | 1 each | — |
| **(root)** | **0** | **−8** |

## Moves executed (old → new FQCN)

| Old | New |
|---|---|
| `…\Collection` | `…\Support\Collection` |
| `…\Json` | `…\Support\Json` |
| `…\ModuleManifest` | `…\Support\ModuleManifest` |
| `…\Module` | `…\Support\Module` |
| `…\FileRepository` | `…\Repositories\FileRepository` |
| `…\ModulesServiceProvider` | `…\Providers\ModulesServiceProvider` |
| `…\LaravelModulesServiceProvider` | `…\Providers\LaravelModulesServiceProvider` |
| `…\LumenModulesServiceProvider` | `…\Providers\LumenModulesServiceProvider` |
| `…\Support\ModuleServiceProvider` | `…\Providers\ModuleServiceProvider` |

The last move was surfaced by a **deeper re-audit** (class *kind*/name vs folder, not
just namespace↔path): `ModuleServiceProvider` — the abstract base that generated
module providers extend — was sitting in `Support/` despite the "all providers in
`Providers/`" rule. Moving it also updated the shipped `scaffold/provider.stub` and
its 11 command snapshots (diff = only the `use` line).

## Reference updates applied

- Every `use`/FQCN across `src/`, `tests/`, `helpers/helpers.php`.
- `composer.json` `extra.laravel.providers` → `…\Providers\LaravelModulesServiceProvider`.
- `vendor:publish` error string in `Exceptions\InvalidActivatorClass`.
- README publish command + CHANGELOG references.
- Added `use` imports for now-cross-namespace sibling references in the moved files
  (e.g. `FileRepository` now imports `Support\{Module,Collection,Json}`; providers
  import `Support\ModuleManifest`, `Contracts\*`, `Laravel\*`/`Lumen\*`).
- Fixed `__DIR__` depth in the moved providers (config/scripts via `../../`, stubs via
  `dirname(__DIR__)`) so published/config paths still resolve.
- Retargeted the phpstan baseline paths + message FQCNs (error count held at 70 — no
  new suppressions introduced).

## Verification — PASS

- `composer dump-autoload` — clean.
- `vendor/bin/phpunit --no-coverage` — **460 tests, 1225 assertions**, green.
- `vendor/bin/phpstan analyse` (level 5 + baseline) — no errors.
- `vendor/bin/pint` — clean.

Behavior-preserving: no logic changed; the only functional edits were autoload-driven
(`use`/FQCN) and `__DIR__` path-depth corrections required by the moves.

[← Docs index](../../README.md#documentation)
