# laranail/package-scaffolder

[![Tests](https://github.com/laranail/package-scaffolder/actions/workflows/tests.yml/badge.svg)](https://github.com/laranail/package-scaffolder/actions/workflows/tests.yml)
[![Static analysis](https://github.com/laranail/package-scaffolder/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/laranail/package-scaffolder/actions/workflows/static-analysis.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

`laranail/package-scaffolder` is not published to Packagist, so there is no registry-version badge to show: see [Install](#install).

> Author-time generator for the laranail ecosystem — scaffold self-contained Laravel **packages, modules, and plugins** (HMVC) from one Artisan command, each with its own views, controllers, models, migrations, service providers, tests, and CI.

Requires PHP `^8.4.1 || ^8.5` on Laravel `^13`. Its runtime counterpart is [`laranail/package-management`](https://opensource.simtabi.com/documentation/laranail/package-management/), which discovers, activates, and wires the generated artifacts into a host app.

## Install

```bash
composer require laranail/package-scaffolder
```

Install it as a regular dependency, not `--dev`: modules generated with
`laranail::package-scaffolder.make` extend its `ModuleServiceProvider` and are loaded at runtime
by its service provider, so a `--no-dev` production install would leave them without a base class.

Every command is named `laranail::package-scaffolder.<command>`. The old `module:<command>` and
`make:artifact` names still work as deprecated aliases and print a one-line warning naming the
replacement; they are removed no earlier than the next minor after 0.1. The module repository is
bound as `laranail.package-scaffolder.modules`; the bare `modules` container alias is likewise kept as a
deprecated alias.

## Quick start guide and usage

### Getting started

Nothing is required before the first call: the generator runs on its packaged config. By default it
also wires the host `composer.json` (merge-plugin plus path repositories) so the generated artifact
autoloads; pass `--no-repo` to skip that. Optionally publish the module config, the artifact config
or the per-file stubs to customise them:

```bash
php artisan vendor:publish --tag=laranail::package-scaffolder-config
php artisan vendor:publish --tag=laranail::package-scaffolder-artifacts
php artisan vendor:publish --tag=laranail::package-scaffolder-stubs
```

### Usage

```bash
php artisan laranail::package-scaffolder.new Blog --type=module
# -> platform/modules/Blog/ with composer.json, module.json, its provider, tests and CI
```

Run it with no arguments for the guided prompts, or fully unattended:

```bash
php artisan laranail::package-scaffolder.new Shop --type=plugin --plugin=filament --no-interaction
```

The full walkthrough is in [Getting started](docs/getting-started.md).
Everything else is in the [documentation index](#documentation).

## <a name="documentation"></a>Documentation

Full documentation is at **[opensource.simtabi.com/documentation/laranail/package-scaffolder](https://opensource.simtabi.com/documentation/laranail/package-scaffolder/)** — getting started, the generated artifacts (package/module/plugin manifests), the make commands, architecture, and configuration.

## Contributing & security

Issues and PRs are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities per
[SECURITY.md](SECURITY.md) (security@simtabi.com); participation follows the [Code of Conduct](CODE_OF_CONDUCT.md).

## License

MIT © Simtabi LLC. See [LICENSE](LICENSE).
