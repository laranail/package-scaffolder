# laranail/package-scaffolder

[![Latest version on Packagist](https://img.shields.io/packagist/v/laranail/package-scaffolder.svg)](https://packagist.org/packages/laranail/package-scaffolder)
[![Tests](https://github.com/laranail/package-scaffolder/actions/workflows/tests.yml/badge.svg)](https://github.com/laranail/package-scaffolder/actions/workflows/tests.yml)
[![Static analysis](https://github.com/laranail/package-scaffolder/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/laranail/package-scaffolder/actions/workflows/static-analysis.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

> Author-time generator for the laranail ecosystem — scaffold self-contained Laravel **packages, modules,
> and plugins** (HMVC) from one Artisan command.

`laranail/package-scaffolder` generates a ready-to-run artifact (its own views, controllers, models,
migrations, service providers, tests and CI) that a large Laravel app can consume as a **package**
(`composer.json`), a **module** (`module.json`), or a **plugin** (`plugin.json`). Its runtime counterpart
is [`laranail/package-management`](https://github.com/laranail/package-management), which discovers,
activates and wires the generated artifacts into a host app.

## Requirements

- PHP `^8.4.1 || ^8.5`
- Laravel `^13.0`

Older Laravel releases (5.4 – 12) are served by earlier major lines of this package; see the
[CHANGELOG](CHANGELOG.md).

## Installation

```bash
composer require laranail/package-scaffolder
```

The service provider and `Module` facade are auto-discovered. Optionally publish the config:

```bash
php artisan vendor:publish --provider="Simtabi\Laranail\Package\Scaffolder\Providers\LaravelModulesServiceProvider"
```

### Autoloading generated modules

Generated modules are autoloaded via `wikimedia/composer-merge-plugin`. Add the merge entry (and allow the
plugin) in your app's `composer.json`, then `composer dump-autoload`:

```json
"extra": {
    "merge-plugin": { "include": ["Modules/*/composer.json"] }
},
"config": {
    "allow-plugins": { "wikimedia/composer-merge-plugin": true }
}
```

> A `Class "Modules\…\…ServiceProvider" not found` error almost always means the plugin isn't allowed or
> `composer dump-autoload` wasn't re-run.

## Quick start

```bash
php artisan make:artifact Blog                     # scaffold an artifact (package/module/plugin roles)
php artisan make:artifact Blog --type=module       # primary role
php artisan make:artifact Blog --flavor=lumen      # framework flavor (vanilla | laravel | lumen | symfony)
```

## Documentation

Hosted at [`opensource.simtabi.com/package-scaffolder/docs/`](https://opensource.simtabi.com/package-scaffolder/docs/)
(product page: [`opensource.simtabi.com/package-scaffolder/`](https://opensource.simtabi.com/package-scaffolder/)).
The same pages live under [`docs/`](docs/):

- [Installation](docs/installation.md) — requirements, install, module autoloading
- [Configuration](docs/configuration.md) — flavors, artifact types, manifest files
- [Generating artifacts (`make:artifact`)](docs/make-artifact.md) — types, plugins, features, portability, composer wiring
- [Architecture](docs/architecture.md) — the generated-artifact structure (package/module/plugin) + the scaffolder's own layout
- [Release](docs/release.md) — how releases are cut

## Sister packages

- [`laranail/package-management`](https://github.com/laranail/package-management) — runtime loader/manager for the artifacts this package generates.
- [`laranail/package-tools`](https://github.com/laranail/package-tools) — the `PackageServiceProvider` base + fluent `Package` builder.
- [`laranail/console`](https://github.com/laranail/console) — the command base enabling `laranail::` namespaced Artisan commands.

## Contributing & security

- [CONTRIBUTING.md](CONTRIBUTING.md) — workflow, coding standards, command naming.
- [SECURITY.md](SECURITY.md) — how to report a vulnerability.
- [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) — community expectations.
- [CHANGELOG.md](CHANGELOG.md) — release history.

## License

MIT. See [LICENSE](LICENSE). © Simtabi LLC.
