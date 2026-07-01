# Package Scaffolder

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laranail/package-scaffolder.svg?style=flat-square)](https://packagist.org/packages/laranail/package-scaffolder)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/laranail/package-scaffolder.svg?style=flat-square)](https://packagist.org/packages/laranail/package-scaffolder)

| **Laravel** | **package-scaffolder** |
|-------------|---------------------|
| 5.4         | ^1.0                |
| 5.5         | ^2.0                |
| 5.6         | ^3.0                |
| 5.7         | ^4.0                |
| 5.8         | ^5.0                |
| 6.0         | ^6.0                |
| 7.0         | ^7.0                |
| 8.0         | ^8.0                |
| 9.0         | ^9.0                |
| 10.0        | ^10.0               |
| 11.0        | ^11.0               |
| 12.0        | ^12.0               |
| 13.0        | ^13.0               |

`laranail/package-scaffolder` is a Laravel package for managing a large Laravel app
as a set of modules (HMVC). A module is like a mini Laravel package — it has its own
views, controllers, models, migrations and service providers.

`laranail/package-scaffolder` is developed and maintained by Simtabi LLC.

## Install

To install via Composer, run:

``` bash
composer require laranail/package-scaffolder
```

The package will automatically register a service provider and alias.

Optionally, publish the package's configuration file by running:

``` bash
php artisan vendor:publish --provider="Simtabi\Laranail\Package\Scaffolder\Providers\LaravelModulesServiceProvider"
```

### Autoloading

> from v11.0 autoloading `"Modules\\": "modules/",` is no longer required, and should be removed from your composer.json if present.

By default, the module classes are not loaded automatically. You can autoload your modules by adding merge-plugin to the extra section:

```json
"extra": {
    "laravel": {
        "dont-discover": []
    },
    "merge-plugin": {
        "include": [
            "Modules/*/composer.json"
        ]
    }
},
```

**Important**

on the first installation you will be asked:

```bash
Do you trust "wikimedia/composer-merge-plugin" to execute code and wish to enable it now? (writes "allow-plugins" to composer.json) [y,n,d,?]
```

Answer `y` to allow the plugin to be executed. Otherwise, you will need to manually enable the following to your composer.json:

```json
"config": {
    "allow-plugins": {
        "wikimedia/composer-merge-plugin": true
    }
```

> if `"wikimedia/composer-merge-plugin": false` modules will not be autoloaded.

> If you hit a `Class "Modules\…\…ServiceProvider" not found` error after following the
> setup, the merge-plugin step above is almost always the cause: the plugin must be
> allowed (the `allow-plugins` entry) and `composer dump-autoload` re-run so each
> `Modules/*/composer.json` is merged.

**Tip: don't forget to run `composer dump-autoload` afterwards**

## Documentation

You'll find documentation on [https://opensource.simtabi.com/package-scaffolder/docs/](https://opensource.simtabi.com/package-scaffolder/docs/).

- [Generating artifacts (`make:artifact`)](docs/make-artifact.md) — types, plugins, features, portability, composer wiring.
- [Architecture](docs/ARCHITECTURE.md) — the generated-artifact structure (package/module/plugin) and the scaffolder's own layout.

## Credits

- [Simtabi LLC](https://github.com/simtabi)
- [Imani Manyara](https://github.com/imanimanyara)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
