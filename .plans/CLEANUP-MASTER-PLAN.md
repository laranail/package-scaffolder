# Laranail Package Suite — Master Plan v6

**Created:** 2026-05-04 (v1) — heavily revised through v9 (2026-05-04). See §13 changelog. **All decisions resolved (§10); ready to execute.**
**Scope:** Design and ship a coherent four-package Laravel suite under the `laranail/*` organization, extracting the current `laranail/packager` repo into purpose-built **polyrepos** and adding the missing functionality the original audit surfaced. Tooling is pure PHP/Composer; no Python.
**Targets:** PHP 8.3+ (recommend 8.4), Laravel 13+ (released 2026-03-17), Pest 3+, Testbench 10+.
**Repo layout:** every package is its own git repo at `/Users/imanimanyara/Artisan/projects/opensource/laranail/<package-name>/`. No monorepo, no subtree-split.
**Docs site:** `opensource.simtabi.com/` (primary) and `opensource.simtabi.com/laranail/{docs, <package-name>}` (Simtabi-organization opensource portal).
**Plan location:** `.plans/CLEANUP-MASTER-PLAN.md` (canonical, hidden, well-designed). `.plans/reference/` reserved for ADRs.

---

## 1. Vision

A senior-engineered Laravel 13 package family that does for package authors what Laravel does for app authors: ship batteries-included, fluent, attribute-driven, opinionated tooling that elevates the floor without forcing ceiling decisions.

- **Better than Spatie.** Match `spatie/laravel-package-tools` v1.93.0 line-for-line in API surface; then add the gaps it leaves: a `package:doctor` health check, attribute-driven command/route/facade discovery, an isolation testing harness, SBOM emission, OSV scanning, IDE-helper auto-generation, an interactive install wizard.
- **SOLID, DRY, KISS, fluent OOP.** Builder + Pipeline + Strategy patterns. No god classes, no orphan traits, no half-implementations.
- **Lose nothing.** Every feature currently in `src/Package/Concerns/Package/` stays — including the 26 currently un-wired traits — actively wired the spatie way (aggregator pattern; max ~10 per class).
- **Forward-targeting.** PHP 8.3+ floor, recommend 8.4 features (property hooks, asymmetric visibility) where they materially reduce code. `#[\Override]` everywhere. Drop pre-Laravel-13 compat.

---

## 2. Package family

Four Composer packages, each in its **own polyrepo** at `/Users/imanimanyara/Artisan/projects/opensource/laranail/<package-name>/`. No monorepo, no subtree-split, no Python. Cross-cutting changes are coordinated via per-repo PRs.

| # | Package | Local repo path | Description |
|---|---|---|---|
| 1 | **`laranail/laranail`** | `/Users/imanimanyara/Artisan/projects/opensource/laranail/laranail/` (exists) | Utility toolbox: Gravatar, Captcha, Avatar, Archiver, Notifications, Foundation/, Shared/, Support/, Laravel/. **Stays as-is**, no longer the destination for runtime Package code. |
| 2 | **`laranail/package-tools`** | `/Users/imanimanyara/Artisan/projects/opensource/laranail/package-tools/` (new) | Runtime base library. Source: `packager/src/Package/` extracted, namespace `Simtabi\Laranail\PackageTools\…`. Fluent `Package` builder + abstract `PackageServiceProvider`. **51 traits actively wired** via aggregator pattern. Adds beyond Spatie: `package:doctor`, attribute discovery, SBOM (via Composer plugin), isolation harness — all native Artisan commands. |
| 3 | **`laranail/package-scaffolder`** | `/Users/imanimanyara/Artisan/projects/opensource/laranail/package-scaffolder/` (rename of `packager/`) | Generator. Artisan commands + 139 stubs. Depends on `package-tools` so generated packages inherit the runtime base. |
| 4 | **`laranail/database-tools`** | `/Users/imanimanyara/Artisan/projects/opensource/laranail/database-tools/` (new) | Framework-agnostic Laravel DB utilities: model traits (UUID, soft-delete-with-undo, audit), schema macros, observer base classes, eager-load helpers. **No deps on `package-tools`**; zero Laranail coupling beyond illuminate contracts. Optional `package-tools` integration via a thin glue trait. |

**Dependency graph (run-time):**

```
laranail/laranail            (independent)
laranail/database-tools      (independent — illuminate/database only)
laranail/package-tools       (independent — illuminate/contracts only)
laranail/package-scaffolder
    └── requires: laranail/package-tools (^1.0)
```

No circular deps. `package-tools` and `database-tools` are siblings, both independent. `package-scaffolder` is the only consumer with a hard dep on a sibling Laranail package.

**Polyrepo coordination:**
- Each repo is independent on Packagist with its own SemVer.
- Cross-cutting refactors (e.g., a shared lint config bump) are tracked via a single GitHub issue cross-linked from per-repo PRs.
- A small `laranail/.github/` profile-org repo holds shared workflow templates (reusable workflows) that each package's `.github/workflows/*.yml` calls via `uses: laranail/.github/.github/workflows/<workflow>.yml@main`.
- **Tooling is pure PHP/Composer.** `composer setup`/`test`/`lint`/`audit` aliases are the contributor surface in every repo. No Python toolchain.

---

## 3. Architectural decisions (key ADRs)

These will become formal `docs/adr/NNNN-*.md` files using the Michael Nygard template (status: proposed → accepted → superseded). Captured here as the load-bearing decisions.

### ADR-001 — Five packages, not one

**Status:** Accepted (user direction, v6).
**Context:** The current `laranail/packager` bundles a runtime library, a generator, dev tooling, and stubs. Different audiences, different release cadences, different stability contracts.
**Decision:** Four packages: `laranail/{laranail, package-tools, package-scaffolder, database-tools}`. (Earlier revisions specified five — included `laranail/package-scaffolder-python` — dropped in v8 per ADR-007.)
**Consequences:** More release machinery, more CI surface. Resolved by ADR-002.

### ADR-002 — Polyrepo, no monorepo

**Status:** Accepted (user direction, v7).
**Context:** A monorepo with subtree-split publishing was considered (and recommended in v6) for coherent cross-cutting refactors. User direction in v7: each package gets its own independent git repo at `/Users/imanimanyara/Artisan/projects/opensource/laranail/<package-name>/`. Simpler ownership story; aligns with each package's independent Packagist/PyPI lifecycle; mirrors how the rest of the Laranail organization is laid out.
**Decision:** Five separate polyrepos. Cross-cutting concerns (shared workflows, shared lint config) handled via a `laranail/.github/` org-level profile repo that exposes reusable workflows callable from each package's `.github/workflows/*.yml`. Cross-cutting refactors tracked by a single GitHub issue with per-repo PRs cross-linked.
**Consequences:** No `symplify/monorepo-builder`, no subtree-split tooling, no monorepo build orchestrator. Each repo self-contained: own CI, own CHANGELOG, own SBOM, own version branches. Cross-cutting changes are slower (two-PR minimum) but each repo is mentally simpler. Reusable workflows in `laranail/.github` keep CI duplication low.

### ADR-003 — Targets: PHP 8.3+, Laravel 13+

**Status:** Accepted (user direction, v6).
**Context:** Laravel 13 shipped 2026-03-17 with zero breaking changes from 12 and a PHP 8.3 floor. Carrying Laravel 9–12 compat costs more than it earns for a fresh package.
**Decision:** Floor: PHP `^8.3`, Laravel `^13.0`. Recommend PHP 8.4; opportunistically use property hooks + asymmetric visibility where they materially reduce code.
**Consequences:** Drop pre-13 testing matrix. Smaller compat surface. Some prospective consumers stuck on 11/12 will need to wait for an LTS branch (defer to a `1.x-laravel11` branch only if requested).

### ADR-004 — Trait composition: aggregator pattern, not direct multi-use

**Status:** Accepted (research-driven, v6).
**Context:** `src/Package/Concerns/Package/` holds 51 traits today. PHP supports `insteadof`/`as` for trait conflicts but the resulting code is unreadable past ~10 traits per class.
**Decision:** Group concerns by domain into aggregator traits (`Concerns\ConfiguresConfig`, `Concerns\ConfiguresViews`, …). `Package` and `PackageServiceProvider` use the aggregators only. Aggregators may use ~10 leaf traits each, with internal conflict resolution if needed. Spatie's `unique-by-prefix` naming (`hasX`, `usesX`, `processX`) stays the rule for first-party trait families — `insteadof` reserved for genuine third-party clashes.
**Consequences:** All 26 currently un-wired traits get wired through their domain aggregator (Decision 1d in §10). No spaghetti `insteadof` blocks. Diff from Spatie's pattern: we have more aggregators, but each is small and coherent.

### ADR-005 — Fluent return convention: `$this` for chaining, `static` for terminal

**Status:** Accepted (user direction, v6).
**Context:** Spatie returns `$this` everywhere; the original packager mixes `static` and `$this`. Both are valid PHP; `static` enables subclass-typed returns from inherited methods, `$this` is simpler.
**Decision:** Fluent (chainable) methods return **`$this`** for clarity and Spatie-compatibility. Methods that don't chain return **`static`** when factory-style or **`void`/concrete** otherwise. The codemod normalizes against this rule.
**Consequences:** Slight subclass-typing loss on inherited fluent methods, accepted in exchange for readability and ecosystem compatibility.

### ADR-006 — Configuration: JSON for shape, Laravel `.env` for secrets, append-only writes

**Status:** Accepted (user direction, v3 onward).
**Context:** Tooling needs structured config; runtime needs to read/write the host Laravel app's `.env` without ever destroying consumer-owned data.
**Decision:** Non-secret runtime config lives in standard Laravel `config/<package>.php` files (the Laravel idiom). Tool-level config (PHPStan/Pint/Rector) lives in their native config files (`phpstan.neon`, `pint.json`, `rector.php`). Secrets in the host's Laravel `.env`, discovered via `Application::environmentFilePath()` first, walk-up fallback otherwise. Writes are append-only (`appendIfMissing`, `appendBlock`), atomic (`tmp + rename`), with transparent `.bak.<timestamp>` backups. `forceSet` exists but is gated by an explicit `acknowledgeDestructive: true` argument and emits `EnvFileMutated`. Package-owned keys are prefixed `LARANAIL_*`.
**Consequences:** No clobbering of consumer `.env`. One service to build (`Simtabi\Laranail\PackageTools\Services\Environment\EnvFileService`). Detailed in §4.3. (Earlier revisions specified a parallel Python `lib/env.py`; v8 dropped Python — see ADR-007.)

### ADR-007 — Tooling is pure PHP/Composer; no Python

**Status:** Accepted (user direction, v8). Supersedes earlier Python-first stance.
**Context:** Earlier revisions designed a Python CLI package (`laranail/package-scaffolder-python`) for development tooling. Re-examination showed (a) most of the original `/scripts/` were one-shot codemods that already finished their work, (b) the rest are wrappers around tools that already have first-class PHP/Composer interfaces (Pest, PHPStan, Pint, Rector, `composer audit`), and (c) adding Python forces every contributor to maintain a second toolchain.
**Decision:** Tooling is pure PHP/Composer. Contributors use composer script aliases (`composer setup` / `test` / `lint` / `audit`). Bootstrap is a single small bash script (`scripts/init.sh`, ~40 lines) per repo — verifies PHP + Composer, runs `composer install`, validates `.env` discovery, smoke-checks lint/tests. **No Python, no `uv`, no `rich`, no `pyproject.toml`, no PyPI package.** Codemods, when needed, use Rector (the standard PHP codemod tool). Cross-cutting suite checks, when needed, use a small `gh`-based shell snippet in `laranail/.github`.
**Consequences:** Smaller cognitive surface for PHP contributors. ~16 hours dropped from the suite estimate. `package:doctor` / `package:audit` / `package:sbom` become native Laravel Artisan commands in `package-tools` (more idiomatic — they need to inspect an installed Laravel app's container anyway). CI gate stays: `find . -type f -name "*.sh" -not -path './vendor/*'` returns exactly one path (`scripts/init.sh`).

### ADR-008 — Plans live in `.plans/`

**Status:** Accepted (user direction, v4).
**Context:** The hidden `.plans/` directory was intentionally well-designed.
**Decision:** Master plan at `.plans/CLEANUP-MASTER-PLAN.md`. ADRs at `docs/adr/NNNN-*.md` per package. `.plans/reference/` reserved for cross-cutting reference docs (naming conventions, supported version matrices, etc.). The visible `plans/` directory is **not** used.

### ADR-009 — Attribute-driven discovery is the differentiator vs Spatie

**Status:** Proposed (research-driven, v6).
**Context:** Laravel 13 added `#[Boot]`, `#[Initialize]`, `#[Scope]`, `#[ScopedBy]` attributes; the ecosystem (`spatie/laravel-auto-discoverer`) supports attribute-driven discovery as a primitive. Spatie's `package-tools` does not yet expose this.
**Decision:** `package-tools` ships first-party attributes — `#[AsArtisanCommand]`, `#[AsRoute]`, `#[AsFacade]`, `#[AsViewComposer]` — and a `Package::discoversWithAttributes()` fluent step that scans the package's `src/` for them. Spatie's path-based fluent steps remain as the explicit fallback.
**Consequences:** Bigger surface area; needs a doctor command to validate that attributes resolved as expected; differentiates the package from Spatie's offering and aligns with Laravel 13's direction.

### ADR-010 — Database-tools is genuinely independent

**Status:** Proposed (open Decision 9 — recommend acceptance).
**Context:** The user asked whether to split DB-related code into `laranail/database-tools` "fully independent, no dependencies."
**Decision:** Yes, split. **Scope:** general-purpose Laravel DB utilities (model traits, schema macros, audit observers, soft-delete-with-undo, eager-load helpers, cursor-based pagination). **NOT in scope:** the package-builder's `HasMigrations`/`HasFactoriesAndSeeders`/`ProcessMigrations` — those stay in `package-tools` because they are coupled to the builder API. `database-tools` requires only `illuminate/database` + `illuminate/support`. `package-tools` does not require `database-tools`. An optional integration trait in `package-tools` (`InteractsWithDatabaseTools`) lets package authors opt in.
**Consequences:** Two genuinely small packages instead of one bloated one. `database-tools` is usable by any Laravel app, not only by package authors.

---

## 4. Cross-cutting strategies

### 4.1 Tooling layout (one shape, copied per package)

```
<package-repo>/
├── composer.json
├── README.md, CHANGELOG.md, LICENSE.md, SECURITY.md, CONTRIBUTING.md, CODE_OF_CONDUCT.md
├── src/                         # PHP source (PSR-4)
├── tests/                       # Pest 3 + Testbench 10
├── docs/
│   ├── README.md
│   ├── adr/NNNN-*.md            # Michael Nygard ADRs
│   ├── examples/                # Runnable examples; CI exercises them
│   └── api/                     # Auto-generated phpDocumentor output (gitignored)
├── stubs/                       # Only for package-scaffolder
├── scripts/
│   └── init.sh                  # The single allowed shell script (~40 lines, see §4.2)
├── .github/workflows/           # tests, security, static-analysis, release — call reusable workflows
├── .pre-commit-config.yaml
├── phpstan.neon, .php-cs-fixer.dist.php, rector.php, pint.json, .editorconfig
├── phpunit.xml, pest.php
├── .env.example                 # Laravel-syntax; LARANAIL_* prefixed keys
└── .gitignore
```

**Tooling is pure PHP/Composer.** The contributor-facing surface is composer script aliases (§4.4 below); `scripts/init.sh` is a thin bootstrap that runs once on a fresh checkout.

### 4.2 `scripts/init.sh` — minimal bootstrap

A small bash script (~40 lines) per repo. Idempotent, exits non-zero on any check failure. Behavior:

1. Verify `php` ≥ 8.3 and `composer` are on PATH.
2. Run `composer install` (or `composer install --no-dev` when `INIT_PROD=1`).
3. Discover host Laravel `.env` (per §4.3). If found: log path + key count + writability. If missing: log INFO with `cp .env.example .env` hint. **Never auto-create.**
4. Run `composer audit` and surface any advisory hits.
5. Run `vendor/bin/pint --test` and `vendor/bin/phpstan analyse --no-progress` as a smoke check (non-fatal warnings only).
6. Print a summary block: PHP version, Composer status, available `composer` aliases, doc links.

A `--check-only` flag skips composer install and step 5; used by CI smoke jobs.

The script is the **only** `.sh` file in the repository (per ADR-007). Verification gate in CI: `find . -type f -name "*.sh" -not -path './vendor/*'` returns exactly one path.

### 4.3 Runtime `EnvFileService` (per package-tools)

Per ADR-006. New PHP runtime service in `package-tools` (`Simtabi\Laranail\PackageTools\Services\Environment\`):

- `EnvFileService` (final class) — `exists()`, `isReadable()`, `isWritable()`, `read()`, `all()`, `appendIfMissing()`, `appendBlock()`, `backup()`, `forceSet(acknowledgeDestructive: true)`.
- `EnvFileParser` — Laravel-compatible (`KEY=VALUE`, quoted values, `#` comments, `${VAR}` interpolation), preserves comments and ordering for round-trip fidelity.
- `Events\EnvFileMutated` — fired on any write.
- `Exceptions\{EnvFileNotFound, EnvFileNotReadable, EnvFileNotWritable}` — actionable messages with absolute paths.
- Atomic writes via `rename()`; transparent `.bak.<timestamp>` next to the file.
- Discovery: `Application::environmentFilePath()` first; walk-up fallback for non-Laravel CLI contexts.

`scripts/init.sh` shells out to a small PHP script that uses `EnvFileService` for the discovery + validation step, so the same code path serves dev and runtime.

### 4.4 Composer script aliases (the contributor surface)

Every package's `composer.json` exposes the same alias set:

```jsonc
"scripts": {
    "setup":          "bash scripts/init.sh",
    "test":           "vendor/bin/pest --colors=always",
    "test-coverage":  "@test --coverage",
    "test-dirty":     "@test --dirty --compact",
    "lint":           ["@pint", "@phpstan", "@rector"],
    "pint":           "vendor/bin/pint --test",
    "pint-fix":       "vendor/bin/pint",
    "phpstan":        "vendor/bin/phpstan analyse --no-progress",
    "rector":         "vendor/bin/rector process --dry-run",
    "rector-fix":     "vendor/bin/rector process",
    "audit":          ["@composer-audit", "@osv-scan"],
    "composer-audit": "@composer audit",
    "osv-scan":       "osv-scanner --lockfile=composer.lock"
}
```

This is the entire developer interface. PHP-only contributors run `composer setup`, `composer test`, `composer lint`, `composer audit` and never see anything else. CI calls the same aliases.

### 4.5 CI/CD per package

Standard matrix:

```yaml
strategy:
  fail-fast: false
  matrix:
    php: ['8.3', '8.4']
    laravel: ['13.*']
    stability: [prefer-lowest, prefer-stable]
    os: [ubuntu-latest]
```

Per-package `.github/workflows/*.yml` files are thin — they call reusable workflows in `laranail/.github/.github/workflows/`. Steps: `shivammathur/setup-php@v2` → `composer update --${{ matrix.stability }}` → `composer lint` → `composer test` → upload coverage to Codecov from one matrix leg.

Release pipeline: tag push → `marcocesarato/php-conventional-changelog` writes `CHANGELOG.md` from Conventional Commits → `cyclonedx-php-composer` emits SBOM → GitHub Release uploaded.

Dependabot: `composer`, `github-actions` weekly; group minor/patch. (No `pip` ecosystem — no Python.)

Security: `roave/security-advisories: dev-master` as dev dep; OSV scan against `composer.lock` on push.

### 4.6 Documentation

**Two public surfaces:**

1. **`opensource.simtabi.com/`** — primary Laranail-branded documentation site. VitePress, hosted via Vercel or GitHub Pages with a custom domain. Aggregates all five packages: getting-started, per-package guides, API reference, ADR index, examples.
2. **`opensource.simtabi.com/laranail/`** — Simtabi's organization-wide opensource portal where Laranail is one of several project families. Pattern:
   - `opensource.simtabi.com/laranail/documentation/` — same content as `opensource.simtabi.com/` (canonical mirror or reverse proxy).
   - `opensource.simtabi.com/laranail/<package-name>/` — per-package landing page with installation, quickstart, links to the deep docs section.

**Implementation options (pick one in §10 Decision E2):**

- **(a) Single VitePress site, two URL surfaces.** One VitePress build deployed to both domains via DNS/proxy. Simpler ownership.
- **(b) Two static sites.** `opensource.simtabi.com/` is the deep VitePress site; `opensource.simtabi.com/laranail/<package>/` is per-package landing pages assembled from each repo's `README.md`. The portal site is generated by a small build script that pulls READMEs from each repo at release time.

Recommended **(a)** for v1: simpler, single source of truth. Migrate to (b) only if the org-portal needs custom navigation that VitePress can't model cleanly.

**Per-package surface (always):**
- `README.md` self-contained for Packagist/GitHub (gets indexed; first impression).
- `docs/` directory in each repo for the deep guide content; sourced by the central VitePress site.
- `docs/adr/NNNN-*.md` per package — Michael Nygard ADR template.
- `docs/examples/` runnable examples; CI exercises them.

**API docs**: `phpDocumentor` auto-generated on tag, published to `opensource.simtabi.com/api/<package>/` (and mirrored under `opensource.simtabi.com/laranail/api/<package>/` if option (b) is chosen).

### 4.7 Conventions

- **Conventional Commits** for all commits. Drives CHANGELOG generation.
- **All package-owned env keys prefixed `LARANAIL_*`** (collision avoidance with framework + vendor keys).
- **`#[\Override]` attribute** on every overriding method.
- **Aggregator traits ≤ 10 leaf traits** (ADR-004).
- **Codemods always `--dry-run` by default**, `--apply` to write, backups to `.artifacts/codemod-backups/<timestamp>/`.
- **Plans go to `.plans/`**; ADRs to `docs/adr/`; never both.
- **Bash forbidden except `scripts/init.sh`** (ADR-007). CI gate enforces.

---

## 5. Per-package design

### 5.1 `laranail/package-tools`

**Public API surface (Spatie parity + extensions):**

```php
$package
    ->name('foo')
    ->hasConfigFile()                   // Spatie parity
    ->hasViews()                        //
    ->hasViewComponents('foo', Card::class)
    ->hasInertiaComponents()
    ->hasViewComposer('layout', AppComposer::class)
    ->sharesDataWithAllViews('foo', 'bar')
    ->hasTranslations()
    ->hasAssets()
    ->hasRoute('web')
    ->hasMigration('create_foos_table')
    ->runsMigrations()
    ->discoversMigrations()
    ->hasCommand(FooCommand::class)
    ->hasInstallCommand(fn ($command) => $command->publishConfigFile()->askToRunMigrations())
    ->publishesServiceProvider(FooServiceProvider::class)

    // Laranail extensions (NEW):
    ->discoversWithAttributes()         // ADR-009: scans src/ for #[AsArtisanCommand], #[AsRoute], #[AsFacade], #[AsViewComposer]
    ->hasFacadeFromContract(FooContract::class)  // Auto-generates and registers facade
    ->hasDoctorCheck(FooHealthCheck::class)      // Wires into `package:doctor`
    ->emitsTelemetry(opt_in: true)               // Anonymous install ping (default off)
    ->discoversIdeHelpers()                      // Auto-runs ide-helper:* on install
;
```

**Trait organization (ADR-004):** all 51 existing traits, regrouped:

```
Concerns/
├── Configures/         # 8 leaf traits — config files, namespaces, runtime merging
│   ├── ConfiguresConfig.php           # aggregator, used by Package
│   └── leaf/{HasConfigs, HasConfigNamespace, HasAdvancedConfig, HasNestedConfigFiles, HasGlobalConfigMerging, HasConfigManipulation, HasNestedLevels, HasCachedNamespaces}.php
├── Routes/             # 2 leaf traits
│   ├── ConfiguresRoutes.php
│   └── leaf/{HasRoutes, HasAdvancedPaths}.php
├── Views/              # 7 leaf traits — views, composers, shared data, components
│   ├── ConfiguresViews.php
│   └── leaf/{HasViews, HasViewSharedData, HasViewComposers, HasEnhancedViewComposers, HasViewComposerRegistry, HasViewComponentLoader, HasBladeComponents}.php
├── Components/         # 5 leaf traits — Blade, Anonymous, Livewire, Vue, Inertia
│   ├── ConfiguresComponents.php
│   └── leaf/{HasBladeDirectives, HasEnhancedAnonymousComponents, HasLivewireComponents, HasVueComponents, HasInertia, HasComponentNamespaces, HasAdditionalNamespaceFormats, HasSafeComponentRegistration}.php
├── Assets/             # 6 leaf traits
│   ├── ConfiguresAssets.php
│   └── leaf/{HasAssets, HasAssetGroups, HasAssetCleanup, HasAssetPublisher, HasModuleAssets, HasVueAssets, HasBatchResourceLoading}.php
├── Database/           # 3 leaf traits — package-builder DB integration
│   ├── ConfiguresDatabase.php
│   └── leaf/{HasMigrations, HasFactoriesAndSeeders, HasComposerOperations}.php
├── Commands/           # 3 leaf traits
│   ├── ConfiguresCommands.php
│   └── leaf/{HasCommands, HasInstallCommand, HasConsoleWrapper}.php
├── Translations/       # 1 leaf trait
│   └── ConfiguresTranslations.php (uses HasTranslations)
├── Helpers/            # 1 leaf trait
│   └── ConfiguresHelpers.php (uses HasHelpers)
├── ServiceProviders/   # 1 leaf trait
│   └── ConfiguresServiceProviders.php (uses HasServiceProviders)
├── Middleware/         # 2 leaf traits
│   ├── ConfiguresMiddleware.php
│   └── leaf/{HasMiddlewareManagement, HasEnhancedMiddleware}.php
├── Events/             # 1 leaf trait
│   └── ConfiguresEvents.php (uses HasEventSystem)
├── Validation/         # 1 leaf trait
│   └── ConfiguresValidation.php (uses HasEnhancedValidation)
├── Security/           # 2 leaf traits
│   ├── ConfiguresSecurity.php
│   └── leaf/{HasSecurityChecking, HasGitOperations}.php
├── Lifecycle/          # 1 leaf trait
│   └── ConfiguresLifecycle.php (uses HasLifecycleHooks)
├── Progress/           # 2 leaf traits
│   ├── ConfiguresProgress.php
│   └── leaf/{HasProgressIndicators, HasTestPublishing}.php
└── Attributes/         # NEW (ADR-009)
    ├── DiscoversWithAttributes.php
    └── attributes/{AsArtisanCommand, AsRoute, AsFacade, AsViewComposer}.php (PHP attributes)
```

**Public-facing classes:**

- `Package` — fluent builder; `use`s ~17 aggregator traits (one per `Concerns/<Domain>/` directory).
- `PackageServiceProvider` — abstract; defines `configurePackage(Package $package): void` for consumers. `use`s the `PackageServiceProvider/Process*` aggregators.
- `Commands\InstallCommand` — extensible by `hasInstallCommand`.
- `Services\Environment\EnvFileService` — per ADR-006.
- `Services\Doctor\DoctorService` — runs `package:doctor` checks.
- `Testing\IsolatedTestCase` — opinionated Testbench wrapper.
- `Attributes\{AsArtisanCommand, AsRoute, AsFacade, AsViewComposer}` — PHP 8.3+ attributes for ADR-009.

### 5.2 `laranail/package-scaffolder`

Generator. Source = current `src/Scaffolder/` + `stubs/`.

**Public commands:**

- `make:package` (was `GeneratePackageCommand`) — interactive scaffolder.
- `package:install`, `package:uninstall`, `package:enable`, `package:disable`, `package:remove`, `package:list`, `package:status`, `package:validate`, `package:bug-hunt`, `package:security-check`, `package:git`, `package:publish-tests`.
- `package:doctor` — health check (delegates to `package-tools`'s `DoctorService`).

Stubs in `stubs/`:

- `composer.stub`, `package.stub`, `LICENSE.md.stub`, `README.md.stub`, `CHANGELOG.md.stub`, `SECURITY.md.stub`, `CONTRIBUTING.md.stub` — repo meta.
- `phpunit.xml.stub`, `phpunit.stub`, `editorconfig.stub`, `.gitignore.stub` — tooling.
- `src/{Providers, Models, Facades, Commands, Http/Controllers}/*.stub` — code skeleton.
- `database/{migrations, seeders, factories}/*.stub` — DB scaffolding (templates, not the runtime DB integration).
- `tests/{Pest, TestCase}.stub`.
- `resources/{lang, views, assets}/**` and `routes/{web, api, demo-routes}.stub`.
- `.github/workflows/tests.yml.stub`, `vite.config.stub`, `tailwind.config.stub`, `postcss.config.stub`.

Generated packages should:

- Default to PHP 8.3 / Laravel 13.
- Extend `Simtabi\Laranail\PackageTools\PackageServiceProvider`.
- Include the standard CI matrix.
- Include the standard composer script aliases (`setup`/`test`/`lint`/`audit`).

### 5.3 `laranail/database-tools`

Independent. Scope per ADR-010.

**Initial feature set:**

- Model traits: `HasUuid`, `HasNanoid`, `HasUlid`, `HasSoftDeletesWithUndo`, `HasAuditLog`, `HasEloquentObserver`, `HasJsonColumnAccessors`.
- Schema macros: `Blueprint::auditColumns()`, `Blueprint::softDeletesWithUndo()`, `Blueprint::polymorphicRelation()`.
- Observer base: `Observers\AuditObserver` + opt-in via `#[Observed(AuditObserver::class)]` attribute.
- Eager-load helpers: `loadIfMissing()`, `loadAggregateIfMissing()`.
- Cursor-pagination DTO: `Pagination\Cursor`.

**Tests:** Testbench 10 + in-memory SQLite. Optional MySQL/Postgres matrix in CI.

**Out of scope:** anything tied to the package builder; queue jobs; eloquent factories beyond what the model traits need.

### 5.4 `laranail/laranail` (existing)

Untouched by this plan **except**:

- Update Laravel constraint from `^10.0 || ^11.0 || ^12.0` to `^13.0` (Decision G).
- Update PHP constraint to `^8.3 || ^8.4` (already there, ✓).
- Add `laranail/database-tools` and `laranail/package-tools` as `suggest` entries.
- Adopt the same CI/CD pipeline shape as the new packages (Pint, Larastan L8, Rector, Pest 3).

---

## 6. Issues found across the suite

Carried forward from v5 (still valid for `packager`); extended with cross-suite issues.

### 6.1 Critical (block correctness, in the current `packager` repo)

| # | Issue | Files | Impact |
|---|---|---|---|
| C1 | **PSR-4 namespace rot** — 47 references to `Simtabi\Laranail\Packager\Services\…` (no such namespace; should be `…\Package\Services\…` or `…\Scaffolder\Services\…`). | sweep across `src/Package/Services/*` + `src/Scaffolder/Services/*` | Autoloader fails. Single biggest defect. |
| C2 | **Duplicate provider**: `GeneratorServiceProvider.php` byte-identical to `ScaffolderServiceProvider.php` (MD5 `70e75b907c5f…`). | `src/Scaffolder/Providers/{Generator,Scaffolder}ServiceProvider.php` | Latent class-redeclaration risk. |
| C3 | **No-op codemod**: `update_setBasePath.php` does `str_replace('setPathFromBase(', 'setPathFromBase(', …)`. | `update_setBasePath.php` | Misleading dead file. |
| C4 | **Broken root TestCase**: references `…\Providers\PackagerServiceProvider`, `…\Generator\Providers\GeneratorServiceProvider`, `config/packager.php`, `config/generator.php` — all nonexistent. | `tests/TestCase.php` | Dead file with 100% broken refs. |
| C5 | **`phpunit.xml` misconfigured**: `bootstrap="tests/bootstrap.php"` (missing); testsuites point at `tests/Unit`/`Integration`/`Feature` (missing); excludes `src/Examples` (missing). | `phpunit.xml` | `vendor/bin/phpunit` finds zero tests. |
| C6 | **`composer test` references missing file**: `auto_prepend_file=tests/Prepend.php`. | `composer.json` line 62 | Silent failure. |

### 6.2 Major (architectural debt)

| # | Issue | Detail |
|---|---|---|
| M1 | Duplicated service trees `Package/Services/{Development,BugHunter}/*` ↔ `Scaffolder/Services/{Development,BugHunter}/*`. | Resolved by ADR-010 — Development/BugHunter analyzers move to `package-scaffolder` (they're scaffolder-time tools), not `package-tools`. |
| M2 | 26 of 51 traits in `Concerns/Package/` are unused. | Resolved by ADR-004 — all 51 wired through aggregators. None deleted. |
| M3 | `tests/Scaffolder/{Unit,Integration}/` empty despite `composer.json` declaring them. | Phase 9 populates. |
| M4 | No single setup script. | ADR-007 + Phase 9/10 — `scripts/init.sh` per package + composer aliases. |
| M5 | Codemod sprawl across root + `/scripts/` + `PROJECT_PLANS/`. | Phase 11 — Python tooling consolidates. |
| M6 | Procedural Python scripts with hardcoded paths in `packager/scripts/`. | Phase 9 — all deleted (per ADR-007 in v8); functionality replaced by composer aliases + native Artisan commands. |
| M7 | Three empty root meta files. | Phase 14 — populate as redirects to `docs/`. |
| M8 | `phpunit.xml` doesn't run any of the actual test paths (carryover of C5). | Phase 8 fix. |
| M9 | No attribute-driven discovery despite Laravel 13 supporting it. | ADR-009 — first-party attributes added in `package-tools`. |
| M10 | No `package:doctor` / SBOM / OSV scan / IDE-helper auto-gen. | ADR-009 + §J research recommendations. |

### 6.3 Suite-level issues (new, found during v6 design)

| # | Issue | Detail |
|---|---|---|
| S1 | Polyrepo coordination cost — cross-cutting refactors require multi-PR. | ADR-002 (v7) — accepted as the trade-off. Reusable workflows in `laranail/.github` repo keep CI shape consistent across packages. Cross-cutting changes tracked by single GitHub issue cross-linked from per-repo PRs. |
| S2 | No CHANGELOG automation; manual updates drift. | Phase 15 — `marcocesarato/php-conventional-changelog` per package. |
| S3 | No SBOM emission for security/supply-chain. | Phase 15 — `cyclonedx/cyclonedx-php-composer` on tag. |
| S4 | No OSV/security advisory scan in CI. | Phase 15 — OSV scanner step. |
| S5 | No Dependabot, no automated dep updates. | Phase 15 — Dependabot config per repo. |
| S6 | No ADR record; architecture decisions live in this plan only. | Phase 16 — bootstrap `docs/adr/` per package. |
| S7 | No published docs site. | Phase 17 — VitePress at `opensource.simtabi.com/`. |
| S8 | No package isolation testing harness. | ADR-009 — `Testing\IsolatedTestCase` in `package-tools`. |

### 6.4 Minor (hygiene)

| # | Issue |
|---|---|
| m1 | `.DS_Store` files at repo root, `src/`, `vendor/`. |
| m2 | Two `.gitignore` stubs (`stubs/gitignore.stub` vs `stubs/.gitignore.stub`); pick `.gitignore.stub`. |
| m3 | `.plans/reference/` empty. Reserved for ADRs / cross-cutting refs. |
| m4 | `bug-report.json` (26 KB) at repo root — one-time output; archive. |
| m5 | `PROJECT_PLANS/` 57 files + 39 stale `.backups/`. To be reviewed and merged into this master plan, then deleted. |
| m6 | Mixed `static`/`$this` fluent return types. Resolved by ADR-005. |
| m7 | `composer.json` lacks `setup`/`lint`/`audit` aliases. Phase 10 fix. |
| m8 | Stub placeholder format verified `{{vendor}}` (no spaces) — no action needed beyond consistency. |
| m9 | `<editor>.local.json` not in `.gitignore`. Phase 0 fix. |

---

## 7. Phase plan

Re-organized from the v5 single-repo cleanup into a suite-wide plan. Each phase is independently mergeable.

### Phase 0 — Safety net + polyrepo bootstrap (≈2 h)

- [ ] `git init` in `packager/` if not initialized; capture baseline `composer dump-autoload`/`pest` output to `.artifacts/baseline-<date>.txt`.
- [ ] Add `.gitignore` entries to `packager/`: `.DS_Store`, `.artifacts/`, `.env`, `bug-report.json`, `<editor>.local.json`, `coverage/`, `.phpunit.cache/`, `.phpstan-cache/`.
- [ ] Create sibling polyrepos as empty git repos with MIT license + minimal `README.md`:
  - `/Users/imanimanyara/Artisan/projects/opensource/laranail/package-tools/`
  - `/Users/imanimanyara/Artisan/projects/opensource/laranail/database-tools/`
- [ ] Create org-level `laranail/.github/` profile repo for **reusable GitHub Actions workflows** (Phase 13 fills it). At minimum a placeholder `README.md` so the org page renders.
- [ ] Create dedicated docs repo `/Users/imanimanyara/Artisan/projects/opensource/laranail/documentation/` (VitePress; Phase 17 fills it).
- [ ] **Do not** move code yet — that begins in Phase 4. Phase 0 only stages the empty repos.

### Phase 1 — Critical bugs in `packager` (≈2.5 h)

Same as v5 Phase 1 — fixes apply before code is split. Detail preserved in Appendix §A.

- [ ] **C1** — sweep namespace rot via a Rector custom rule (`Laranail\Rector\FixBrokenLaranailNamespacesRule`) or a one-off PHP script. Idempotent; dry-run reviewed.
- [ ] **C2** — delete `GeneratorServiceProvider.php`.
- [ ] **C3** — delete `update_setBasePath.php`.
- [ ] **C4** — delete `tests/TestCase.php`.
- [ ] **C5** — rewrite `phpunit.xml` for actual layout.
- [ ] **C6** — drop `auto_prepend_file=tests/Prepend.php` directive (default).

### Phase 2 — Deduplicate services in `packager` (≈3 h)

Scaffolder-time analyzers (`Development/`, `BugHunter/`) consolidate **into Scaffolder** (revised vs v5 — these are scaffolder-time concerns). Update consumers (commands) before deleting Package-side duplicates.

### Phase 3 — Wire all 51 traits via aggregators (≈6 h, was Phase 3 in v5)

Implements ADR-004. Steps:

- [ ] Create `Concerns/<Domain>/<Configures*>.php` aggregator traits (15 aggregators).
- [ ] Move existing 51 leaf traits into `Concerns/<Domain>/leaf/`.
- [ ] Run `grep` for method-name conflicts across all leaves; resolve via prefix-renaming or `insteadof` on aggregator level (not on `Package`).
- [ ] Update `Package` and `PackageServiceProvider` to `use` only aggregators.
- [ ] Run tests; fix any leaks. Add `tests/Package/Unit/Concerns/<Domain>/` coverage for at least each aggregator's smoke test.

### Phase 4 — Move runtime to `laranail/package-tools` (≈8 h)

Big-bang extraction. Target repo: `/Users/imanimanyara/Artisan/projects/opensource/laranail/package-tools/` (created empty in Phase 0).

- [ ] `composer.json` for `laranail/package-tools` — PHP `^8.3 || ^8.4`, `illuminate/contracts: ^13.0`, `pest: ^3.0`, `testbench: ^10.0`, `larastan: ^3.0`.
- [ ] Move `packager/src/Package/` → `package-tools/src/`. Namespace transform: `Simtabi\Laranail\Packager\Package\…` → `Simtabi\Laranail\PackageTools\…`. Driven by a Rector rule against both source dirs in one pass; dry-run reviewed.
- [ ] Move `packager/tests/Package/` → `package-tools/tests/`. Same namespace transform applied to test code.
- [ ] Move related docs from `packager/docs/` (architecture, services, configuration sections that pertain to runtime) → `package-tools/docs/`.
- [ ] Add `.github/workflows/` (calling reusable workflows from `laranail/.github`), `phpunit.xml`, `pest.php`, `phpstan.neon`, `.php-cs-fixer.dist.php`, `pint.json`, `rector.php`, `.editorconfig`, `.pre-commit-config.yaml`, `scripts/init.sh`.
- [ ] Run tests in `package-tools/` standalone — must be green before Phase 5.
- [ ] First commit: `feat: extract package-tools from laranail/packager`.

### Phase 5 — Add new functionality to `package-tools` — tiered v1.0 / v1.1 / v1.2 (≈10 h total, ~5 h in v1.0)

Implements ADR-009 + §J research recommendations. **All native Laravel Artisan commands** (per ADR-007 — no Python). Per Decision G (Strategy 1), shipped in three tiered releases:

#### 5A — Ship in v1.0 (≈5 h)

- [ ] **Attributes**: `#[AsArtisanCommand]`, `#[AsRoute]`, `#[AsFacade]`, `#[AsViewComposer]` PHP 8.3 attributes.
- [ ] **`Package::discoversWithAttributes()`** — uses `spatie/laravel-auto-discoverer` to scan `src/`.
- [ ] **`Services\Doctor\DoctorService`** + `Commands\PackageDoctorCommand` (`php artisan package:doctor`) — wires `Package::hasDoctorCheck()`.
- [ ] **`Testing\IsolatedTestCase`** — opinionated Testbench wrapper with in-memory SQLite, snapshot helpers.
- [ ] **`Services\Environment\EnvFileService`** (per ADR-006) — append-only writer.

These four are the v1.0 differentiators: attribute discovery vs Spatie's path-based fluent API; `package:doctor` as the operator-grade marketing tagline; `IsolatedTestCase` for consumer DX; `EnvFileService` already required by `InstallCommand`.

#### 5B — Ship in v1.1 (≈3 h, post-v1.0)

- [ ] **`Services\Sbom\SbomService`** wrapping `cyclonedx/cyclonedx-php-composer` + `Commands\PackageSbomCommand` (`php artisan package:sbom`).
- [ ] **`Services\Audit\OsvAuditService`** + `Commands\PackageAuditCommand` (`php artisan package:audit`) — checks `composer.lock` against OSV.dev.
- [ ] **`Services\InstallWizard\InstallWizardService`** + extension to `InstallCommand` for interactive prompts.

Security/install trio. Defer until v1.0 has real-world feedback.

#### 5C — Ship in v1.2 (≈2 h, post-v1.1)

- [ ] **`Services\IdeHelper\IdeHelperService`** + `Commands\PackageIdeHelperCommand` (`php artisan package:ide-helper`) — emits per-package facade/config/blade-component stubs.
- [ ] **`Services\Facade\FacadeAutoGenerator`** + `Package::hasFacadeFromContract(Contract::class)`.

Developer-experience trio. Lowest priority; nice but consumers can hand-write facades while waiting.

#### Deferred to v2.0+ (or skip entirely)

- ~~`Services\Telemetry\TelemetryService`~~ — **dropped from v1.x scope** per Decision F. Packagist already publishes install counts publicly; phone-home telemetry adds maintenance + privacy surface for marginal data. Re-evaluate at v2.0 only if a concrete need surfaces.

### Phase 6 — Carve `laranail/database-tools` (≈6 h)

Implements ADR-010. Target repo: `/Users/imanimanyara/Artisan/projects/opensource/laranail/database-tools/` (created empty in Phase 0).

- [ ] `composer.json` — PHP `^8.3`, `illuminate/database: ^13.0`, `illuminate/support: ^13.0`, `pest`, `testbench`. **No `package-tools` dep.**
- [ ] Initial features per §5.4: `HasUuid`, `HasNanoid`, `HasUlid`, `HasSoftDeletesWithUndo`, `HasAuditLog`, `HasJsonColumnAccessors` model traits; `Blueprint::auditColumns()`, `softDeletesWithUndo()` schema macros; `AuditObserver` + `#[Observed]` attribute; `Pagination\Cursor` DTO; eager-load helpers.
- [ ] Tests: in-memory SQLite + optional MySQL/Postgres CI matrix.
- [ ] Optional integration trait `InteractsWithDatabaseTools` exposed in `package-tools`.

### Phase 7 — Rename `packager` → `laranail/package-scaffolder` (≈4 h)

- [ ] Rename directory: `packager/` → `package-scaffolder/` (sibling-level rename, since both live under `/laranail/`). Preserve git history with `git mv` semantics or a fresh `git clone` + `git remote set-url` to a new GitHub repo. The original GitHub repo can be archived with a redirect README.
- [ ] `composer.json` rename: `laranail/packager` → `laranail/package-scaffolder`. Add `require: laranail/package-tools: ^1.0`.
- [ ] Delete `src/Package/` (already moved in Phase 4).
- [ ] Namespace transform: `Simtabi\Laranail\Packager\Scaffolder\…` → `Simtabi\Laranail\PackageScaffolder\…`.
- [ ] Update stubs: generated packages should `extend Simtabi\Laranail\PackageTools\PackageServiceProvider` and `require: laranail/package-tools`.
- [ ] Default new packages to PHP 8.3 / Laravel 13.
- [ ] Tests stay; populate `tests/Scaffolder/` per v5 Phase 4.

### Phase 8 — Test parity + phpunit.xml fix (≈2.5 h)

Per v5 Phase 4. Add to `tests/Scaffolder/`:

- [ ] `Pest.php`, `TestCase.php`, `bootstrap.php`.
- [ ] `Unit/PlaceholderResolverTest.php`, `Integration/GeneratePackageCommandTest.php`.
- [ ] Update `phpunit.xml` Scaffolder testsuite block.

### Phase 9 — Delete legacy `/scripts/` and bootstrap `init.sh` (≈2 h)

Replaces v7's Phase 9–10 (Python package).

- [ ] Delete every script under `packager/scripts/` and the root-level codemods (`fix_*.php`, `create_*_stubs.sh`, `update_setBasePath.php`, etc.) — all completed one-shots or wrappers superseded by composer aliases. Record each in `docs/codemod-archive.md` (one paragraph per retired script).
- [ ] Write the canonical `scripts/init.sh` per §4.2 in each repo (~40 lines bash).
- [ ] Confirm the bash-elimination CI gate passes in each repo (single `.sh` file).

### Phase 10 — Composer script aliases (≈1 h)

- [ ] Add the standard alias block (per §4.4) to every package's `composer.json`:
      `setup`, `test`, `test-coverage`, `test-dirty`, `lint`, `pint`, `pint-fix`, `phpstan`, `rector`, `rector-fix`, `audit`, `composer-audit`, `osv-scan`.
- [ ] Update each repo's `README.md` quickstart to point at `composer setup` / `composer test` / `composer lint` / `composer audit`.
- [ ] Update `docs/CONTRIBUTING.md` to drop any Python references.

### Phase 11 — Move related docs (≈3 h)

- [ ] `packager/docs/ARCHITECTURE.md`, `SERVICES.md`, `CONFIGURATION.md` (runtime sections) → `package-tools/docs/`.
- [ ] `packager/docs/TESTING.md`, `CONTRIBUTING.md`, `SECURITY.md` (scaffolder-relevant sections) → `package-scaffolder/docs/`.
- [ ] Per-package `docs/` directory holds the canonical guides. Each package's `docs/` is the source of truth; cross-cutting conceptual docs (suite overview, architecture diagram) live in a dedicated docs repo (see Phase 17).

### Phase 12 — Legacy plan review + merge (≈4 h)

Review `PROJECT_PLANS/` (57 files), distill any not-yet-captured decisions or context into this master plan or into per-package `docs/adr/`, then archive the directory and `.backups/` to `.artifacts/legacy-plans-<date>/` and delete from active path.

### Phase 13 — Per-package CI/CD (≈5 h)

Per §4.3. Reusable workflows live in `laranail/.github/.github/workflows/`; each package's `.github/workflows/*.yml` calls them.

- [ ] **In `laranail/.github` org repo**: define reusable workflows `tests.yml`, `security.yml`, `static-analysis.yml`, `release.yml`. Each declares its inputs (PHP versions, Laravel versions, package path).
- [ ] **In each package repo**: thin `.github/workflows/*.yml` files calling the reusable workflows via `uses: laranail/.github/.github/workflows/<workflow>.yml@main`.
- [ ] Dependabot config per repo (`composer`, `github-actions` only — no `pip`, no Python).
- [ ] OSV scan step in `security.yml`.
- [ ] SBOM emission on tag in `release.yml`.

### Phase 14 — Documentation completeness (≈3 h)

- [ ] Empty root meta files become thin redirects to `docs/`.
- [ ] Each package gets `README.md` (self-contained for Packagist), `CHANGELOG.md`, `CONTRIBUTING.md`, `SECURITY.md`, `LICENSE.md`.
- [ ] First batch of ADRs in **each package's** `docs/adr/` directory, derived from §3 of this plan. Each ADR clarifies its scope (single-package vs suite-wide).
- [ ] Examples directory at **each package's** `docs/examples/` directory; cross-package examples live in the central `laranail/documentation` repo under `docs/examples/`.

### Phase 15 — Release tooling (≈3 h)

- [ ] `marcocesarato/php-conventional-changelog` per package — generates CHANGELOG from Conventional Commits on tag.
- [ ] `cyclonedx/cyclonedx-php-composer` per package — emits CycloneDX SBOM as a release artifact.
- [ ] `roave/security-advisories: dev-master` as dev dep per package — composer install fails if any registered package has an open advisory.
- [ ] OSV scan and SBOM emit run inside the reusable `release.yml` workflow.
- [ ] **No monorepo split tooling** (per ADR-002). Each repo tags and publishes independently. Coordination of cross-package releases is documented in `docs/CONTRIBUTING.md` of each repo.

### Phase 16 — ADR bootstrap (≈2 h)

- [ ] `docs/adr/0001-five-packages.md` through `0010-database-tools-independent.md` (the ten ADRs in §3).
- [ ] ADR template at `docs/adr/template.md`.
- [ ] README in `docs/adr/` explaining the lifecycle.

### Phase 17 — VitePress docs site (≈5 h)

Dedicated docs repo: `/Users/imanimanyara/Artisan/projects/opensource/laranail/documentation/` (new). Not published to Packagist — it's a build artifact.

- [ ] Create `laranail/documentation` polyrepo with VitePress scaffold.
- [ ] `.vitepress/config.ts` with sidebar grouping per package: Getting Started → Package Tools → Package Scaffolder → Database Tools → Laranail Toolbox → ADR Index → Examples.
- [ ] **Source ingestion**: a small Node script (`scripts/sync-docs.mjs`) clones each package repo at the latest tag into `docs/<package>/` and pulls the `docs/` directory. Runs on push to `main` and on cron.
- [ ] Per-package landing page in `docs/<package>/index.md`.
- [ ] ADR index auto-generated by walking each cloned repo's `docs/adr/*.md`.
- [ ] Examples linked.
- [ ] **Deploy**:
  - Primary: `opensource.simtabi.com/` via Vercel or GitHub Pages with custom domain.
  - Mirror/portal: `opensource.simtabi.com/laranail/` — single VitePress build, two domain aliases on the same Vercel deploy (per §10 Decision E2 recommendation).
- [ ] DNS: ensure `opensource.simtabi.com` and `opensource.simtabi.com` CNAMEs are configured.

### Phase 18 — Final verification (≈2 h)

- [ ] All four packages green on CI.
- [ ] `composer audit` clean per package.
- [ ] Bash-elimination CI gate verified (one `scripts/init.sh` per repo, no other `.sh` files).
- [ ] Each `package-tools` API method covered by at least one Pest test.
- [ ] `php artisan package:doctor` runs clean against a freshly scaffolded package.
- [ ] First tag of each package: `v1.0.0-beta.1`. Each polyrepo tags + publishes independently. Packagist registers them.
- [ ] CHANGELOG and SBOM artifacts attached to GitHub releases.

**Total estimate to v1.0: ~65 h** (v8's ~70 h minus ~5 h from Phase 5 tiering — only Tier A ships in v1.0; Tiers B and C ship in v1.1 and v1.2 respectively, ~5 h combined post-v1.0). Splittable across many PRs at every phase boundary.

---

## 8. Cleanup catalog (suite-wide)

Carryover from v5 §7.5 (`packager` repo specifics) plus suite-level additions.

### A. Files to DELETE

All v5 §7.5(A) entries (no-op codemod, broken root TestCase, duplicate provider, duplicate service trees, every one-shot codemod, `.DS_Store`, `gitignore.stub` duplicate). **No traits deleted** (ADR-004 wires all 51 via aggregators). **`PROJECT_PLANS/.backups/` (39 stale dups) deleted outright** (Phase 12).

### B. Files to MOVE / ARCHIVE

- `bug-report.json` → `.artifacts/audit/bug-report-<date>.json`.
- `PROJECT_PLANS/` → `.artifacts/legacy-plans-<date>/` after Phase 12 review.
- `packager/src/Package/` → `package-tools/src/` (sibling polyrepo) (Phase 4).
- `packager/tests/Package/` → `package-tools/tests/`.
- `packager/docs/{ARCHITECTURE,SERVICES,CONFIGURATION}.md` → `package-tools/docs/`.

### C. Files to MERGE / CONSOLIDATE

- 51 traits → 15 aggregator traits + 51 leaf traits, structurally reorganized (Phase 3).
- All scripts under `packager/scripts/` and root-level codemods → **deleted** (Phase 9). Replaced by composer aliases (Phase 10) and a small `scripts/init.sh` per repo. Each retired script gets a one-paragraph entry in `docs/codemod-archive.md` so the institutional knowledge isn't lost.
- Three empty root meta files → thin redirects to `docs/`.

### D. Files to KEEP AS-IS (with possibly an edit)

- `composer.json` per package — substantially rewritten in Phase 7/14.
- `phpunit.xml` per package — rewritten in Phase 1 (C5) for `packager`, fresh per package thereafter.
- `config/` files in `packager` — move to `package-tools` and `package-scaffolder` per concern.
- All current docs — re-homed and extended.
- `.github/workflows/*` — re-templated per Phase 13.
- Stubs — stay in `package-scaffolder/stubs/`.

### E. Files to CREATE (suite-wide)

- Org repo `laranail/.github`: profile README + `.github/workflows/{tests,security,static-analysis,release}.yml` reusable workflows callable from each package.
- Docs repo `laranail/documentation`: VitePress source + `scripts/sync-docs.mjs` puller; deploys to `opensource.simtabi.com/` and `opensource.simtabi.com/laranail/`.
- Each PHP package: standard Laravel-package skeleton (composer.json, phpunit.xml, pest.php, phpstan.neon, .php-cs-fixer.dist.php, pint.json, rector.php, .editorconfig, .pre-commit-config.yaml, .github/workflows/, scripts/init.sh, .env.example, README/CHANGELOG/LICENSE/SECURITY/CONTRIBUTING.md, docs/codemod-archive.md).
- ADRs at each package's `docs/adr/NNNN-*.md` (Phase 16). Suite-wide ADRs (e.g., the §3 ones spanning all packages) duplicated to each repo with a "Status: accepted; canonical version in `laranail/package-tools`" cross-link.
- New `package-tools` services (Phase 5): Doctor, Sbom, OsvAudit, IdeHelper, FacadeAutoGenerator, InstallWizard, IsolatedTestCase, Telemetry, EnvFileService.
- New `database-tools` features (Phase 6): all of §5.4.

---

## 9. Risks & mitigations

| Risk | Mitigation |
|---|---|
| Big-bang split (Phase 4) breaks tests for a long stretch. | Phase 1 + 2 land first to ensure baseline green. Phase 4 is on a feature branch; merge only when `package-tools` standalone CI is green. |
| 51 traits via aggregators surfaces method conflicts. | Phase 3 includes a grep-driven conflict audit before wiring. Conflicts resolved by prefix-renaming, not `insteadof` on `Package`. |
| Laravel 13 hasn't shaken out yet (released 2026-03-17, ~7 weeks ago). | Track Laravel issues; pin to `^13.0` minor for now; bump on `13.1`/`13.2` releases. |
| PHP 8.4 features (property hooks, asymmetric vis) break in some hosting environments. | Floor stays at 8.3; 8.4 features used opportunistically with `version_compare()` guards. |
| Polyrepo cross-cutting changes can drift (one repo bumps a dep, others lag). | Reusable workflows in `laranail/.github` enforce shared CI shape. Quarterly drift check via a small `gh`-based bash snippet in `laranail/.github/scripts/suite-audit.sh` — surveys composer.json across repos and flags version skew. |
| 26 newly-wired traits double the public API surface; consumers may stumble on confusing methods. | Phase 5's `package:doctor` validates configuration coherence; PHPDoc on every fluent method; documentation site explains the aggregator structure. |
| Without a Python tooling layer, codemods become harder when needed. | Use Rector — the standard PHP codemod tool — for any future code transformations. Rector rules are testable, idempotent, and ship with their own dry-run/diff workflow. |
| ADR drift: decisions made without recording an ADR. | Pre-commit hook (Phase 13) requires any `composer.json` `require` change to come with an ADR or referenced existing ADR. |
| Existing `laranail/laranail` consumers hit a Laravel 13 bump unexpectedly. | Cut a `v1.x-laravel12` maintenance branch before bumping; document in CHANGELOG. |

---

## 10. Decisions (resolved through user input)

| # | Question | Resolution |
|---|---|---|
| 0 | Split into multiple packages? | **Yes, 5 packages** (per ADR-001). |
| 1 | Trait pruning | **Keep all 51 + actively wire via aggregators** (per ADR-004). |
| 2 | Fluent return convention | **`$this` for chaining, `static` for terminal/factory** (per ADR-005). |
| 3 | `PROJECT_PLANS/` fate | **Review and merge into master plan, then archive to `.artifacts/legacy-plans-<date>/`** (Phase 12). |
| 4 | `git init` for `packager/`? | **Yes** (Phase 0). |
| 5 | Python deps | **Stdlib + `rich`** allowed. |
| 6 | `tests/Prepend.php` (C6) | **Drop the directive** (Phase 1). |
| 7 | Where does runtime Package land? | **New repo `laranail/package-tools` (not under `laranail/laranail`)** — user direction in v6. |
| 8 | Timing vs `laranail/laranail` refactor | **No feature branch needed** — laranail/laranail's refactor is fresh and won't collide. |
| 9 | `laranail/database-tools` separate package? | **Yes** (per ADR-010). Independent. Scope per §5.4. |
| A | Monorepo or polyrepo? | **Polyrepo** (per ADR-002, user direction v7). |
| B | Where does each repo live? | **`/Users/imanimanyara/Artisan/projects/opensource/laranail/<package-name>/`** (per user direction v7). |
| C | `laranail/laranail` Laravel constraint bump (10/11/12 → 13)? | **Bump to ^13.0** (per user direction v9). Cut `1.x-laravel12` LTS branch first as a frozen maintenance line. |
| D | ~~Python distribution~~ | **Obsolete (v8)** — Python package dropped per ADR-007. |
| E1 | Docs site domains? | **`opensource.simtabi.com/`** primary + **`opensource.simtabi.com/laranail/{docs, <package>}`** portal (per user direction v7). |
| E2 | Single VitePress build serving both domains, or two separate sites? | **(a) single build, two URL surfaces** (per user direction v9 — accepted default). Simpler, single source of truth. Migrate to (b) only if `opensource.simtabi.com` later hosts multiple sibling project families needing org-wide nav. |
| F | Telemetry feature in `package-tools`? | **Dropped from v1.x scope** (per user direction v9). Packagist publishes install counts; the maintenance + privacy surface isn't worth marginal data. Re-evaluate at v2.0+ only if a concrete need surfaces. |
| G | Phase 5 scope: ship all features in v1.0 or tier? | **Strategy 1 — tiered release** (per user direction v9 — accepted default). Tier A in v1.0 (attribute discovery, `package:doctor`, `IsolatedTestCase`, `EnvFileService`); Tier B in v1.1 (SBOM, OSV audit, install wizard); Tier C in v1.2 (ide-helper, facade auto-gen). |
| **Resolved v8** | Python tooling? | **Dropped** (per ADR-007 + user choice option (a)). Tooling is pure PHP/Composer. |
| **All decisions resolved** | — | **Plan is execution-ready.** |

---

## 11. Time estimate

- v5 (single-repo cleanup): ~29 h
- v6 (suite-wide redesign, monorepo): ~84 h
- v7 (suite-wide redesign, polyrepo): ~86 h
- v8 (4 packages, no Python): ~70 h
- **v9 (decisions locked, telemetry deferred, Phase 5 tiered): ~65 h to v1.0 + ~5 h follow-up across v1.1/v1.2.**
  - **v1.0 path** (~65 h): Phase 5A (5 h, Tier A only) + Phase 4 (8 h) + Phase 3 (6 h) + Phase 6 (6 h) + Phase 17 (5 h) + Phase 13 (5 h) + Phase 12 (4 h) + Phase 7 (4 h) + Phase 15 (3 h) + Phase 11 (3 h) + Phase 14 (3 h) + Phase 1 (2.5 h) + Phase 2 (3 h) + Phase 8 (2.5 h) + Phase 0 (2 h) + Phase 9 (2 h) + Phase 16 (2 h) + Phase 18 (2 h) + Phase 10 (1 h).
  - **Post-v1.0**: Phase 5B (3 h → v1.1), Phase 5C (2 h → v1.2). Telemetry deferred indefinitely.

PR-sized chunks at every phase boundary.

---

## 12. Bug log (preserved from v1–v5)

Twenty-nine plan-self-review defects across five revisions, P1–P29. Each fixed inline in the plan above. See §13 changelog for the per-version list.

---

## 13. Changelog

- **v1** (2026-05-04) — initial draft, 11 phases, 22 h estimate, TOML config.
- **v2** (2026-05-04) — switched to JSON + `.env`. C1 expanded to 47 references. Added C4. Reordered Phase 2. Added `lib/env.py` and `lib/paths.py`. P1–P12.
- **v3** (2026-05-04) — `.env` realigned to host Laravel reality. Append-only contract. Phase 10b runtime `EnvFileService`. P13–P17.
- **v4** (2026-05-04) — bash-elimination policy + comprehensive cleanup catalog. Plan briefly moved to `plans/`, restored to `.plans/`. P18–P22.
- **v5** (2026-05-04) — added C5 (broken `phpunit.xml`) and C6 (missing `tests/Prepend.php`). Fixed §3.1 ↔ Phase 10 contradiction. Verified trait counts (25 used / 26 unused). P23–P29. Estimate ~29 h.
- **v6** (2026-05-04) — **major direction change**: 5-package suite, not single repo. PHP 8.3+/Laravel 13+. Plan structure rewritten around ADRs (10 architectural decisions), per-package design, monorepo + subtree-split (later flipped in v7), attribute-driven discovery (ADR-009), `database-tools` independence (ADR-010), trait aggregator pattern (ADR-004) wires all 51 traits. New phases 4–19 covering extraction, new functionality, Python package, docs site. Estimate: ~84 h.
- **v7** (2026-05-04) — polyrepo flip + docs domains.
  - **ADR-002 flipped** from monorepo + subtree-split to polyrepo. Each package gets its own git repo at `/Users/imanimanyara/Artisan/projects/opensource/laranail/<package-name>/`. No `symplify/monorepo-builder`. Cross-cutting workflows live in a `laranail/.github` org profile repo and are called as reusable workflows.
  - **Docs domains** finalized: `opensource.simtabi.com/` primary + `opensource.simtabi.com/laranail/{docs, <package>}` portal. New §4.6 lays out two implementation options (single build vs two sites); recommendation: single VitePress build serving both URL surfaces.
  - Phase 0 simplified — no monorepo bootstrap; just `git init` per sibling repo.
  - Phase 13 (CI) restructured around reusable workflows in `laranail/.github`.
  - Phase 16 dropped monorepo-builder; releases are independent per repo.
  - Phase 18 moved to a dedicated `laranail/documentation` repo with a sync script that pulls each package's `docs/` at tag time.
  - §6.3 S1 reframed: polyrepo coordination is the trade-off (not a defect to fix).
  - **v7-specific bug log (P42+):**
    - **P42** v6 said "monorepo + subtree-split" but user direction was polyrepo. Plan would have generated unnecessary tooling and confusion. Fixed: ADR-002 flipped; Phase 0/13/16/18 rewritten.
    - **P43** v6 §4.6 said "VitePress site at `docs.laranail.dev`" — user has different domain (`opensource.simtabi.com/` and `opensource.simtabi.com/laranail/`). Fixed: §4.6 + Phase 18 updated.
    - **P44** v6 had no plan for the `laranail/.github` org-level repo (necessary to keep CI consistent across polyrepos without `symplify/monorepo-builder`). Fixed: introduced in §2 + Phase 0 + Phase 13.
    - **P45** v6 placed packages under `monorepo/packages/<name>/`; user direction is sibling polyrepos at `/laranail/<name>/`. Fixed: every Phase that referenced a monorepo path now references the sibling-repo path.
    - **P46** v6's Phase 7 used `git mv` to rename `packager` → `package-scaffolder` inside a monorepo; in a polyrepo world this is a directory rename plus a GitHub repo rename + Packagist update. Fixed: Phase 7 spells out the polyrepo rename steps and the original repo's archive-with-redirect-README.
    - **P47** v6 Decision E was open ("docs domain"); user supplied two domains in v7. New decision E2 surfaces about single-build vs two-sites — recommend single-build.
    - **P48** v6 estimate was ~84 h; polyrepo dropping monorepo tooling shaves ~3 h but adds ~5 h for the `laranail/.github` reusable workflows + Phase 18 sync script. Net: ~86 h.
  - Total estimate now **~86 h**.
- **v8** (2026-05-04) — Python package dropped. **5 → 4 packages.**
  - **ADR-007 rewritten**: tooling is pure PHP/Composer; no Python, no `uv`, no `rich`, no `pyproject.toml`, no PyPI package.
  - **Package #4 (`laranail/package-scaffolder-python`) removed.** Phase 9 (build Python package, 10 h) and Phase 10 (migrate Python consumers, 3 h) replaced by smaller phases — Phase 9 now deletes legacy scripts + writes `init.sh` (2 h), Phase 10 adds composer aliases (1 h).
  - **Phase 5 commands** (`package:doctor`, `package:audit`, `package:sbom`, `package:ide-helper`) become native Laravel Artisan commands in `package-tools` — more idiomatic, since they need to inspect an installed Laravel app's container anyway.
  - **§4 restructured** to put composer aliases (`§4.4`) front and centre as the contributor surface. `init.sh` (`§4.2`) is a thin ~40-line bash bootstrap.
  - **ADR-006** simplified: only one `.env` writer service (the PHP `EnvFileService`); removed mention of a parallel Python `lib/env.py`.
  - **Decision D** (Python distribution) marked obsolete.
  - **v8-specific bug log (P49+):**
    - **P49** v7 §3.2 had elaborate Python `lib/` design (`config.py`, `env.py`, `paths.py`, `runner.py`, `shell.py`, `codemod.py`, `audit.py`, `reporter.py`). Re-examination: the original `/scripts/` were ~80% one-shot codemods that already finished, ~20% wrappers around tools with first-class PHP/composer interfaces (Pest, PHPStan, Pint, Rector, `composer audit`). Dropped entirely.
    - **P50** v7's ADR-006 said "two services to build (`scripts/lib/env.py` + `Package\Services\Environment\EnvFileService`)." With Python dropped, only the PHP service remains. ADR-006 simplified.
    - **P51** v7's `init.sh` step list (host checks → `.env` provisioning → composer install → venv → config validation → audit → summary) had a Python venv step + a "validate config.json against schema via Python" step. v8's `init.sh` drops both — no venv, no JSON config (tool config goes in native files like `phpstan.neon`).
    - **P52** v7 `§3.2` tooling layout had `scripts/lib/`, `scripts/setup/`, `scripts/test/`, `scripts/lint/`, `scripts/audit/`, `scripts/codemod/`, `scripts/config.json`, `scripts/config.schema.json`, `scripts/requirements.txt`, `tests/scripts/`. All gone in v8. The `scripts/` directory now contains exactly one file: `init.sh`.
    - **P53** v7 Phase 5 listed `package:doctor` as "wires `Package::hasDoctorCheck()`" without specifying it was an Artisan command. Subsequent phrasing ("python3 scripts/audit/project_audit.py") implied Python was the runner. v8 explicitly: every Phase-5 item is a native Artisan command.
    - **P54** §6.3 S1 (polyrepo coordination) v7 mitigation mentioned `scripts/audit/suite_audit.py in package-scaffolder-python`. With Python dropped, the suite-audit becomes a small `gh`-based shell snippet in `laranail/.github` (run quarterly).
    - **P55** §10 Decision D was open ("Python distribution: PyPI or GitHub-only?"). Now obsolete.
    - **P56** v7's CI matrix included a `pip` ecosystem in Dependabot. v8 drops `pip` — only `composer` and `github-actions`.
    - **P57** v7's "Files to CREATE" included a Python package skeleton (pyproject.toml, uv.lock, .python-version, src/laranail_scaffolder/, etc.). All removed.
    - **P58** v7's `tests/scripts/` (Python `unittest` smoke tests for `lib/`) gone in v8. PHPUnit/Pest discovery no longer needs to exclude it.
    - **P59** v7 Phase 11 ("Move related docs") fragmented runtime vs scaffolder docs across two repos. v8 keeps the same logic — fine; no Python impact.
    - **P60** v7 Phase 14 ("composer.json script aliases + init.sh") was sub-1h. In v8 it's the primary developer surface and gets its own §4.4 in the spec; Phase 14 (now Phase 10) is correspondingly meaningful.
  - **Net savings: ~16 h** (Phase 9: −8 h; Phase 10: −2 h; init.sh simplification: −2 h; lost docs phase: −1 h; misc: −3 h).
  - Total estimate now **~70 h**.
- **v9** (2026-05-04) — **all decisions locked; plan is execution-ready.**
  - **Decision C accepted**: bump `laranail/laranail` from `^10.0 || ^11.0 || ^12.0` to `^13.0`. Cut `1.x-laravel12` LTS branch first as a frozen maintenance line.
  - **Decision E2 accepted (default)**: single VitePress build, two URL surfaces (`opensource.simtabi.com/` + `opensource.simtabi.com/laranail/`). Migrate to two-site model only if `opensource.simtabi.com` later hosts multiple sibling project families.
  - **Decision F accepted**: telemetry feature dropped from v1.x scope. Packagist already publishes install counts publicly; the maintenance + privacy surface isn't worth the marginal data. Re-evaluate at v2.0+ only if a concrete need surfaces.
  - **Decision G accepted (default)**: Strategy 1 — tiered Phase 5 release.
    - **Tier A → v1.0** (`#1` attributes + `discoversWithAttributes`, `#2` `package:doctor`, `#3` `IsolatedTestCase`, `#4` `EnvFileService`). ~5 h.
    - **Tier B → v1.1** (`#5` SBOM, `#6` OSV audit, `#7` install wizard). ~3 h.
    - **Tier C → v1.2** (`#8` ide-helper, `#9` facade auto-gen). ~2 h.
    - Telemetry deferred per Decision F.
  - **Phase 5 restructured** into 5A / 5B / 5C subsections with explicit ship-version markers.
  - §10 Decisions table: every row resolved. New row "All decisions resolved — plan is execution-ready."
  - **v9-specific bug log (P61+):**
    - **P61** v8 Phase 5 listed all 10 features as a single block; reviewer couldn't tell which would ship when. Fixed: split into 5A/5B/5C with explicit version targets.
    - **P62** v8's `Services\Telemetry\TelemetryService` was still in Phase 5's list. Per Decision F, dropped — replaced with a struck-through "deferred to v2.0+ or skip" entry under Phase 5 so the prior decision history is visible without polluting the active scope.
    - **P63** v8 estimate said `~70 h` without distinguishing v1.0 vs follow-up work. v9 splits: ~65 h to v1.0 + ~5 h post-v1.0 across v1.1/v1.2.
    - **P64** v8 Decision G's recommendation phrasing didn't make Tier B's grouping (security/install trio) and Tier C's grouping (DX trio) explicit. Fixed in §10 row + Phase 5 split.
  - **Total estimate now ~65 h to v1.0 + ~5 h post-v1.0 across two minor releases.**
  - **Ready to start at Phase 0.**

  **v6-specific bug log (P30+):**
  - **P30** v5 said trait pruning would delete 26 unused traits; user direction in v6 is "keep + wire all 51 the spatie way." All deletions reversed; ADR-004 specifies aggregator wiring.
  - **P31** v5's Phase 2 consolidated Development/BugHunter under `Package/Services/`; research revealed these are scaffolder-time analyzers and belong with `package-scaffolder`. Direction flipped in §6.2 M1 + Phase 2.
  - **P32** v5 had no architectural decision records; this plan added 10 ADRs in §3 to be formalized in Phase 17.
  - **P33** v5 didn't mention attribute-driven discovery despite Laravel 13 supporting it; ADR-009 + Phase 5 add it as the differentiator vs Spatie.
  - **P34** v5 didn't mention SBOM/OSV/CHANGELOG automation; Phase 16 covers all three.
  - **P35** v5 had no monorepo strategy; ADR-002 + Phase 0 add `symplify/monorepo-builder` + subtree-split.
  - **P36** v5 floored at PHP 8.0 / Laravel 9; user direction in v6 floors at 8.3 / 13. ADR-003 captures the bump.
  - **P37** v5 specified runtime moves to `laranail/laranail`; user clarified in v6 that `package-tools` is a separate package. §2 + ADR-001 captures the five-package family.
  - **P38** v5's "delete all unused traits" violated user's "lose nothing" principle (only made explicit in v6). ADR-004 preserves them via aggregators — net add of 0 deletions.
  - **P39** v5 specified `static` everywhere; user clarified `$this` for chaining and `static` for terminal. ADR-005 captures.
  - **P40** v5 had no public-API parity check vs Spatie's latest; v6 §5.1 lists every Spatie fluent method explicitly + Laranail extensions.
  - **P41** v5 had no isolation testing harness; ADR-009 + Phase 5 add `Testing\IsolatedTestCase`.

---

## A. Appendix — v5 single-repo cleanup phase detail (preserved for execution reference)

The v5 plan detailed Phases 0–11 + 10b for the `packager` repo cleanup. Most of those phases are absorbed into v6's Phase 1, 2, 8 (which target `packager` before splitting). The detailed step lists from v5 — namespace codemod logic, trait conflict audit recipes, `.env` parser quirk handling, runtime `EnvFileService` API, `init.sh` step list, etc. — remain valid and should be referenced during execution. Key fragments:

### A.1 Namespace codemod (Phase 1 C1)

`scripts/codemod/fix_broken_namespaces.py` — idempotent, dry-run by default. Rewrites every `Simtabi\Laranail\Packager\Services\X\Y` → `Simtabi\Laranail\Packager\Package\Services\X\Y` (Phase 1) or `…\PackageTools\…` after Phase 4 namespace transform. Verification: `grep -rn "Simtabi\\\\Laranail\\\\Packager\\\\Services\\\\" src/` returns zero.

### A.2 Trait conflict audit (Phase 3)

```bash
# For each domain aggregator, compute method overlap:
for agg in Concerns/*/Configures*.php; do
    methods=$(grep -hE "function [a-zA-Z]+\(" "$agg" | awk '{print $2}' | sed 's/(.*//')
    echo "$agg: $methods"
done | sort | uniq -d
```

Conflicts get resolved at the aggregator level via prefix-renaming. `Package` only ever sees aggregators.

### A.3 `EnvFileService` API surface (Phase 5)

`Simtabi\Laranail\PackageTools\Services\Environment\EnvFileService` — `final class` with `exists()`, `isReadable()`, `isWritable()`, `read(string $key, ?string $default = null)`, `all(): array`, `appendIfMissing(string $key, string $value, ?string $comment = null): bool`, `appendBlock(array $entries, ?string $sectionTitle = null): int`, `backup(): string`. `forceSet()` gated by `bool $acknowledgeDestructive` and emits `EnvFileMutated`. Atomic writes; `.bak.<timestamp>` backups; LF/CRLF detection; missing-trailing-newline handling; `${VAR}` interpolation in `EnvFileParser`.

### A.4 Configuration — superseded in v8

v3–v7 specified a `scripts/config.json` consumed by Python tooling. v8 (per ADR-007) drops that entire layer: tool config goes in native files (`phpstan.neon`, `pint.json`, `rector.php`, `phpunit.xml`), runtime config goes in standard Laravel `config/<package>.php` files. No JSON shape file or schema validator is needed.

### A.5 `.env` (Laravel-syntax) keys

```dotenv
# === Laravel framework keys (consumer fills) ===
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

# === laranail/* — dev-tool integration (all optional) ===
LARANAIL_GITHUB_TOKEN=
LARANAIL_CODECOV_TOKEN=
LARANAIL_COMPOSER_AUTH=
LARANAIL_LOG_LEVEL=INFO
LARANAIL_TELEMETRY=false
```

All package-owned keys prefixed `LARANAIL_*` (collision avoidance).

---

End of plan.
