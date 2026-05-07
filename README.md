# laranail/package-scaffolder

> Artisan command suite + 139 stubs for scaffolding new Laravel packages.

`package-scaffolder` generates a complete, opinionated Laravel package
skeleton — composer.json, service provider, config, views, routes,
migrations, tests, GitHub workflows, and more. Generated packages extend
[`laranail/package-tools`](../package-tools/) so they inherit the fluent
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
php artisan make:package vendor/widget
```

The scaffold writes a new package skeleton at `packages/vendor/widget/`
(configurable). Review the generated `composer.json` + service provider
and you're ready to extend `laranail/package-tools`.

## Available Artisan commands

```
make:package                  # scaffold a new package
package:install <name>        # run a generated package's install command
package:uninstall <name>
package:enable <name>
package:disable <name>
package:remove <name>
package:list                  # list discovered packages
package:status <name>
package:validate <name>
package:bug-hunt <name>       # static-analysis sweep (delegates to Scaffolder/BugHunter)
package:security-check <name>
package:git <name> <op>
package:publish-tests <name>
package:doctor                # via laranail/package-tools (after install)
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

- Primary: [`opensource.simtabi.com/package-scaffolder/docs/`](https://opensource.simtabi.com/package-scaffolder/docs/)
- Portal: [`opensource.simtabi.com/package-scaffolder/`](https://opensource.simtabi.com/package-scaffolder/)
- Master plan: [.plans/CLEANUP-MASTER-PLAN.md](.plans/CLEANUP-MASTER-PLAN.md)
- Codemod archive: [scripts/codemod-archive.md](scripts/codemod-archive.md)

## Sister packages

- [`laranail/package-tools`](https://github.com/laranail/package-tools) — runtime base library (required dependency for generated packages).
- [`laranail/database-tools`](https://github.com/laranail/database-tools) — independent Laravel DB utilities; usable by any Laravel app.
- [`laranail/laranail`](https://github.com/laranail/laranail) — Simtabi's Laravel utility toolbox.

## License

MIT. See [LICENSE.md](LICENSE.md).
