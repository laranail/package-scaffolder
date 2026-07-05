# Architecture

Two layouts matter here: **(1)** the structure of the artifact this package *generates*, and **(2)**
the internal structure of this package itself. Part 1 is the one you'll interact with day to day.

---

## 1. Generated artifact structure (what `make:artifact` produces)

`make:artifact` (canonical `laranail::package-scaffolder.new`) generates a complete artifact from a
per-flavor blueprint under `stubs/blueprints/{flavor}/`. Generation has four orthogonal, data-driven
dimensions (all from the `flavors`/`kinds`/`plugin_types`/`features` registries in
`config/artifacts.php`):

- **Flavor (`--flavor`).** The framework: `laravel` (default, the full `laravel/package-tools`
  blueprint) · `lumen` (lean service-provider package) · `vanilla` (pure-PHP library, no Illuminate) ·
  `symfony` (Symfony container-service package).
  The registry maps each flavor → blueprint dir + which manifests/panels/features it allows. **Adding
  a framework (e.g. `symfony`) = one registry entry + one `stubs/blueprints/{flavor}/` dir; no code.**
- **Type & container.** `--type` = `package` | `module` | `plugin` selects the container directory
  (`platform/packages/{Name}`, `platform/modules/{Name}`, `platform/plugins/{Name}`). The folder is a
  **location only** — the PHP root namespace comes from `--namespace`, never the container. The same
  artifact generated into any container resolves to the identical namespace.
- **Manifests & the runtime loader.** A generated repo carries every manifest its flavor supports —
  `composer.json` (package) + `module.json` (module) + `plugin.json` (plugin) — so one repo is
  consumable as any role, loaded at runtime by
  [`laranail/package-management`](https://opensource.simtabi.com/package-management/). Panels
  (Nova/Filament) are laravel-only and make the repo a panel plugin.
- **PSR-4 root is `src/`** (not `app/`). The blueprint decouples four identifiers: the root namespace
  (`--namespace`, e.g. `Modules\Blog`), the composer name (`{vendor}/{name}`), the config/view/trans
  key (`{vendor}.{name}`), and the component slug (`{vendor}-{name}`).
- **Artifact vs entity.** The **artifact** is the package/module/plugin (`Blog` → `{Artifact}`, the
  manager/facade/config/slug). The **primary entity** is the main record (`Post` → `{Entity}`, via
  `--entity`, default `Item`, must differ from the artifact). `Comment`/`Category`/`Tag` stay as the
  generic supporting layer. See [make:artifact](tools/make-artifact.md).

### Canonical tree

```
platform/{packages,modules,plugins}/{Name}/      # container = --type; folder ≠ namespace
├── composer.json  module.json  package.json      # module.json = manager manifest (name/alias/providers)
├── phpstan.neon  phpunit.xml  pint.json  rector.php
├── vite.config.js  tailwind.config.js            # asset-pipeline
├── config/{artifact}.php                          # config('{vendor}.{name}.*')
├── routes/{web,api}.php
├── database/{factories,migrations,seeders}/
├── resources/
│   ├── assets/{css,sass,scripts}/                 # asset-pipeline (tailwind/bootstrap/vanilla)
│   ├── lang/en/{artifact}.php
│   └── views/{components,livewire,partials,feed,layouts,{entity}}/
├── tests/{Feature, Feature/Api, Unit, Fixtures}/
├── docs/  (+ docs/tools/)
└── src/                                           # PSR-4 root — namespace from --namespace
    ├── {Artifact}.php                             # Macroable manager + fluent DSL
    ├── Facades/  Mixins/
    ├── Models/  Enums/  DataTransferObjects/  Traits/  Exceptions/
    ├── Contracts/  Repositories/                  # {Eloquent,Caching}{Entity}Repository
    ├── Services/  Actions/
    ├── Search/ (SearchManager + Drivers/)         # Database + Scout drivers
    ├── Processing/ (BodyProcessor + Stages/)
    ├── Events/  Listeners/  Observers/  Jobs/  Notifications/
    ├── Console/                                   # commands on the laranail/console base
    ├── Http/{Controllers, Controllers/Api, Middleware, Requests (+Concerns), Resources}/
    ├── Policies/  Rules/  Doctor/
    ├── View/Components/  Livewire/
    ├── Providers/ ({Artifact}ServiceProvider + Integrations/{Filament,Nova}ServiceProvider)
    └── Filament/ (Plugin + Resources/)  Nova/ (Resources/ + Tools/)   # only for --plugin
```

### How features & plugins shape the output

A disabled feature is **not generated at all** — its files are removed, its provider wiring is
stripped, its config keys and tests dropped:

| Feature | Adds | Requires |
|---|---|---|
| `web-ui` | `Http/Controllers`, `View/Components`, `resources/views`, `routes/web.php` | — |
| `livewire` | `Livewire/` components | `web-ui` |
| `rest-api` | `Http/Controllers/Api`, `Http/Resources`, `EnsureApiAbility`, `routes/api.php` | — |
| `caching` | `Repositories/Caching{Entity}Repository` + invalidation listener | — |
| `feeds` | RSS/sitemap controller + views + routes | `web-ui` |
| `scheduling` | scheduled-publish command + job | — |
| `asset-pipeline` | Vite config + `resources/assets` + `<x-…::assets>` | `web-ui` |
| `notifications` | published-record notification listener | — |

`--plugin` is `nova` \| `filament` \| `none` (mutually exclusive). `none` produces a **literal-zero**
Nova/Filament footprint (no panel code, deps, providers, docs, or prose). The **core substrate** —
the Macroable manager/DSL, `Search` manager, `Processing` pipeline, lifecycle `Events`, and the
`{Artifact}::spy()` test seam — is always present. See [FEATURE_CATALOG.md](../FEATURE_CATALOG.md).

---

## 2. Scaffolder package internals (this repo)

The package follows a strict **type-folder** layout: every class lives in a folder whose path mirrors
its namespace segment. PSR-4 base `Simtabi\Laranail\Package\Scaffolder\` → `src/`.

| Type | Folder → namespace | Notes |
|---|---|---|
| Artisan commands | `src/Commands/` (`Actions/`, `Database/`, `Make/`, `Publish/`) | Incl. `MakeArtifactCommand` (the blueprint generator). |
| Artifact engine | `src/Support/Artifacts/` | `ArtifactGenerator`, `GenerationRequest`, `TokenReplacer`, `MarkerProcessor`, `HostComposerWriter`. |
| Support / utilities | `src/Support/` | `Collection`, `Json`, `Module`, `ModuleManifest`, `Stub`. |
| Repositories | `src/Repositories/` | `FileRepository` (abstract module repository). |
| Service providers | `src/Providers/` | ALL providers, incl. the published `LaravelModulesServiceProvider` and the `ModuleServiceProvider` base that generated module providers extend. |
| Contracts · Traits · Exceptions · Generators · Publishing · Process · Routing · Migrations · Facades · Constants · Activators | `src/{Type}/` | one concern each. |

**Framework variants.** `src/Laravel/` and `src/Lumen/` group framework-specific subclasses
(`*FileRepository` extend `Repositories\FileRepository`; `Laravel\Module`/`Lumen\Module` extend
`Support\Module`) — grouped by framework because splitting would collide the three `Module` variants.

**Templates.** `stubs/` (top-level) holds the `module:make-*` per-file templates + the vendored
`stubs/blueprints/laravel/` (excluded from classmap + phpstan). **Procedural helpers** live in
`helpers/helpers.php` (composer `files` autoload — not namespaced).

**Invariant.** Every `src/**/*.php` class's declared namespace equals its directory path under the
PSR-4 base; there are no root-level `src/*.php` classes (verified in
[refactor/AFTER.md](refactor/AFTER.md)).

[← Docs index](../README.md#documentation)
