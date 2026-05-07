# laranail/package-scaffolder — documentation

Generator + 139 stubs for scaffolding new Laravel packages.

## In this directory

- [CONTRIBUTING.md](CONTRIBUTING.md) — how to set up a development checkout and submit changes.
- [TESTING.md](TESTING.md) — running the Pest suite, writing new tests, fixtures.
- [SECURITY.md](SECURITY.md) — vulnerability reporting + supply-chain posture.
- `adr/` — Architectural Decision Records (Phase 16 will populate).
- `examples/` — runnable examples (future).

## Runtime documentation

The runtime base library — `Package` builder + `PackageServiceProvider`
+ all the fluent API steps generated packages call — lives in
[`laranail/package-tools`](https://github.com/laranail/package-tools).
For runtime architecture, services, configuration, and the v1.0
differentiator features (attribute discovery, `package:doctor`,
`IsolatedTestCase`, `EnvFileService`), see that repo's `docs/`.

## Online docs

- Primary: [`opensource.simtabi.com/package-scaffolder/docs/`](https://opensource.simtabi.com/package-scaffolder/docs/)
- Portal: [`opensource.simtabi.com/package-scaffolder/`](https://opensource.simtabi.com/package-scaffolder/)

## Quickstart

```bash
composer require --dev laranail/package-scaffolder
php artisan make:package vendor/widget
```

The scaffold writes a new package skeleton at `packages/vendor/widget/`
(configurable). The generated `composer.json` requires
`laranail/package-tools`; the generated service provider extends
`Simtabi\Laranail\PackageTools\PackageServiceProvider`.

See [CONTRIBUTING.md](CONTRIBUTING.md) for local-development workflow.
