# Configuration

Two config files drive the scaffolder, and each publishes under its own tag:

| Packaged file | Config key | Publish tag | Published to |
|---|---|---|---|
| `config/artifacts.php` — what `laranail::package-scaffolder.new` emits | `laranail.package-scaffolder.artifacts` | `laranail::package-scaffolder-artifacts` | `config/laranail/package-scaffolder/artifacts.php` |
| `config/config.php` — module runtime paths + generators | `laranail.package-scaffolder.modules` | `laranail::package-scaffolder-config` | `config/laranail/package-scaffolder/modules.php` |

```bash
php artisan vendor:publish --tag=laranail::package-scaffolder-artifacts
php artisan vendor:publish --tag=laranail::package-scaffolder-config
```

A published file only needs the keys you change; the packaged defaults are merged underneath it.

## Environment variables

Each variable is read under its vendor-prefixed name first. The former unprefixed name is still read
as a fallback, so an existing `.env` keeps working, but it is deprecated and is removed no earlier
than the next minor after 0.1.

| Variable | Deprecated fallback | Config key | Default |
|---|---|---|---|
| `LARANAIL_PACKAGE_SCAFFOLDER_ARTIFACT_DEFAULT_NAMESPACE` | `ARTIFACT_DEFAULT_NAMESPACE` | `artifacts.default_namespace` | `Modules` |
| `LARANAIL_PACKAGE_SCAFFOLDER_ARTIFACT_DEFAULT_ENTITY` | `ARTIFACT_DEFAULT_ENTITY` | `artifacts.default_entity` | `Item` |
| `LARANAIL_PACKAGE_SCAFFOLDER_ARTIFACT_DEFAULT_FLAVOR` | `ARTIFACT_DEFAULT_FLAVOR` | `artifacts.default_flavor` | `laravel` |
| `LARANAIL_PACKAGE_SCAFFOLDER_MODULE_VENDOR` | `MODULE_VENDOR` | `modules.composer.vendor` | `simtabi` |
| `LARANAIL_PACKAGE_SCAFFOLDER_MODULE_AUTHOR_NAME` | `MODULE_AUTHOR_NAME` | `modules.composer.author.name` | `Simtabi LLC` |
| `LARANAIL_PACKAGE_SCAFFOLDER_MODULE_AUTHOR_EMAIL` | `MODULE_AUTHOR_EMAIL` | `modules.composer.author.email` | `opensource@simtabi.com` |

`VAPOR_MAINTENANCE_MODE` keeps its name: Laravel Vapor sets it, not this package.

## Flavors

`config/artifacts.php` carries a data-driven **flavor registry** — the framework shape an artifact is
generated for:

```php
'default_flavor' => env('LARANAIL_PACKAGE_SCAFFOLDER_ARTIFACT_DEFAULT_FLAVOR', env('ARTIFACT_DEFAULT_FLAVOR', 'laravel')),
'flavors' => [
    'vanilla' => [ /* framework-neutral, Illuminate-free */ ],
    'laravel' => [ /* full Laravel provider + features */ ],
    'lumen'   => [ /* Lumen-shaped */ ],
    'symfony' => [ /* Symfony-shaped, self-wiring provider */ ],
],
```

Choose one per generation with `--flavor`:

```bash
php artisan laranail::package-scaffolder.new Blog --flavor=lumen
```

Each flavor declares which features and blueprint set it supports; `laranail::package-scaffolder.new` resolves the feature
set from the chosen flavor (see [`laranail::package-scaffolder.new`](tools/make-artifact.md)).

## Artifact roles + manifest files

One generated repo can be consumed in three **roles**, each keyed by a manifest emitted per
`config/artifacts.php` `manifest_files`:

| Role | Manifest | Consumed by |
|---|---|---|
| package | `composer.json` | Composer autoload / Laravel auto-discovery |
| module | `module.json` | `laranail/package-management` (activation-gated) |
| plugin | `plugin.json` | `laranail/package-management` / host ecosystem |

`--type` selects the primary role; unsupported manifests for a flavor are pruned during generation. The
manifest schemas are the shared contract with
[`laranail/package-management`](https://github.com/laranail/package-management).

## Module runtime paths

`config/config.php` `paths` control where modules are generated and how their sub-generators (controllers,
models, migrations, …) are laid out. Placement stays `platform/{packages,modules,plugins}/{Name}` for
artifacts, matching the loader's discovery paths.

[← Docs index](../README.md#documentation)
