# Codemod archive

Records what each retired one-shot script did, so the institutional knowledge
isn't lost when the file is deleted (per ADR-007's bash-elimination policy).

## Retired in Phase 9 (laranail suite cleanup)

All scripts below lived under `scripts/` until Phase 9 deleted them. They had
already done their work (or have first-class PHP/Composer replacements). For
new automation, use Composer scripts (`composer test` / `lint` / `audit`),
Rector for code transformations, or Pint for formatting.

### Restructuring (one-shot, completed)

- **`restructure.sh`** — Master orchestrator that performed the
  `Packager → Package` + `Generator → Scaffolder` namespace migration. Work
  landed in commits over Nov–Dec 2025; further structural moves landed in
  Phases 4 + 7 of this cleanup. No replacement needed.
- **`update-namespaces.php`** — Regex-based namespace transformations
  applied alongside `restructure.sh`. Superseded by Phase 1 codemod
  (`fix_broken_namespaces.php`, archived under `.artifacts/`) and by
  Rector for any future bulk renames.
- **`update-configs.php`** — Updated config-file headers + `config()`
  call references during the Packager→Package rename. Work landed.
- **`update-composer.php`** — Updated `composer.json` PSR-4 mappings +
  service-provider entries during the rename. Work landed in Phase 4 +
  Phase 7.
- **`verify-restructure.php`** — Read-only validator that confirmed the
  restructure produced the expected structure. Replaced by:
  `composer test` (Pest verifies the runtime), `composer lint` (PHPStan
  + Pint + Rector verify static structure), and the per-package CI
  workflows in Phase 13.

### Plan / docs cleanup (one-shot, completed)

- **`fix_plans_comprehensive.py`** — String replacements over the legacy
  `PROJECT_PLANS/*.txt` files (paths, TODO markers, return-type
  annotations). PROJECT_PLANS itself is archived in Phase 12.
- **`fix_plans_manual.sh`** — sed-based companion to the above. Same
  fate.

### Generic dev tooling (replaced by Composer aliases)

- **`run_tests.py`** — OOP wrapper around Pest/PHPUnit. Replaced by
  `composer test`.
- **`test.sh`** — Simple Pest invocation wrapper. Replaced by
  `composer test`.
- **`validate_code.sh`** — Ran PHPStan + Psalm + PHP CS Fixer + `php -l`.
  Replaced by `composer lint` (Pint + PHPStan + Rector dry-run, all
  configured in `composer.json`).

### Static-analysis (replaced by tools with first-class PHP interfaces)

- **`bug_hunter.py`** — Scanned src/ for hardcoded values, missing type
  hints, native-function usage. PHPStan level 8 (configured at
  `phpstan.neon`) and Rector type-declaration sets (configured at
  `rector.php`) cover this with better accuracy and IDE integration.
- **`analyze_project.py`** — Reported project structure, configs,
  helpers, stubs, tests, and separation status as JSON. Replaced by
  Composer's standard `composer info` + `composer outdated` and by ad-hoc
  `find` / `grep` invocations when needed. Cross-repo audits (rare) are
  handled by a small `gh`-based shell snippet in `laranail/.github`.

## How to add new automation

Per ADR-007: tooling is pure PHP/Composer. The contributor surface is:

```
composer setup        # bash scripts/init.sh
composer test         # vendor/bin/pest
composer lint         # pint + phpstan + rector --dry-run
composer audit        # composer audit + (optional) osv-scanner
```

For codemods, use Rector. For one-off shell tasks during a phased
cleanup, write a brief PHP one-shot under `.artifacts/` (gitignored)
rather than committing it to `scripts/`.
