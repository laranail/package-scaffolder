# Generating artifacts — `make:artifact`

`make:artifact` (canonical name `laranail::package-scaffolder.new`) generates a complete,
opinionated artifact — a **module**, **package**, or **plugin** — from the bundled blueprint
template (`stubs/blueprint/`). The output is a full `laranail/package-tools` package (models,
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
| Name | positional `name` | StudlyCase; **must be unique across all containers**. |
| Type | `--type=` | `package` · `module` · `plugin`. Required (no default in unattended mode). |
| Plugin | `--plugin=` | `nova` · `filament` · `none`. Required **iff** `--type=plugin`. |
| Features | `--features=a,b` or repeated `--feature=` | Default = all on (the full blueprint). Unknown feature ⇒ error. |
| Namespace | `--namespace=` | Root PHP namespace; defaults to `config('artifacts.default_namespace')`. |
| Vendor | `--vendor=` | Composer vendor; defaults from config. |
| — | `--path=` | Override the container base directory. |
| — | `--force` | Overwrite an existing target. |
| — | `--no-repo` | Skip wiring the host `composer.json`. |

## Types & containers

Each type writes to its container under the host app, but **the folder is a location only** — the
PHP root namespace comes from `--namespace`, never from the container. The same artifact generated
into any container resolves to the identical namespace.

| Type | Container |
|------|-----------|
| `package` | `platform/packages/{Name}` |
| `module` | `platform/modules/{Name}` |
| `plugin` | `platform/plugins/{Name}` |

## Plugins

Only relevant for `--type=plugin`. `filament` / `nova` generate that panel's integration (resources +
a `class_exists`-guarded provider) and dependency; `none` produces a **zero Nova/Filament footprint** —
no panel code, stubs, dependencies, providers, panel docs/tests, or consumer-facing README/docs prose.

## Features

Toggleable (default all on): `web-ui` (with a `livewire` sub-toggle), `rest-api`, `caching`, `feeds`,
`scheduling`, `asset-pipeline`, `notifications`. A disabled feature is **not generated at all** — its
files are removed, its wiring blocks stripped, its config keys dropped, and any orphaned imports
cleaned by a Pint pass.

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
