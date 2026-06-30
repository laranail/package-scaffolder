# Configuration

All options live in the package's `config/blog.php`, namespaced under the `modules/blog` vendor:
they publish to `config/modules/blog.php` and are read via `config('modules.blog.*')`.
(`env('BLOG_*')` variable names are unchanged.)

| Key | Default | Purpose |
| --- | --- | --- |
| `user_model` | `App\Models\User` | Host authenticatable model for authors. Override with `BLOG_USER_MODEL`. |
| `pagination.per_page` | `15` | Default page size. |
| `pagination.max_per_page` | `100` | Hard cap for the API `per_page` param. |
| `comments.allow_guests` | `true` | Allow unauthenticated comment submissions. |
| `comments.auto_approve` | `false` | Whether new comments are visible immediately. |
| `comments.honeypot` | `website` | Hidden field name that must stay empty. |
| `comments.min_submit_seconds` | `2` | Reject comments submitted faster than this. |
| `comments.max_length` | `2000` | Max comment body length. |
| `notifications.channels` | `['mail']` | Channels for the "post published" notification. |
| `scheduling.enabled` | `true` | Self-register `blog:publish-scheduled` on the host scheduler. |
| `scheduling.cron` | `* * * * *` | Cron expression for the scheduled publish. |
| `scheduling.timezone` | `null` | Timezone the cron is interpreted in (`null` = the app default). |
| `scheduling.on_one_server` | `false` | Run on a single server in a multi-server deploy (needs a shared cache lock). |
| `routes.web` | … | Toggle/prefix; `middleware` (public base) + `auth_middleware` (author writes). |
| `routes.api` | … | Toggle/prefix/name/middleware; `auth_middleware` + optional `abilities` guard writes. |
| `routes.api.abilities.write` / `.moderate` | `null` | Optional Sanctum token ability required on writes/moderation (null = no gate). |
| `rate_limiting.comments_per_minute` | `5` | The `blog-comments` limiter. |
| `security.sanitize_html` / `.allowed_tags` | `true` / allow-list | Strip dangerous HTML from post bodies on save (a model-layer pipeline stage). |
| `processing.markdown` | `false` | Render Markdown bodies to HTML on display (needs `league/commonmark`). See [features.md](tools/features.md). |
| `processing.stages` | `[SanitizeHtmlStage]` | Ordered save-time body stages (class-strings). Add more via `Blog::pipe()` / the `blog.body.stages` tag. See [extending.md](tools/extending.md). |
| `search.driver` | `database` | Active search driver (`database`/`scout`/custom). See [search.md](tools/search.md). |
| `cache.enabled` / `.ttl` | `false` / `3600` | Opt-in read caching (repository decorator, event-busted). |
| `popular_by` | `comments` | `popularPosts()` ranking: `comments` or `views`. |
| `features.rss` / `.sitemap` / `.feed_limit` / `.related_limit` | `true`/`true`/`20`/`5` | Toggle feeds/sitemap; feed size; default related-posts count. |
| `features.count_views` | `false` | Count a view on the web show route (`Blog::recordView`). |
| `ui.default_status` | `draft` | Default status for new posts. |
| `ui.sortable` | `['published_at','created_at','title','views']` | Allow-list for the API `?sort=` param. |
| `ui.framework` | `tailwind` | CSS bundle the assets component loads (`tailwind`/`bootstrap`/`vanilla`/`none`). |
| `morph_map` | `[]` | **Host** commentable/taggable models → stable aliases (`'product' => Product::class`). The package's own models are aliased in code (`Blog::morphMap()`) and always registered, so clearing this can't make them store the placeholder FQCN. |
| `ui.assets.build_directory` | `vendor/blog/build` | Where the package's built assets live (relative to `public/`). The framework→entry-point mapping is internal build wiring and lives in code (`Assets::BUNDLES`), not config. See [assets.md](tools/assets.md). |
| `ui.assets.live` | `false` | `false` = load the already-compiled build (plain `<link>`/`<script>` for the hashed files); `true` = drive it through Laravel's Vite runtime (modulepreload + HMR when the dev server runs). |
| `components.prefix` | `modules-blog` | Unique prefix for Blade/Livewire components; normalized to a slug. |
| `ui.layout` | `modules/blog::layouts.master` | Parent layout the package pages `@extends`; point at your app's layout to embed. |
| `ui.routes` | `blog.*` map | Route names the components link to (remap to mount elsewhere; missing → `#`). |
| `ui.auth` | `login`/`register`/`logout` | Host auth route names used by the auth components. |

## Decoupling from Sanctum

The API write routes default to `['auth:sanctum']`. To use a different guard:

```php
'routes' => [
    'api' => [
        'auth_middleware' => ['auth:api'],   // or ['auth']
    ],
],
```

## Disabling a route group

```php
'routes' => ['web' => ['enabled' => false]],
```
