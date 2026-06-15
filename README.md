# laranail/package-scaffolder

> Artisan command suite + 139 stubs for scaffolding new Laravel packages.

`package-scaffolder` generates a complete, opinionated Laravel package
skeleton — composer.json, service provider, config, views, routes,
migrations, tests, GitHub workflows, and more. Generated packages extend
[`laranail/package-tools`](https://github.com/laranail/package-tools) so they inherit the fluent
`Package` builder, attribute-driven discovery, `package:doctor`, and the
isolation testing harness from minute one.

## Targets

- PHP `^8.3 || ^8.4`
- Laravel `^13.0`
- Pest `^3.0`, Testbench `^11.0`

## Install

```bash
composer require --dev laranail/package-scaffolder
```

(Local development against a sibling checkout: `composer.json` already
declares a path repository at `../package-tools` for the runtime.)

## Quickstart

```bash
# In your Laravel app:
php artisan packager:generate vendor widget               # default skeleton
php artisan packager:generate vendor blog --preset=crud   # full CRUD bundle
php artisan packager:install  vendor widget               # add as path repo + composer require
```

The scaffold writes a new package skeleton at `packages/vendor/widget/`
(configurable via `scaffolder.paths.output`). Review the generated
`composer.json` + service provider and you're ready to extend
`laranail/package-tools`.

The `--preset=crud` overlay adds a full resource scaffold on top of the
default skeleton: Web/Api controllers (extending the package-tools
`WebController`/`ApiController` bases), form requests, JSON resource,
Eloquent repository + interface pair, `RouteServiceProvider`,
`DatabaseSeeder`, and `create`/`edit` Blade views. Configurable in
`config/scaffolder.php` under `presets.crud`.

## Available Artisan commands

The scaffolder ships its commands under the `packager:*` namespace. The
runtime base library `laranail/package-tools` adds a few `package:*`
commands on top once it's installed.

```
# Scaffolding
packager:generate vendor package [--preset=crud] [--force] [--path=…]
packager:install   vendor package [--constraint=*@dev] [--no-symlink]
packager:uninstall vendor package
packager:enable    vendor package
packager:disable   vendor package
packager:remove    vendor package
packager:list      [--git]                 # discovered local packages
packager:status    vendor package
packager:validate  vendor package
packager:bug-hunt  vendor package          # static-analysis sweep
packager:security-check vendor package
packager:publish-tests  vendor package

# Git workflows for scaffolded packages
packager:git status   <path>
packager:git commit   <path> --message=…
packager:git push     <path> [--remote=origin] [--branch=main]
packager:git tag      <path> --version=v0.1.0
packager:git init     <path> [--branch=main]   # idempotent first commit
packager:git bootstrap <path> --url=git@…       # init + remote add + push

# From laranail/package-tools (auto-registered)
package:doctor       # registered DoctorChecks
package:audit        # OSV.dev advisories on composer.lock
package:sbom         # CycloneDX 1.5 SBOM
package:ide-helper   # generate Facade classes from #[AsFacade] contracts
```

## Local development

```bash
bash scripts/init.sh          # one-shot bootstrap
composer setup                # alias for the above
composer test                 # vendor/bin/pest --colors=always
composer lint                 # pint + phpstan + rector --dry-run
composer audit                # composer audit (security advisories)
composer pint-fix             # apply Pint formatting
composer rector-fix           # apply Rector transformations
```

`scripts/init.sh` is the only shell script in the repo (per the suite's
ADR-007 — tooling is pure PHP/Composer).

## Documentation

In-repo docs live under [`docs/`](docs/):

- [docs/CONTRIBUTING.md](docs/CONTRIBUTING.md) — how to set up a development checkout and submit changes.
- [docs/TESTING.md](docs/TESTING.md) — running the Pest suite, writing new tests, fixtures.
- [docs/SECURITY.md](docs/SECURITY.md) — vulnerability reporting + supply-chain posture.
- [docs/adr/](docs/adr/) — Architectural Decision Records (Phase 16 will populate).
- [docs/examples/](docs/examples/) — runnable examples (future).
- Master plan: [.plans/CLEANUP-MASTER-PLAN.md](.plans/CLEANUP-MASTER-PLAN.md)
- Codemod archive: [scripts/codemod-archive.md](scripts/codemod-archive.md)

### Online docs

- Primary: [`opensource.simtabi.com/package-scaffolder/docs/`](https://opensource.simtabi.com/package-scaffolder/docs/)
- Portal: [`opensource.simtabi.com/package-scaffolder/`](https://opensource.simtabi.com/package-scaffolder/)

### Runtime documentation

The runtime base library — `Package` builder + `PackageServiceProvider`
+ all the fluent API steps generated packages call — lives in
[`laranail/package-tools`](https://github.com/laranail/package-tools).
For runtime architecture, services, configuration, and the v1.0
differentiator features (attribute discovery, `package:doctor`,
`IsolatedTestCase`, `EnvFileService`), see that repo's `docs/`.

## Sister packages

- [`laranail/package-tools`](https://github.com/laranail/package-tools) — runtime base library (required dependency for generated packages).
- [`laranail/database-tools`](https://github.com/laranail/database-tools) — independent Laravel DB utilities; usable by any Laravel app.
- [`laranail/laranail`](https://github.com/laranail/laranail) — Simtabi's Laravel utility toolbox.

## License

MIT. See [LICENSE.md](LICENSE.md).
