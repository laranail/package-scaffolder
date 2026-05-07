# Example — scaffold a new package end-to-end

```bash
# 1. In your Laravel 13+ app, install the scaffolder as a dev dependency.
composer require --dev laranail/package-scaffolder

# 2. Generate a new package skeleton.
php artisan make:package acme/widget

#    Output (default config writes to packages/acme/widget/):
#    ✓ packages/acme/widget/composer.json
#    ✓ packages/acme/widget/src/WidgetServiceProvider.php
#    ✓ packages/acme/widget/src/Widget.php
#    ✓ packages/acme/widget/config/widget.php
#    ✓ packages/acme/widget/database/migrations/2026_05_05_create_widgets_table.php
#    ✓ packages/acme/widget/resources/views/index.blade.php
#    ✓ packages/acme/widget/resources/lang/en/widget.php
#    ✓ packages/acme/widget/routes/web.php
#    ✓ packages/acme/widget/tests/Feature/WidgetTest.php
#    ✓ packages/acme/widget/.github/workflows/tests.yml
#    ✓ packages/acme/widget/CHANGELOG.md
#    ✓ packages/acme/widget/README.md
#    ✓ packages/acme/widget/LICENSE.md
#    ✓ packages/acme/widget/CODE_OF_CONDUCT.md

# 3. The generated composer.json already requires laranail/package-tools.
#    The generated WidgetServiceProvider already extends:
#      Simtabi\Laranail\PackageTools\PackageServiceProvider

# 4. Wire the new package into your app's composer.json (path repo).
composer config repositories.acme-widget path packages/acme/widget
composer require acme/widget:@dev

# 5. Verify the install:
php artisan package:doctor

# 6. Customise:
#    - packages/acme/widget/src/WidgetServiceProvider.php — fluent Package builder
#    - packages/acme/widget/config/widget.php — runtime config
#    - packages/acme/widget/database/migrations/...   — schema
```

## What the generator writes

The 139 stubs cover every standard Laravel-package shape:

| Path                                           | What                                  |
|-----------------------------------------------|---------------------------------------|
| `composer.json`                                | name, autoload, requires             |
| `src/<Name>ServiceProvider.php`                | extends `PackageServiceProvider`      |
| `src/<Name>.php`                               | main class                            |
| `config/<name>.php`                            | runtime config                        |
| `database/{migrations,seeders,factories}/`     | schema scaffolding                    |
| `resources/{views,lang,assets}/`               | view + i18n + asset directories       |
| `routes/{web,api,demo-routes}.php`             | route file scaffolding                |
| `tests/{Unit,Feature}/`                        | Pest test scaffolding                 |
| `.github/workflows/tests.yml`                  | reusable-workflow caller              |
| `pint.json`, `phpstan.neon`, `rector.php`      | tool configs (consistent with suite)  |
| `README.md`, `CHANGELOG.md`, `LICENSE.md`,     | community files                       |
| `CODE_OF_CONDUCT.md`, `CONTRIBUTING.md`,       |                                       |
| `SECURITY.md`                                  |                                       |

All stubs use the placeholder set: `{{vendor}}`, `{{package}}`,
`{{namespace}}`, `{{package_studly}}`, `{{package_upper}}`,
`{{author_name}}`, `{{author_email}}`, `{{description}}`,
`{{php_version}}`, `{{laravel_version}}`.

## Configuring the generator

The default scaffold lands at `packages/<vendor>/<package>/`. Override
via `config/scaffolder.php` (after publishing it):

```bash
php artisan vendor:publish --tag=package-scaffolder-config
```

Then edit `config/scaffolder.php` — `output_path`, default
`author_name`, `author_email`, license, etc.
