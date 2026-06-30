# Assets (Tailwind · Bootstrap · Vanilla)

The package ships **three independent CSS bundles**, built by Vite — pick one per app
(you never load more than one):

| Bundle | What it is | CSS/JS entry points | Default config you can edit |
| --- | --- | --- | --- |
| `tailwind` (default) | Tailwind CSS v4 (CSS-first) | `resources/assets/css/tailwind.css`, `resources/assets/scripts/tailwind.js` | `@theme { … }` tokens in `tailwind.css` |
| `bootstrap` | Bootstrap 5 (Sass) | `resources/assets/sass/bootstrap.scss`, `resources/assets/scripts/bootstrap.js` | `resources/assets/sass/_variables.scss` |
| `vanilla` | The package's own framework-agnostic base styles (bring-your-own CSS) | `resources/assets/scripts/blog.js` (→ `sass/blog.scss`) | `resources/assets/sass/blog.scss` |

All three share the same restylable BEM base (`blog.scss`); the framework bundles layer
Tailwind / Bootstrap on top of it.

## Building

```bash
npm install
npm run build        # production build → public/build (+ .vite/manifest.json)
npm run dev          # dev server with hot-module reload (HMR)
```

Vite builds into the package-local `public/build` with a manifest. The assets are resolved by
**Laravel's Vite handler**, so `npm run dev` gives HMR while developing the package (it writes a
`hot` file the component detects), and production emits hashed URLs + modulepreload.

## Customizing the defaults

- **Tailwind** — edit the CSS-first `@theme` block in `resources/assets/css/tailwind.css`
  (e.g. `--color-brand-500`, `--font-sans`); `@source "../../views"` scans the package's Blade for
  class usage. A `tailwind.config.js` (loaded via `@config`) is also included — see *Tailwind config*
  below.
- **Bootstrap** — edit the override tokens in `resources/assets/sass/_variables.scss`
  (`$primary`, `$border-radius`, …); they are fed to Bootstrap via `@use "bootstrap/scss/bootstrap"
  with (…)` (the correct Sass override mechanism).
- **Vanilla** — edit `resources/assets/sass/blog.scss` (or override its `.blog__*` classes in your
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

The package layout already includes the assets component, which emits the right `<link>`/`<script>`
tags for the active bundle (via Laravel's Vite handler):

```blade
<x-modules-blog::assets />                       {{-- uses config('modules.blog.ui.framework') --}}
<x-modules-blog::assets framework="vanilla" />   {{-- or force one --}}
```

(Replace the prefix with your `config('modules.blog.components.prefix')`.) If you published the
build somewhere other than the default `public/vendor/blog/build`, point
`config('modules.blog.ui.assets.build_directory')` (relative to `public/`) at it. The component
falls back to the package-local `public/build` automatically during package development, and emits
nothing when the framework is `none` or the assets aren't built yet.

### Compiled (default) vs live

`config('modules.blog.ui.assets.live')` chooses how the bundle loads:

| `live` | Behaviour |
| --- | --- |
| `false` (default) | Loads the **already-compiled** build — plain `<link>`/`<script>` tags pointing at the hashed files (the hash is the cache-buster). No Vite runtime, no dev server. Ideal for production / consumers who just use the shipped build. |
| `true` | Drives the bundle through Laravel's **Vite** runtime: hashed URLs + modulepreload, and **HMR** when `npm run dev` is running (a `hot` file is present). Use this while customizing the package's styles. |

The framework → Vite entry-point mapping is internal build wiring (it mirrors `vite.config.js`), so
it lives in code (`Assets::BUNDLES`) — not in your published config, where it would freeze the
package's internal paths.

### Tailwind config

Tailwind v4 is **CSS-first**: theme tokens live in `resources/assets/css/tailwind.css` (the `@theme`
block) and source scanning uses `@source`. A `tailwind.config.js` is also included and loaded via
`@config` in that file — it provides the familiar `content` / `darkMode` / `theme.extend` / `plugins`
surface and powers the Tailwind IntelliSense editor extension. Keep theme tokens in CSS (`@theme`);
use `theme.extend` in the JS config only for things you prefer to express there.
