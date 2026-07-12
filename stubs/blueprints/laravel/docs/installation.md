# Installation

## Requirements

- PHP `^8.4.1 || ^8.5`
- Laravel `^13.0`

## Install

```bash
composer require modules/blog
```

The service provider (`Some\NamespacePath\Blog\Providers\BlogServiceProvider`) and the
`Blog` facade are auto-discovered.

## Set up

The fastest path is the interactive installer:

```bash
php artisan blog:install
```

It publishes the config and compiled assets, then offers to run the migrations.

### Manual setup

```bash
php artisan vendor:publish --tag="modules/blog::config"        # config/blog.php
php artisan vendor:publish --tag="modules/blog::assets"        # public/vendor/blog (optional)
php artisan migrate
```

Migrations are loaded by the package automatically (`runsMigrations()`), so you
only need `php artisan migrate`.

## Verify

```bash
php artisan laranail::package-tools.doctor   # runs the Blog health check
php artisan about                            # shows the "Blog" section
php artisan route:list --name=blog           # lists the package routes
```

## Optional Livewire

```bash
composer require livewire/livewire
```

The Livewire components register themselves only when Livewire is present.
