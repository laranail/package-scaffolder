# Installation

## Requirements

- PHP `^8.4.1 || ^8.5`
- Laravel `^13.0`

Older Laravel releases (5.4 – 12) are served by earlier major lines; see the [CHANGELOG](../CHANGELOG.md).

## Install

```bash
composer require laranail/package-scaffolder
```

The service provider and the `Module` facade are auto-discovered. Optionally publish the config:

```bash
php artisan vendor:publish --provider="Simtabi\Laranail\Package\Scaffolder\Providers\LaravelModulesServiceProvider"
```

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
