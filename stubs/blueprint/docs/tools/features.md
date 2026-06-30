# Features

Standard blog features, kept minimal (no admin UI, no upload pipeline).

## SEO

`blog_posts` carries `meta_title` and `meta_description`. The head component emits `<title>`,
meta description, canonical, OpenGraph, Twitter cards and JSON-LD `Article`:

```blade
<head>
    <x-modules-blog::meta :post="$post" />
</head>
```

Falls back to the post title/excerpt when meta fields are empty.

## Featured image

A `featured_image` column (a URL/path string — bring your own storage). Surfaced by
`<x-modules-blog::post-card>` / `<x-modules-blog::post>` and the API. Validated `nullable|url`.

## Reading time

`$post->reading_time` — estimated minutes (≈200 wpm, min 1). No DB column; exposed in the
post card/meta and the `PostResource` (`reading_time`).

## Related posts

`Blog::related($post)` — published posts sharing the category, recent, excluding self
(limit via `config('modules.blog.features.related_limit')`). Rendered by `<x-modules-blog::post>` and
`@include('modules/blog::partials.related', ['related' => …])`.

## RSS / Atom feed & sitemap

Public, toggleable endpoints (`config('modules.blog.features.rss'|'sitemap')`):

```
GET /blog/feed          → application/rss+xml
GET /blog/sitemap.xml   → application/xml
```

## Tags

A `Tag` model + a **polymorphic** `blog_taggables` pivot (`morphToMany`/`morphedByMany`), so tags
attach to posts or any host model. Attach on create/update:

```php
Blog::create(PostData::fromArray(['title' => '…', 'body' => '…', 'tags' => ['Laravel', 'PHP']]));
```

Filter the API/feed by tag (`/api/v1/posts?tag=laravel`), read tags via `/api/v1/tags`, and
render the `@include('modules/blog::partials.tags')` widget (data auto-injected). Tags are flat
(no hierarchy).

## Markdown (opt-in, source-preserving)

Enable `config('modules.blog.processing.markdown')` (+ `composer require league/commonmark`) and bodies
authored in Markdown are rendered to HTML **on display** (`$post->rendered_body`, used by the
`<x-modules-blog::post>` component and the API `body_html` field). The stored `body` stays the author's
Markdown source, so editing round-trips. The save-time sanitizer still runs, so **raw HTML inside a
Markdown body is stripped** — author content in Markdown syntax (which `strip_tags` leaves intact),
not embedded HTML.

## View counts & popularity

A `views` column + `Blog::recordView($post)` (atomic). Enable `config('modules.blog.features.count_views')`
to count on the web show route, or call `recordView()` yourself. `Blog::popularPosts()` ranks by
`config('modules.blog.popular_by')` — `comments` (default) or `views`.

## Featured / pinned posts

An `is_featured` column + `Post::scopeFeatured()` + `Blog::featured()`. Surface a "pinned"
section without a separate model.

## Pluggable search & caching

Search is driver-based (`database` default, optional `scout`, or your own) — see
[search.md](search.md). Opt-in read caching (`config('modules.blog.cache.enabled')`) decorates the
repository and busts on lifecycle events — see [extending.md](extending.md).

## Extensibility

Macros, a body-processing pipeline, container decoration, swappable search drivers and a full
set of lifecycle events let consumers reshape the package at runtime. See
[extending.md](extending.md) and the optional admin [panels](panels.md).
