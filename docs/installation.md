# Installation

## Requirements

- PHP `^8.4.1 || ^8.5`
- Laravel `^13.0`

Older Laravel releases (5.4 – 12) are served by earlier major lines; see the [CHANGELOG](../CHANGELOG.md).

## Install

```bash
composer require laranail/package-scaffolder
```

Install it as a regular dependency, not with `--dev`. The generator itself is author-time, but the
package also carries the runtime that modules generated with `laranail::package-scaffolder.make`
depend on: their service provider extends
`Simtabi\Laranail\Package\Scaffolder\Providers\ModuleServiceProvider`, and this package's provider
discovers and boots them. A `composer install --no-dev` in production would drop that base class and
the loader. (Artifacts from `laranail::package-scaffolder.new` are self-contained and loaded by
[`laranail/package-management`](https://github.com/laranail/package-management); if that is all you
generate, `--dev` works, but a regular dependency is the safe default.)

The service provider and the `Module` facade are auto-discovered. Optionally publish the config
files, each under its own tag:

```bash
php artisan vendor:publish --tag=laranail::package-scaffolder-config      # config/laranail/package-scaffolder/modules.php
php artisan vendor:publish --tag=laranail::package-scaffolder-artifacts   # config/laranail/package-scaffolder/artifacts.php
```

## Command names

Every command is named `laranail::package-scaffolder.<command>` (for example
`laranail::package-scaffolder.make`, `.migrate`, `.new`). The former names, `module:<command>` and
`make:artifact`, remain registered as deprecated aliases: they run the same command and print a
one-line warning naming the replacement. They are removed no earlier than the next minor after 0.1.

The warning comes from `laranail/console`'s shared `WarnsOnDeprecatedAlias`: each command lists its
old name in `$deprecatedCommandAliases`. The warning is one line printed before the command's own
output, and the exit code is unchanged. It currently lands on stdout, not stderr: Laravel wraps the
terminal output in an `OutputStyle` before the trait sees it, so the trait's stderr route is not
taken. A script that parses a command's output should call the scoped name.

## Container alias

The module repository is registered in the container as `laranail.package-scaffolder.modules`
(`ModulesServiceProvider::CONTAINER_ALIAS`), and the `Module` facade resolves through it. Resolve it by
that name or by `Contracts\RepositoryInterface`:

```php
app('laranail.package-scaffolder.modules')->find('Blog');
```

`modules`, the name nwidart/laravel-modules uses, remains registered as a deprecated alias of the same
repository, so `app('modules')` and `$app['modules']` keep working. It is a plain container alias, so it
prints no warning. It is removed no earlier than the next minor after 0.1.

## Autoloading generated modules

Generated modules are autoloaded through `wikimedia/composer-merge-plugin`. In the host app's
`composer.json`, include each module's `composer.json` and allow the plugin, then re-dump the autoloader:

```json
"extra": {
    "merge-plugin": { "include": ["Modules/*/composer.json"] }
},
"config": {
    "allow-plugins": { "wikimedia/composer-merge-plugin": true }
}
```

```bash
composer dump-autoload
```

> A `Class "Modules\…\…ServiceProvider" not found` error almost always means the plugin isn't allowed, or
> `composer dump-autoload` wasn't re-run after adding a module.

For running generated artifacts at runtime (discovery, activation, wiring), pair the scaffolder with
[`laranail/package-management`](https://github.com/laranail/package-management).

[← Docs index](../README.md#documentation)
