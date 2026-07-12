# Livewire

Livewire support is **optional**. The components register themselves only when
`livewire/livewire` (v4) is installed — the package works fine without it.

```bash
composer require livewire/livewire
```

## Components

| Tag | Props | Behaviour |
| --- | --- | --- |
| `<livewire:modules-blog.post-list />` | — | Searchable, paginated list of published posts |
| `<livewire:modules-blog.comment-form :post="$post" />` | `:post` | Submits a comment (respects `comments.auto_approve`) |

```blade
<livewire:modules-blog.post-list />

<livewire:modules-blog.comment-form :post="$post" />
```

## Notes

- Components are registered with dotted names (`modules-blog.post-list`) which resolve
  cleanly in Livewire 4.
- The comment form creates **unapproved** comments by default, matching the REST
  API and the moderation workflow.
- The list component reuses the `<x-modules-blog::post-card>` Blade component for each row.
