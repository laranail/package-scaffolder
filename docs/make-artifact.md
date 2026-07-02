# Generating artifacts — `make:artifact`

`make:artifact` (canonical name `laranail::package-scaffolder.new`) generates a complete,
opinionated artifact — a **module**, **package**, or **plugin** — from the bundled blueprint
template (`stubs/blueprints/laravel/`). The output is a full `laranail/package-tools` package (models,
services, actions, repositories, search manager, body pipeline, lifecycle events, console commands,
optional web/API/feeds/panels, tests and docs), parameterised to your name and namespace and pruned
to the features you select.

It runs the **same way interactively or unattended** — every prompt has a flag, both share one
validation + generation path, so a flag and its prompt can never drift. A non-TTY (or
`--no-interaction`) runs unattended; a missing **required** value then fails loudly, naming the flag.

## Usage

```bash
# interactive (guided prompts)
php artisan make:artifact

# unattended (flags) — alias and canonical name are equivalent
php artisan make:artifact Blog --type=module --plugin=none --features=web-ui,rest-api,caching
php artisan laranail::package-scaffolder.new Shop --type=plugin --plugin=filament --no-interaction
```

### Inputs (prompt ⇄ flag)

| Input | Flag | Notes |
|-------|------|-------|
| Name | positional `name` | The **artifact** (package/module/plugin), StudlyCase; **must be unique across all containers**. |
| Entity | `--entity=` | The **primary entity** (StudlyCase). Defaults to a distinct generic (`Item`) and must differ from the artifact name. See *Naming model*. |
| Type | `--type=` | `package` · `module` · `plugin`. Required (no default in unattended mode). The role/container; the repo still carries all manifests its flavor supports. |
| Flavor | `--flavor=` | `laravel` · `lumen` · `vanilla` — the framework. Default `laravel`. Selects the blueprint + gates panels/features. |
| Panel | `--plugin=` | `nova` · `filament` · `none` — a single **mutually-exclusive** choice; **laravel-only**; default `none`. |
| Features | `--features=a,b` or repeated `--feature=` | Default = the flavor's feature set (laravel = all; lumen/vanilla = none). Unknown or flavor-incompatible feature ⇒ error. |
| Namespace | `--namespace=` | Root PHP namespace; defaults to `config('artifacts.default_namespace')`. |
| Vendor | `--vendor=` | Composer vendor; defaults from config. |
| — | `--path=` | Override the container base directory. |
| — | `--force` | Overwrite an existing target. |
| — | `--no-repo` | Skip wiring the host `composer.json`. |

## Naming model (artifact vs entity)

The blueprint is a domain-agnostic template, not a "Blog". It decouples two names, which are
tokenized independently so you can generate a `Customer`, `Admin`, `Billing` — anything — cleanly:

- **Artifact** — the package/module/plugin itself (`Blog` → `{Artifact}`). Drives the namespace,
  manager/facade, composer name, config key/file, slug, routes, `BLOG_` env prefix, and the
  container folder.
- **Primary entity** — the main domain record (`Post` → `{Entity}`). The blueprint deliberately makes
  these different concepts (`Blog` ≠ `Post`), so the entity is **prompted** (`--entity`), defaulting
  to a distinct generic (`Item`) and must differ from the artifact name (the manager is named after the artifact, the model after the entity). Entity files are renamed too (`PostController` →
  `{Entity}Controller`), with real inflection for singular/plural and Studly/camel/snake/SCREAMING case.

`Comment`, `Category`, and `Tag` are **kept as the generic supporting layer** — they're cross-domain
nouns (any record can have comments/categories/tags), so they are not tokenized.

**`post` and the framework.** Lowercase `post` is also Laravel's HTTP verb (`Route::post`,
`->postJson`, `->post()`), which is framework API and must not be renamed. Entity tokenization
rewrites entity references (`$post`, `{post}`, `post_id`, `postService`, `Post*` classes) but
**protects** framework calls and English words (`Postgres`, `compost`, `posted`). "Zero leftovers"
therefore means zero *entity* `blog`/`post`, not the framework's own `post`.

## Build & test matrix policy

Generated artifacts are verified across the **type × {nova, filament, none} × feature** matrix:

- **Static (every combination):** generation succeeds, the provider is `php -l`-valid, no `@artifact`
  or `[[…]]` markers leak, both laranail libraries are required, and the plugin dimension is honored
  (incl. `plugin=none` zero Nova/Filament footprint).
- **Runtime (all-features-on):** the generated provider is registered and **booted** in a real
  container (`config` merges, manager + repository binding resolve).
- **Pruned artifacts** (a feature off → its code/routes/tests deleted) are verified to build; their
  feature-specific suites are **not** run (they'd reference deleted code). `ReviewHardeningTest` is a
  full-feature integration fixture — run it only against an all-features artifact.

## Types & containers

Each type writes to its container under the host app, but **the folder is a location only** — the
PHP root namespace comes from `--namespace`, never from the container. The same artifact generated
into any container resolves to the identical namespace.

| Type | Container |
|------|-----------|
| `package` | `platform/packages/{Name}` |
| `module` | `platform/modules/{Name}` |
| `plugin` | `platform/plugins/{Name}` |

## Flavors & manifests

`--flavor` is the **framework** dimension (orthogonal to `--type`). It's data-driven from the
`flavors` registry in `config/artifacts.php` — adding a framework (e.g. `symfony`) is one registry
entry + one `stubs/blueprints/{flavor}/` dir, no code change.

| Flavor | Blueprint | Manifests emitted | Panels | Features |
|--------|-----------|-------------------|--------|----------|
| `laravel` (default) | full package-tools blueprint | composer + module + plugin | nova/filament/none | all |
| `lumen` | lean service-provider package | composer + module + plugin | none | none (lean) |
| `vanilla` | pure-PHP library (no Illuminate) | composer only | none | none |

A generated repo carries **all manifests its flavor supports**, so one repo is consumable as a
Composer **package** (`composer.json`), a **module** (`module.json`), and/or a **plugin**
(`plugin.json`) — loaded at runtime by
[`laranail/package-management`](https://opensource.simtabi.com/package-management/). The manifest
schemas are the shared contract (see that package's `docs/manifests.md`). Nova/Filament code makes the
same repo a panel plugin; those are laravel-only.

The laravel/lumen flavors also generate a **lifecycle `Hook`** (`src/Hooks/{Artifact}Hook.php`, referenced
by the `hook` field in `module.json`/`plugin.json`) with `activated`/`deactivated`/`installed`/`removed`
stubs. It's a **plain, decoupled class** — the loader duck-types it, so the generated repo keeps **no
runtime dependency** on `laranail/package-management` (only a `suggest`).

## Plugins

Only relevant for `--type=plugin`. `filament` / `nova` generate that panel's integration (resources +
a `class_exists`-guarded provider) and dependency; `none` produces a **zero Nova/Filament footprint** —
no panel code, stubs, dependencies, providers, panel docs/tests, or consumer-facing README/docs prose.

## Features

Toggleable (default all on): `web-ui` (with a `livewire` sub-toggle), `rest-api`, `caching`, `feeds`,
`scheduling`, `asset-pipeline`, `notifications`. A disabled feature is **not generated at all** — its
files are removed, its wiring blocks stripped, its config keys dropped, and any orphaned imports
cleaned by a Pint pass.

`livewire`, `feeds`, and `asset-pipeline` **require `web-ui`** (they are web/Blade concerns —
Livewire components, feed routes/controller, and the `<x-…::assets>` component). Selecting any of
them pulls `web-ui` in automatically.

The blueprint's core substrate — lifecycle events, the search manager, the body pipeline, the
macroable manager/DSL and the `Blog::spy()` test seam — is always present (it is the blueprint's
identity and is not separable).

## Host composer wiring

Unless `--no-repo` is passed, the host `composer.json` is **idempotently** wired (run twice = same
result, unrelated keys preserved): `wikimedia/composer-merge-plugin` includes for
`./platform/{modules,packages,plugins}/*/composer.json`, matching path `repositories`, the relevant
`config` (allow-plugins, optimize-autoloader, preferred-install, sort-packages) and
`minimum-stability`/`prefer-stable` (set only when absent). Run `composer dump-autoload` afterwards.

---

[← Docs index](../README.md#documentation)
