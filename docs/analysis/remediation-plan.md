# laravel-modules (scaffolder) — analysis & remediation plan

## Context

The repo at `…/laranail/package/scaffolder` is a **pristine, current clone of
`nwidart/laravel-modules`** — local `master` == `origin/master` (0 ahead / 0
behind), origin points straight at upstream, composer name is still
`nwidart/laravel-modules`, latest commit 2026-04-13 (≈ `v13.0.0` + 5). There is
**no local drift** to reconcile. So the original "merge our feature branches"
framing collapses: the real work is (a) fix confirmed upstream bugs, (b) pull in
the small set of still-open upstream PRs that fix issues we care about, and (c)
salvage a couple of unique scraps from stale branches.

**Signed-off decisions** (2026-06-29):
1. **Local only.** All work is **local commits** — no pushes, no remote branches,
   no PRs. Fetching upstream PR commits to cherry-pick is read-only against
   origin and lands locally. First confirm local `master` reflects the remote
   (already 0/0 vs `origin/master`).
2. **Identity:** keep `nwidart/laravel-modules` identity for now; laranail
   rebrand (composer name, `laranail::package-scaffolder.*` commands, Simtabi
   metadata/required files) is a **separate later effort**, not in this pass.
3. **PR sourcing:** cherry-pick upstream PRs where clean; drop overlapping/unclear ones.
4. **Scope:** **fix everything this pass** — every confirmed bug, gap, and
   inconsistency, including the two big structural bugs (#2159, #2164) and the
   lower-priority docs/feature items. Nothing deferred. Breaking changes to
   generated output are acceptable with tests + CHANGELOG notes. More
   functionality will arrive in subsequent refactors, so leave the code in a
   clean, well-tested, current state to build on.
5. **Branches:** salvage unique bits (phpstan config/types from `add-larastan`);
   otherwise leave remote branches alone.

---

## Phase 1 — Repo analysis (summary)

- **Stack:** PHP `^8.3`; Laravel `^13.0`; PHPUnit `^12`; Orchestra Testbench
  `^11`; `wikimedia/composer-merge-plugin ^2.1` (the only runtime dep). PSR-4
  `Nwidart\Modules\ → src/`, helpers auto-loaded from `src/helpers.php`.
- **Public API:** `Module` facade (bound `'modules'` → `RepositoryInterface`);
  ~60 Artisan commands (40+ `module:make-*` generators, lifecycle actions, 7
  database commands, 5 publish commands); service providers
  `LaravelModulesServiceProvider` + `ConsoleServiceProvider`; config at
  `config/config.php`.
- **Scaffolding flow:** `ModuleMakeCommand` → `ModuleGenerator` (folders →
  `module.json` → stub files → sub-`make` resources → activate → dump-autoload).
  Stubs in `stubs/` rendered by `src/Support/Stub.php` via
  `$PLACEHOLDER$` substitution; replacements resolved in
  `ModuleGenerator::getReplacement()`.
- **Tests:** 91 test files, Testbench-based (`tests/BaseTestCase.php`),
  snapshot assertions. CI = `.github/workflows/php.yml`, matrix PHP 8.3/8.4/8.5
  (`composer test` + Pint auto-commit).
- **Static analysis gap:** `phpstan/phpstan ^2.0` is **installed but has no
  config** (no `phpstan.neon`); no Larastan/Rector. Pint (`laravel` preset) is
  the only enforced check.
- **Coverage gaps (~0%):** Publishing, Process, Database commands, Generators,
  Lumen, most Exceptions/Contracts.

## Phase 2 — Issues, PRs, branches (from GitHub, cited)

### Open issues (13) — verified against local code

| # | Title | Area | Severity | Verdict |
|---|---|---|---|---|
| [2159](https://github.com/nwidart/laravel-modules/issues/2159) | migrate vs rollback/reset use two Migrator impls | Database | **critical** | REPRODUCES |
| [2164](https://github.com/nwidart/laravel-modules/issues/2164) | `…ServiceProvider not found` (path/namespace mismatch) | Generators/Config | **high** | REPRODUCES |
| [2128](https://github.com/nwidart/laravel-modules/issues/2128) | L12 `withEvents()` discovery ignores modules | Providers | **high** | likely (not yet code-verified) |
| [2110](https://github.com/nwidart/laravel-modules/issues/2110) | route provider maps web/api even when disabled | Generators/Stubs | **high** | REPRODUCES |
| [2158](https://github.com/nwidart/laravel-modules/issues/2158) | `module_path()` fatal on null `find()` | Helpers | **high** | REPRODUCES |
| [2163](https://github.com/nwidart/laravel-modules/issues/2163) | HMVC docs omit merge-plugin step | Docs | medium | docs |
| [2152](https://github.com/nwidart/laravel-modules/issues/2152) | `app_path('app')` vs `'app/'` not normalized | Traits | medium | REPRODUCES |
| [2151](https://github.com/nwidart/laravel-modules/issues/2151) | `SeedCommand::executeAction()` return mismatch | Database | medium | REPRODUCES |
| [1861](https://github.com/nwidart/laravel-modules/issues/1861) | seeders not found in custom scan paths | Database | medium | likely (not yet code-verified) |
| [2148](https://github.com/nwidart/laravel-modules/issues/2148) | `assets.generate=false` still makes dir | Generators | low | REPRODUCES |
| [2149](https://github.com/nwidart/laravel-modules/issues/2149) | `views.generate` misnamed | Config | low | design (cannot-tell) |
| [2147](https://github.com/nwidart/laravel-modules/issues/2147) | auto-generate base seeder | Feature | low | feature |
| [2120](https://github.com/nwidart/laravel-modules/issues/2120) | module blade hot-reload | Docs | low | docs |

Recurring closed issues confirm #2164 is systemic: #2124, #2127, #2137, #2105,
#2109 are all the same "ServiceProvider not found" root cause.

### Open PRs (5)

| PR | Head branch | Does | Decision |
|---|---|---|---|
| [2129](https://github.com/nwidart/laravel-modules/pull/2129) | `fix-2110` | configurable web/api route generation + Stub "removal tag" API; fixes #2110 | **cherry-pick** |
| [2162](https://github.com/nwidart/laravel-modules/pull/2162) | `fix/module-path-null-safe-v2` | null-safe `module_path()`; fixes #2158 | **cherry-pick** |
| [2157](https://github.com/nwidart/laravel-modules/pull/2157) | `boost/add-laravel-boost-skills` | move Boost skills to canonical `resources/boost/` | **cherry-pick** |
| [2161](https://github.com/nwidart/laravel-modules/pull/2161) | `fix/route-provider-check-files` | route-file existence check | **drop** (subsumed by #2129) |
| [2153](https://github.com/nwidart/laravel-modules/pull/2153) | `patch-1` | vague 30-line config churn | **drop** (unclear/risky) |

### Branches — nothing to merge

All 15 non-release branches are stale or already merged into master. The only
unique scraps worth salvaging: **`add-larastan`** (`phpstan.neon` + type hints —
fills the unconfigured-phpstan gap). The rest (`FixModuleLoading`, `patch-56`,
`reafactor`, `tareq-alqadi/master`, etc.) are superseded by current master and
are dropped. Release branches `1.0–7.0` are historical, untouched.

---

## Remediation plan (grouped by area)

### A. Generators / Stubs / Config

- **#2164 (high, breaking)** — provider FQCN vs file location mismatch.
  - Root: `ProviderMakeCommand::getDefaultNamespace()` (`src/Commands/Make/ProviderMakeCommand.php:38-42`)
    `ltrim()`s `app/` off the path so namespace = `Providers`, but the file is
    written under `…/app/Providers/`; PSR-4 then can't autoload it.
  - Fix: make the registered provider namespace consistent with the on-disk
    path. Preferred: derive the namespace from the **full generator path**
    (including `app/` → `App`) via the existing `PathNamespace` trait
    (`src/Traits/PathNamespace.php`) rather than stripping `app_folder`, so the
    generated `module.json` `providers[]` FQCN and the `composer.json` PSR-4 map
    agree with the file path. Apply the same logic anywhere a generator computes
    a namespace from a path that contains `app_folder` (controllers, etc.).
  - Breaking: changes generated `module.json`/namespaces. Document in CHANGELOG +
    add an upgrade note. Update affected snapshot tests deliberately.
  - Tests: extend `tests/Commands/Make/ProviderMakeCommandTest` and the
    `module:make` end-to-end test to assert the generated provider FQCN resolves
    to the actual file path; add a regression test that boots a generated module.

- **#2110 (high)** — via **cherry-pick of PR #2129**: adds
  `'web' => true / 'api' => true` under routes config, `shouldGenerateWebRoutes()`/
  `shouldGenerateApiRoutes()` in `RouteProviderMakeCommand`, conditional
  `%START_WEB_ROUTES%…%END_WEB_ROUTES%` markers in `route-provider.stub`, and the
  removal-tag engine in `src/Support/Stub.php`. Keep its tests; verify the new
  Stub tag API is covered (it becomes public surface).

- **#2148 (low)** — `assets.generate=false` still creates `resources/assets`
  because `generateFiles()` auto-creates stub parents (`ModuleGenerator.php:381`)
  for the `assets/js/app` + `assets/sass/app` stub entries regardless of the
  flag. Fix: in `generateFiles()`, skip stub files whose owning generator group
  has `generate=false` (reuse `GenerateConfigReader` to check the flag), mirroring
  how `generateFolders()` already gates folders. Test: assert no `resources/assets`
  dir after `module:make` with assets disabled.

- **#2149 (low, design)** — clarify `views.generate` semantics. Lowest priority;
  implement as an **alias** (`views.index.generate` honored, old key still
  respected with a deprecation note) to avoid a breaking config rename. Confirm
  intended behavior before coding (only ambiguous item).

### B. Helpers / Traits

- **#2158 (high)** — via **cherry-pick of PR #2162**: null-safe `module_path()`
  in `src/helpers.php` (fall back to default `Modules/{name}` path when
  `find()` is null). Add a unit test in `tests/HelpersTest.php` for the null path.

- **#2152 (medium)** — `app_path()` in `src/Traits/PathNamespace.php:55-74`
  doesn't normalize the configured `app_folder` (`app` vs `app/` vs `App`). Fix:
  normalize trailing slash/case once at read time. Add `tests/Traits/PathNamespaceTest`
  cases for `app`, `app/`, `App`, and a custom `src` folder.

### C. Database commands

- **#2159 (critical, structural)** — unify the migrate code paths.
  - Root: `MigrateCommand` delegates to Laravel core `migrate`
    (`Illuminate\Database\Migrations\Migrator`, path-registered, provider-boot
    dependent) while `MigrateRollbackCommand`/`MigrateResetCommand` instantiate
    the custom `Nwidart\Modules\Migrations\Migrator` (filesystem scan, single
    dir). Causes disabled-module asymmetry, multi-path blind spots, and silent
    no-ops on `refresh`.
  - Fix direction: make all of migrate/rollback/reset/refresh/status use **one**
    discovery source. Safest unification is to route rollback/reset/refresh
    through the same path set the forward migrate uses (the registered
    `loadMigrationsFrom()` paths / module migration dirs), so every command sees
    the same migrations. Add explicit warnings instead of silent exit-0 when a
    module is disabled or a path is empty.
  - Risk: data-integrity sensitive. Build behind thorough tests in a dedicated
    commit; cover disabled modules, multi-path modules, and the full
    migrate→rollback→refresh cycle on the in-memory SQLite testbench. This is the
    first area with **0% existing coverage**, so tests are net-new.

- **#2151 (medium)** — `SeedCommand::executeAction()` returns `false` where the
  Task component expects the integer enum value. Fix: return
  `TaskResult::Failure->value` on failure (and the success value otherwise);
  confirm the exact declared signature first and align it. Add a test asserting
  the command's exit/status on a failing seeder.

- **#1861 (medium)** — seeders not discovered for modules in custom `scan.paths`.
  Verify in code (not yet confirmed): seeder resolution likely uses the default
  modules path instead of going through `RepositoryInterface::find()`/scan
  config. Fix: resolve the module via the repository (which honors scan paths)
  before seeding. Add a test with a module under a custom scan path.

### D. Providers / framework integration

- **#2128 (high)** — Laravel 12 `withEvents(discover: […])` doesn't pick up
  module listeners (they live under `Modules/*/app/Listeners`, not `app/`). Needs
  code verification first, then integrate with `DiscoverEvents` (e.g. register a
  `guessClassNamesUsing` resolver that maps module listener paths → module
  namespaces) in `LaravelModulesServiceProvider`. Medium-large; gate behind a
  test that discovers a listener in a generated module.

### E. Tooling salvage / hardening

- **Configure phpstan** (salvage from `add-larastan`): add a `phpstan.neon`
  (level + `src/` paths), bring over the still-relevant type hints, and add a
  `phpstan` CI step to `.github/workflows/php.yml`. Drop anything from that 2024
  branch that conflicts with current code; re-derive rather than force-merge.

### F. Docs & small features (same pass — included, not deferred)

- **#2163** add the `composer-merge-plugin` step to the HMVC setup docs.
- **#2120** document module-aware `vite.config.js` `refreshPaths` and seed it in
  the `vite.stub` so new modules hot-reload out of the box.
- **#2147** auto-generate the base seeder when creating a module seeder (gated by
  a config flag, default on) so seeders don't fail on a missing base. Add a test.

---

## Branch / PR execution strategy

All commits are **local only** (no push). Cherry-picks fetch upstream PR heads
read-only and commit locally.

**Cherry-pick order** (origin == upstream, so PR heads are fetchable via
`git fetch origin pull/<n>/head` or `gh pr checkout`):

1. **#2162** (`module_path` null-safety) — isolated, touches only `helpers.php`.
   Lowest conflict risk. Land first.
2. **#2129** (web/api route config + Stub removal-tags) — touches
   `config/config.php`, `RouteProviderMakeCommand.php`, `route-provider.stub`,
   `src/Support/Stub.php`. Land before our #2164 work so route-provider snapshots
   settle once.
3. **#2157** (Boost skills relocation) — adds/moves files under `resources/boost/`;
   near-zero conflict with code. Land any time; do it after code fixes to keep
   diffs clean.

**Expected conflicts:** #2129 and our #2164 fix both touch generated-output
snapshots and the provider/route generation area — sequence #2129 first, then
#2164, regenerating snapshots once after each so changes are reviewable. The
phpstan salvage may surface new type errors in untouched files — fix or baseline
them explicitly (no blanket ignores).

**Dropped:** PRs #2161, #2153; branches `FixModuleLoading`, `patch-56`,
`reafactor`, `tareq-alqadi/master`, and all already-merged/stale branches.

---

## Commit sequencing (small, scoped, lowercase)

1. `cherry-pick module_path null-safety fix (#2158/#2162)` + test
2. `cherry-pick configurable web/api route generation (#2110/#2129)` + snapshots
3. `fix provider namespace to match generated path (#2164)` + tests + CHANGELOG
4. `gate asset stub generation on assets.generate flag (#2148)` + test
5. `normalize app_folder handling in app_path() (#2152)` + tests
6. `fix SeedCommand executeAction return value (#2151)` + test
7. `resolve module seeders via scan paths (#1861)` + test
8. `unify module migration code paths (#2159)` + net-new migration tests + CHANGELOG
9. `integrate laravel 12 event discovery for modules (#2128)` + test
10. `add phpstan config and ci step (salvage add-larastan)` 
11. `cherry-pick boost skills relocation (#2157)`
12. `docs: hmvc merge-plugin + module vite hot-reload (#2163/#2120)`
13. `clarify views.generate config naming (#2149)`

Each fix lands with its test(s); CHANGELOG updated for the breaking ones
(#2164, #2159) with upgrade notes. `git config user.email
imanimanyara@users.noreply.github.com` set before committing (per conventions).

## Decisions already signed off (recorded)

- Identity: **no rebrand this pass** (deferred).
- PRs: **cherry-pick #2129/#2162/#2157; drop #2161/#2153**.
- Scope: **include #2159 and #2164** (breaking changes OK, with tests + CHANGELOG).
- Branches: **salvage `add-larastan` phpstan/types**; leave the rest.

Remaining items needing a quick confirm during execution (not blockers):
- #2149 exact desired config-key behavior (alias vs rename).
- #2164 fix shape — confirm we want the `App\`-segment namespace (vs dropping
  `app/` from the path) once I see how it ripples through snapshots.

---

## Verification

- **Unit/feature:** `composer test` (PHPUnit 12 on Testbench). Add/refresh
  snapshots with `composer update-snapshots` only after eyeballing the diff.
- **Per-fix end-to-end:** in a Testbench app, `php artisan module:make Blog`,
  then assert: the generated provider autoloads and boots (#2164); disabling
  `routes/web` produces a provider with no `mapWebRoutes()` and artisan still
  runs (#2110); `assets.generate=false` leaves no `resources/assets` (#2148);
  `module_path('Missing')` returns a path instead of fatal (#2158).
- **Migrations (#2159):** test the full migrate → rollback → refresh cycle
  against in-memory SQLite for an enabled module, a disabled module, and a
  multi-path module; assert no silent no-ops.
- **Static analysis:** `vendor/bin/phpstan analyse` clean at the chosen level.
- **Style:** `composer lint` (Pint) clean.
- **CI:** green across PHP 8.3 / 8.4 / 8.5.
