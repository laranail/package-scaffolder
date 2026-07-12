# laranail/package-scaffolder

[![Latest version on Packagist](https://img.shields.io/packagist/v/laranail/package-scaffolder.svg)](https://packagist.org/packages/laranail/package-scaffolder)
[![Tests](https://github.com/laranail/package-scaffolder/actions/workflows/tests.yml/badge.svg)](https://github.com/laranail/package-scaffolder/actions/workflows/tests.yml)
[![Static analysis](https://github.com/laranail/package-scaffolder/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/laranail/package-scaffolder/actions/workflows/static-analysis.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

> Author-time generator for the laranail ecosystem — scaffold self-contained Laravel **packages, modules, and plugins** (HMVC) from one Artisan command, each with its own views, controllers, models, migrations, service providers, tests, and CI.

Requires PHP `^8.4.1 || ^8.5` on Laravel `^13`. Its runtime counterpart is [`laranail/package-management`](https://opensource.simtabi.com/documentation/laranail/package-management/), which discovers, activates, and wires the generated artifacts into a host app.

## Install

```bash
composer require --dev laranail/package-scaffolder
```

## Documentation

Full documentation is at **[opensource.simtabi.com/documentation/laranail/package-scaffolder](https://opensource.simtabi.com/documentation/laranail/package-scaffolder/)** — getting started, the generated artifacts (package/module/plugin manifests), the make commands, architecture, and configuration.

## Contributing & security

Issues and PRs are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities per
[SECURITY.md](SECURITY.md) (opensource@simtabi.com); participation follows the [Code of Conduct](CODE_OF_CONDUCT.md).

## License

MIT © Simtabi LLC. See [LICENSE](LICENSE).
