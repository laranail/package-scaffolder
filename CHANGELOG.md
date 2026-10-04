# Changelog

All notable changes to `laranail/package-scaffolder` are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **`laranail::package-scaffolder-artifacts` publish tag** for `config/artifacts.php`, published to
  `config/laranail/package-scaffolder/artifacts.php`. The configuration docs already described it as
  a published file, but only `config.php` had a tag, so it could not be published at all.
- **Vendor-prefixed environment variables**: `LARANAIL_PACKAGE_SCAFFOLDER_ARTIFACT_DEFAULT_NAMESPACE`,
  `_ARTIFACT_DEFAULT_ENTITY`, `_ARTIFACT_DEFAULT_FLAVOR`, `_MODULE_VENDOR`, `_MODULE_AUTHOR_NAME` and
  `_MODULE_AUTHOR_EMAIL`. Each is read first; the old unprefixed name is the fallback.
- **A one-line deprecation warning when a command is invoked by a bare alias**, naming the
  vendor-scoped command to use instead. Implemented once, in the
  `Commands\Concerns\WarnsOnDeprecatedAlias` trait, which every command uses through its base class
  or directly; a test reads the live Artisan registry to assert all 70 do.

### Changed

- **Internal calls use the vendor-scoped command names.** `ModuleGenerator`, `ModelMakeCommand`,
  `SeedMakeCommand`, `ComponentClassMakeCommand`, `InstallCommand` and the migrate / migrate-fresh /
  migrate-refresh commands called sibling commands by their bare `module:*` names; they now call
  `laranail::package-scaffolder.*`, so no internal call prints the new deprecation warning. The test
  suite likewise invokes the scoped names, and a source scan keeps both that way.
- **Install docs agree on `composer require laranail/package-scaffolder`** (no `--dev`). The README
  said `--dev`; installation and getting-started did not. Modules generated with
  `laranail::package-scaffolder.make` extend this package's `ModuleServiceProvider` and are booted by
  its provider at runtime, so a `--no-dev` production install would break them.

### Deprecated

- **The bare command aliases** — `make:artifact` and all 69 `module:*` names (`module:make`,
  `module:migrate`, `module:make-model`, …). They stay registered and run the same command, with a
  warning; use `laranail::package-scaffolder.<command>`. Removal no earlier than the next minor
  after 0.1.
- **The unprefixed environment variables** `ARTIFACT_DEFAULT_NAMESPACE`, `ARTIFACT_DEFAULT_ENTITY`,
  `ARTIFACT_DEFAULT_FLAVOR`, `MODULE_VENDOR`, `MODULE_AUTHOR_NAME` and `MODULE_AUTHOR_EMAIL`. Still
  read as fallbacks; use the `LARANAIL_PACKAGE_SCAFFOLDER_` names. Removal no earlier than the next
  minor after 0.1.

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
