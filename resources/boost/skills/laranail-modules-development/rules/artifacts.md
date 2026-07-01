# Blueprint generation — `make:artifact`

`make:artifact` (canonical `laranail::package-scaffolder.new`) scaffolds a **complete, opinionated
artifact** from the bundled gold-standard blueprint (`stubs/blueprint/`): a full
`laranail/package-tools` package with the manager + fluent DSL, services/actions, a repository +
contract, a search manager, a model body-processing pipeline, lifecycle events, policies, an
optional REST API, web UI, feeds, scheduling, an asset pipeline, and tests.

Use this to **start** an artifact; use `module:make-*` (see `rules/generators.md`) to add individual
classes into one that already exists.

## Usage

```bash
# interactive (guided prompts)
php artisan make:artifact

# unattended
php artisan make:artifact Blog --type=module --plugin=none --features=web-ui,rest-api,caching --no-interaction
php artisan make:artifact Customer --type=package --entity=Account --plugin=filament
```

| Input | Flag | Notes |
|---|---|---|
| Name | positional | The **artifact** (StudlyCase); unique across all containers. |
| Entity | `--entity=` | The **primary entity**; default = distinct generic `Item`, must differ from the artifact. |
| Type | `--type=` | `package` · `module` · `plugin`. |
| Panel | `--plugin=` | `nova` · `filament` · `none` — single mutually-exclusive choice; default `none`. |
| Features | `--features=a,b` | Subset from the catalog; default = all. Unknown ⇒ error; requires auto-resolved. |
| Namespace | `--namespace=` | PSR-4 root; default from config. |
| — | `--vendor=` / `--path=` / `--force` / `--no-repo` | Vendor, container override, overwrite, skip composer wiring. |

## Naming model (generic template)

The blueprint is **domain-agnostic** — generate `Customer`, `Admin`, `Billing`, anything:

- **Artifact** = the package/module/plugin (`Blog` → `{Name}`): namespace, manager/facade, composer
  name, config key/file, slug, routes, `BLOG_` env prefix, container folder.
- **Primary entity** = the main record (`Post` → `{Entity}`): models, services, controllers, etc.
- **Supporting entities** = `Comment` (1-to-many child), `Category` (grouping), `Tag` (m-to-many
  label) — ship in every artifact verbatim; not tokenized, not pruned. Rename them by hand later if
  a domain needs to.

Folder is location-only: `platform/{modules,packages,plugins}/Blog` all resolve to the same PSR-4
namespace. Output is zero-`blog`/zero-`post` for a non-blog artifact (framework `Route::post`/
`->postJson` are protected, not entity references).

## Panel (Nova / Filament / none)

Mutually exclusive. `nova`/`filament` scaffold only that adapter (guarded by `class_exists`) in its
own provider + dependency; `none` emits **zero** Nova/Filament code, stubs, deps, providers, or
panel docs/tests — and strips even the panel-named comments.

## Feature catalog (config-driven)

The toggle set is **data**, in `config('artifacts.features')` (and documented in `FEATURE_CATALOG.md`),
so prompts and `--features` can't drift. Each feature has a `default`, `requires`, and `description`.

- **Always-on core** (not toggleable): lifecycle events, search manager, body pipeline, macroable
  manager/DSL, spy seam, the domain model — removing them would mean rewriting the service/observer/models.
- **Optional** (default on; opt out via `--features`): `web-ui` (+ `livewire`), `rest-api`, `caching`,
  `feeds`, `scheduling`, `asset-pipeline`, `notifications`.

Turning a feature off removes its files, routes, migrations, config keys and feature-specific tests
with no dangling references; selecting one pulls in its `requires` (e.g. `livewire` → `web-ui`).

## Build & test matrix policy

Generated artifacts are verified across type × {nova,filament,none} × features: every combo generates
a `php -l`-valid provider with no leftover markers and both laranail deps; an all-features artifact is
booted in a real container. Pruned artifacts are verified to build; their feature-specific suites are
not run (they'd reference deleted code) — `ReviewHardeningTest` is a full-feature fixture.

---

[← Skill](../SKILL.md)
