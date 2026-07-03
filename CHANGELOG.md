# Changelog

All notable changes to `laranail/package-scaffolder` will be documented in this file.

## Next

### Class reorganisation into type folders (breaking)

Every package class now lives in a type folder whose path mirrors its namespace
segment. The eight previously root-level classes moved — update your imports:

| Old FQCN | New FQCN |
|---|---|
| `Simtabi\Laranail\Package\Scaffolder\Collection` | `…\Support\Collection` |
| `Simtabi\Laranail\Package\Scaffolder\Json` | `…\Support\Json` |
| `Simtabi\Laranail\Package\Scaffolder\ModuleManifest` | `…\Support\ModuleManifest` |
| `Simtabi\Laranail\Package\Scaffolder\Module` | `…\Support\Module` |
| `Simtabi\Laranail\Package\Scaffolder\FileRepository` | `…\Repositories\FileRepository` |
| `Simtabi\Laranail\Package\Scaffolder\ModulesServiceProvider` | `…\Providers\ModulesServiceProvider` |
| `Simtabi\Laranail\Package\Scaffolder\LaravelModulesServiceProvider` | `…\Providers\LaravelModulesServiceProvider` |
| `Simtabi\Laranail\Package\Scaffolder\LumenModulesServiceProvider` | `…\Providers\LumenModulesServiceProvider` |
| `Simtabi\Laranail\Package\Scaffolder\Support\ModuleServiceProvider` | `…\Providers\ModuleServiceProvider` |

The last row is the abstract base that **generated module providers extend** (via
the `module:make-provider` stub): already-generated modules import
`…\Support\ModuleServiceProvider` and must update to `…\Providers\ModuleServiceProvider`
(newly-generated modules use the new path automatically).

The published service-provider reference (Laravel auto-discovery / `vendor:publish`)
is now `…\Providers\LaravelModulesServiceProvider`. The framework-variant classes
under `Laravel\` and `Lumen\` are unchanged. No behavior changed — this is a
namespace/path move only. See `docs/ARCHITECTURE.md`.

### Package identity (breaking)

- Published as `laranail/package-scaffolder` by Simtabi LLC.
- PHP root namespace `Simtabi\Laranail\Package\Scaffolder\`; the published service provider is `Simtabi\Laranail\Package\Scaffolder\Providers\LaravelModulesServiceProvider`. The user-facing module namespace (`Modules\`, config-driven) is unchanged.
- Artisan commands use the `laranail::package-scaffolder.*` shape (e.g. `laranail::package-scaffolder.make`), with the `module:*` names kept as **aliases** so existing scripts keep working.
- PHP floor `^8.4.1 || ^8.5` (CI matrix 8.4 / 8.5) — required by the `laranail/console` command base used for the `::` naming.

### Fixes & features

- Fixed generator namespaces being corrupted when stripping the app folder prefix: `ltrim($path, $appFolder)` treated the app folder as a character mask, so paths like `app/api` became `pi` and custom `app_folder` values (e.g. `src/`) were mishandled. Now stripped as a proper prefix via `PathNamespace::strip_app_folder()` (#2164, #2124, #2152).
- Prevented a fatal error in the `module_path()` helper when the module registry is not yet resolved; it now falls back to the configured modules path (#2158).
- Added configurable web/api route generation: set `paths.generator.routes.web`/`api` to `false` to omit the matching `map*Routes()` method from a module's RouteServiceProvider (#2110).
- Fixed `PathNamespace::app_path()` so `app`, `app/`, `App` and a custom `app_folder` are handled consistently; a bare `app` no longer produces a duplicated path such as `src/app` (#2152).
- Fixed `paths.generator.assets.generate = false` still creating the `resources/assets` directory: the asset stub files were written regardless of the flag (#2148).
- Fixed `module:seed` reporting a failed seeder as `DONE`: the task callback returned `false`, which the console task component (strict `match` on `TaskResult::*->value`) renders as success; it now returns `TaskResult::Failure->value` (#2151).
- Fixed `module:seed` not discovering the database seeder for modules in custom scan paths whose namespace differs from the default: the seeder is now also resolved from the module's own `composer.json` psr-4 namespace (#1861).
- Moved the Laravel Boost AI skills and guidelines to the canonical `resources/boost/` location and converted the skill to Markdown (#2157).
- `module:make-seed` now auto-generates the module's base `{Module}DatabaseSeeder` when it is missing (opt out with `--without-base`) (#2147).
- The generated module `vite.config.js` now watches the module's own `resources/views` and `routes` so Blade/route changes hot-refresh during `npm run dev` (#2120).
- `paths.generator.views.generate = false` now also skips the `index`/`master` view stub files, not just the views directory (#2149).
- Documented that the `Class ...ServiceProvider not found` error is caused by the composer-merge-plugin step (allow-plugins + dump-autoload) (#2163).
- Configured PHPStan (level 5 over `src/`, with a baseline of pre-existing issues) and added it to CI via a `composer analyse` script, so new code is statically analysed.
- Fixed `Process\Installer::getRepoUrl()` missing a `return null` on its fallthrough path.
- Added module-aware event discovery: Laravel's event discovery (an app-level `withEvents(discover: [...])` over module listener directories, or a module's own EventServiceProvider) now resolves listener files under `Modules/{Module}/app/Listeners` to the module's real namespace instead of the application namespace. Toggle via `auto-discover.events` (#2128).
- Unified the module migration commands so `module:migrate`, `module:migrate-rollback`, `module:migrate-reset`, `module:migrate-refresh` and `module:migrate-status` all operate on the same migration path set (registered paths plus the module's own migration directory) via Laravel's core migrator. This removes the previous asymmetry where rollback/reset used a separate filesystem migrator: disabled and multi-path modules are now handled consistently, and a module with no migrations warns instead of silently doing nothing (#2159). Note: explicitly targeting a disabled module with `module:migrate` now runs its migrations instead of silently no-oping.
- [@omerbaflah](https://github.com/omerbaflah) Fixes Invokable Controller Stub
- [@solomon-ochepa](https://github.com/solomon-ochepa) Added create module:make-class command

### Tooling & conventions

- Aligned the repo with the laranail conventions: `# laranail/package-scaffolder` README with the 4-badge
  set + standard section spine; a bare `LICENSE`; `UPGRADING.md`; `.github/` dependabot + PR template +
  `tests` / `static-analysis` / `security` / `release` workflows; `docs/` renamed/expanded
  (`architecture.md`, new `installation.md` / `configuration.md` / `release.md`).
- Added **Rector** to the gate (`composer lint` = Pint + PHPStan + Rector; CI dry-run) and cleaned the
  codebase across the code-quality, dead-code, early-return, mechanical PHP-8.x **and TYPE_DECLARATION**
  sets. A few rules are skipped in `rector.php` with inline reasons (notably `StrictArrayParamDimFetchRector`,
  which mistyped Laravel container closures' `$app` as `array`). PHPStan baseline trimmed as issues were fixed.
- composer scripts brought to convention (`setup`, `test-dirty`, `pint`/`pint-fix`, `phpstan`, `rector`/
  `rector-fix`, `composer-audit`); `config.sort-packages`; keywords lead with `laravel`.


---

_Release history begins with the `laranail/package-scaffolder` line; earlier tags predate this project._
