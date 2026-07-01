# Admin panels (Filament & Nova)

Optional, first-class adapters that reuse the package's models and the `Blog` manager. Each
lives behind a `class_exists`-guarded service provider, so the package stays **headless** when
the panel isn't installed — nothing to configure to opt out.

Admin writes persist via Eloquent, so the **model-layer invariants still apply**: bodies are
sanitized and the lifecycle events fire, exactly as for the facade/API/CLI.

> These are adapter **skeletons** verified against Filament v4 and Nova v5 (mid-2026). Both
> move between major versions — confirm field/component signatures against the version you
> install, and that it supports your Laravel version.

## Filament

```bash
composer require filament/filament:^4.0
```

Add the plugin to your panel provider:

```php
use Some\NamespacePath\Blog\Filament\BlogPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugin(BlogPlugin::make());      // posts, categories, tags, comments
    // or omit comment moderation:
    // return $panel->plugin(BlogPlugin::make()->comments(false));
}
```

Resources: `PostResource`, `CategoryResource`, `TagResource`, `CommentResource` (comment
moderation toggles `approved`, firing `CommentApproved`). They are simple (modal) resources;
customise by publishing/overriding them in your app.

> **Version note:** Filament v4's `form()` takes `Filament\Schemas\Schema` (v3 took
> `Filament\Forms\Form`); v5 is GA and has moved on again. Verify v4 supports Laravel 13.

## Nova

```bash
composer require laravel/nova   # paid/private — never required by this package
```

The package registers its resources via `Nova::serving()` automatically (guarded). To add the
optional sidebar tool, register it in your `NovaServiceProvider`:

```php
protected function tools(): array
{
    return [ new \Some\NamespacePath\Blog\Nova\Tools\BlogTool() ];
}
```

Resources: `Nova\Resources\{Post,Category,Tag,Comment}`.

> **Version note:** targets Nova v5 (typed `fields(NovaRequest)`). Verify the release declares
> Laravel 13 support before pinning.

## Why these aren't in the package's own CI

Nova is paid (can't be installed in CI) and Filament v4 may not declare Laravel 13 support, so
`src/Filament` and `src/Nova` are excluded from static analysis and integration tests. They are
verified as **no-ops when the panel is absent** (proving the package stays headless) plus lint;
full panel testing happens in a host app.
