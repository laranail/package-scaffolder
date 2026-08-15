# Changelog

All notable changes to `laranail/package-scaffolder` are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2026-08-15

### Changed

- **Config keys are vendor-scoped.** `config('modules.*')` → `config('laranail.package-scaffolder.modules.*')`
  and `config('artifacts.*')` → `config('laranail.package-scaffolder.artifacts.*')`, published to
  `config/laranail/package-scaffolder/modules.php`. Laravel's config repository is a flat map, and
  `modules` and `artifacts` are names an application would very plausibly use for its own files.

- **Publish tags are vendor-scoped:** `config`, `stubs` and `vite` → `laranail::package-scaffolder-config`,
  `-stubs`, `-vite`. A tag of `config` is about as generic as one can get — `vendor:publish --tag=config`
  fired every package that claimed it, in registration order.

- **A generated module's own config publishes under `<module>-config`,** not the bare `config` every
  module used to share, so `vendor:publish --tag=config` no longer fires all of them at once. The
  module's name and not `laranail-`: `ModuleServiceProvider` is the base class a *consuming
  application's* modules extend, so that name belongs to the application. Its Blade component
  prefix is left alone for the same reason — it is the application's module namespace, not this
  package's.

The suite runs and passes: 483 tests, 1289 assertions. An earlier note here claimed it executed
zero tests — that was a stale autoloader, not an empty suite, and it hid 161 broken tests that the
rename had caused. Those are fixed.

Initial public release.
