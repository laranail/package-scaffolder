# Blade components

Components are registered under a **configurable prefix** (the slug of the vendor/package, since
Blade/Livewire tags can't contain `/`). The default is `modules-blog` — `<x-modules-blog::post>`,
`<livewire:modules-blog.post-list>` — overridable via `config('modules.blog.components.prefix')`
(both Blade and Livewire components follow it). Views and translations use the matching
`modules/blog::` namespace (`view('modules/blog::posts.index')`, `__('modules/blog::blog.posts')`).

## Restylable (overridable defaults)

Every component forwards your `class`/`id`/`style`/attributes via `$attributes->merge()`, so you
can extend or restyle freely (or bring your own CSS framework):

```blade
<x-modules-blog::status-badge :status="$post->status" class="ml-2 uppercase" id="badge" data-x="1" />
```

The default `blog__*` class stays; your class is appended.

## Class **and** classless

You can add a component as a class (`src/View/Components/*.php` + a view) **or** as a plain,
classless `.blade.php` in `resources/views/components/` — both resolve as `<x-modules-blog::name>`
(class components win their names; classless is the fallback). Example classless component:
`<x-modules-blog::meta-item>`.

## Section components (layout-agnostic)

Drop these into **your own** layout — no package layout required:

| Tag | Props | Renders |
| --- | --- | --- |
| `<x-modules-blog::posts>` | `:posts` (optional), `:perPage` | A post list/feed (uses `post-card`) |
| `<x-modules-blog::post>` | `:post` | A full post (meta, body, tags, related, comments, form) |
| `<x-modules-blog::comments>` | `:post` or `:comments` | Approved comments |
| `<x-modules-blog::comment-form>` | `:post` | Auth-aware comment form (or login prompt) |
| `<x-modules-blog::post-card>` | `:post` | A post summary card |
| `<x-modules-blog::status-badge>` | `:status` | A coloured status pill |
| `<x-modules-blog::meta>` | `:post` | SEO head tags (see [features.md](features.md)) |
| `<x-modules-blog::auth-links>` | — | User menu / login / logout (see [partials.md](partials.md)) |
| `<x-modules-blog::login-prompt>` | `message` | "Log in to continue" (classless) |
| `<x-modules-blog::alert>` | `type` | A flash/alert box around its slot |
| `<x-modules-blog::assets>` | `framework` | The active CSS bundle's tags (see [assets.md](assets.md)) |

## Configurable layout

The package's own pages `@extends(config('modules.blog.ui.layout'))` (default `modules/blog::layouts.master`).
Point it at your app's layout to embed the pages, or skip the pages entirely and use the section
components above. The package shares `$blogLayout`, `$blogComponentPrefix` and a
`$blogRoute(name, params)` resolver to its views via a composer, so nothing hardcodes them.

## Publishing the views

```bash
php artisan vendor:publish --tag="modules/blog::views"   # → resources/views/vendor/blog
```
