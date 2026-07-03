# Configuration

Two published config files drive the scaffolder: `config/artifacts.php` (what `make:artifact` emits) and
`config/config.php` (module runtime paths + generators, inherited from the upstream module engine).

## Flavors

`config/artifacts.php` carries a data-driven **flavor registry** — the framework shape an artifact is
generated for:

```php
'default_flavor' => env('ARTIFACT_DEFAULT_FLAVOR', 'laravel'),
'flavors' => [
    'vanilla' => [ /* framework-neutral, Illuminate-free */ ],
    'laravel' => [ /* full Laravel provider + features */ ],
    'lumen'   => [ /* Lumen-shaped */ ],
    'symfony' => [ /* Symfony-shaped, self-wiring provider */ ],
],
```

Choose one per generation with `--flavor`:

```bash
php artisan make:artifact Blog --flavor=lumen
```

Each flavor declares which features and blueprint set it supports; `make:artifact` resolves the feature
set from the chosen flavor (see [make-artifact.md](make-artifact.md)).

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
