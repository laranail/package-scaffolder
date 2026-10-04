# Getting started

Scaffold your first artifact with `laranail::package-scaffolder.new`, then run it. For the full command reference see
[Generating artifacts](tools/make-artifact.md); for everything else the
[Documentation index](../README.md#documentation).

## 1. Install

```bash
composer require laranail/package-scaffolder
```

The service provider + the `Module` facade are auto-discovered. Install it as a regular dependency,
not `--dev`: generated modules extend its `ModuleServiceProvider` at runtime (see
[Installation](installation.md)).

## 2. Scaffold an artifact

```bash
php artisan laranail::package-scaffolder.new Blog                  # prompts for the role; laravel flavor
php artisan laranail::package-scaffolder.new Blog --type=module    # pick the primary role
php artisan laranail::package-scaffolder.new Blog --flavor=lumen   # pick the framework flavor (vanilla | laravel | lumen | symfony)
```

This emits a ready-to-run artifact under `platform/` carrying its manifests (`composer.json`,
`module.json`, `plugin.json`) — the contract the runtime loader reads.

## 3. Run it

Autoload generated modules via `wikimedia/composer-merge-plugin` (see [Installation](installation.md)),
then load/activate them at runtime with
[`laranail/package-management`](https://github.com/laranail/package-management):

```bash
composer dump-autoload
php artisan laranail::package-management.install blog
```

## Next steps

- [Generating artifacts (`laranail::package-scaffolder.new`)](tools/make-artifact.md) — types, plugins, features, portability.
- [Configuration](configuration.md) — flavors, artifact types, manifest files.
- [Architecture](architecture.md) — the generated-artifact structure + the scaffolder's own layout.

---

[← Docs index](../README.md#documentation)
