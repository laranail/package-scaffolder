# Assets (Tailwind · Bootstrap · Vanilla)

The package ships **three independent CSS bundles**, built by Vite — pick one per app
(you never load more than one):

| Bundle | What it is | CSS/JS entry points | Default config you can edit |
| --- | --- | --- | --- |
| `tailwind` (default) | Tailwind CSS v4 (CSS-first) | `resources/assets/css/tailwind.css`, `resources/assets/scripts/tailwind.js` | `@theme { … }` tokens in `tailwind.css` |
| `bootstrap` | Bootstrap 5 (Sass) | `resources/assets/sass/bootstrap.scss`, `resources/assets/scripts/bootstrap.js` | `resources/assets/sass/_variables.scss` |
| `vanilla` | The package's own framework-agnostic base styles (bring-your-own CSS) | `resources/assets/scripts/app.js` (→ `sass/app.scss`) | `resources/assets/sass/app.scss` |

All three share the same restylable BEM base (`app.scss`); the framework bundles layer
Tailwind / Bootstrap on top of it.

## Building

```bash
npm install
npm run build        # or: npm run dev
```

Vite builds into the package-local `public/build` with a manifest.

## Customizing the defaults

- **Tailwind** — Tailwind v4 has no `tailwind.config.js`; edit the `@theme` block in
  `resources/assets/css/tailwind.css` (e.g. `--color-brand-500`, `--font-sans`). `@source
  "../../views"` scans the package's Blade for class usage.
- **Bootstrap** — edit the override tokens in `resources/assets/sass/_variables.scss`
  (`$primary`, `$border-radius`, …); they are fed to Bootstrap via `@use "bootstrap/scss/bootstrap"
  with (…)` (the correct Sass override mechanism).
- **Vanilla** — edit `resources/assets/sass/app.scss` (or override its `.blog__*` classes in your
  own CSS); no framework required.

## Choosing a bundle

```php
// config/blog.php → ui.framework
'framework' => env('BLOG_UI_FRAMEWORK', 'tailwind'), // tailwind | bootstrap | vanilla | none
```

## Publishing to the host app

```bash
php artisan vendor:publish --tag="modules/blog::assets"   # → public/vendor/blog
```

## Loading it

The package layout already includes the assets component, which reads the Vite manifest and
emits the right `<link>`/`<script>` tags for the active bundle:

```blade
<x-modules-blog::assets />                       {{-- uses config('modules.blog.ui.framework') --}}
<x-modules-blog::assets framework="vanilla" />   {{-- or force one --}}
```

(Replace the prefix with your `config('modules.blog.components.prefix')`.) Point
`config('modules.blog.ui.assets.manifest')` / `...base` at wherever you published the build if
you use a non-default location.
