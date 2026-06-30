# Partials

Two kinds of reusable, `@includable` partials — both restylable via an optional `$class`.

## Composer-backed (auto-injected data)

Registered through a single declarative `View::composer` map in the provider, so the data is
fetched (via the manager) and injected automatically — you just `@include`:

```blade
@include('modules/blog::partials.categories')        {{-- $categories with post counts --}}
@include('modules/blog::partials.recent-posts')      {{-- $recentPosts --}}
@include('modules/blog::partials.popular-posts')     {{-- $popularPosts (by comment count) --}}
@include('modules/blog::partials.archive')           {{-- month buckets --}}
@include('modules/blog::partials.tags')              {{-- $tags --}}

@include('modules/blog::partials.categories', ['class' => 'my-sidebar'])   {{-- restyle --}}
```

Add a widget = one entry in the map (`registerComposerPartials()` in the service provider).

## Auth partials / components

Auth-state-aware and configurable via `config('modules.blog.ui.auth')` (host login/register/logout
route names, `Route::has`-guarded):

```blade
<x-modules-blog::auth-links />        {{-- user name + login/logout/register --}}
<x-modules-blog::login-prompt />      {{-- "log in to continue" (classless component) --}}
<x-modules-blog::comment-form :post="$post" />   {{-- form when allowed, else login prompt --}}
```

The comment form posts to the configurable web route (`modules.blog.comments.store`) and honours
`comments.allow_guests` + the honeypot/anti-flood checks.
