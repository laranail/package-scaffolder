# REST API

Base path: `/api/v1` (configurable). Route names are prefixed `api.blog.`.
Reads are public; writes require the configured `auth_middleware`
(`auth:sanctum` by default). A machine-readable spec lives in
[`openapi.yaml`](openapi.yaml).

## Posts

| Method | URI | Auth | Notes |
| --- | --- | --- | --- |
| GET | `/posts` | public | Filter: `status`, `category`, `tag`, `featured`, `search`, `sort` (incl. `views`), `direction`, `per_page` |
| GET | `/posts/{post}` | public | 404 for drafts you cannot preview; `body` is source, `body_html` is rendered |
| POST | `/posts` | yes | `author_id` is rejected; accepts `is_featured`, `tags[]`, meta fields |
| PUT/PATCH | `/posts/{post}` | owner | Partial updates supported |
| DELETE | `/posts/{post}` | owner | Soft delete |
| POST | `/posts/{post}/publish` | owner | |
| POST | `/posts/{post}/unpublish` | owner | |
| POST | `/posts/{post}/restore` | owner | Restores a soft-deleted post |
| DELETE | `/posts/{post}/force` | owner | Permanent delete |

`{post}` is the post **slug**.

### Filtering example

```
GET /api/v1/posts?search=laravel&category=engineering&sort=title&direction=asc&per_page=20
```

`sort` is validated against `config('modules.blog.ui.sortable')` — anything else is a 422.

## Categories & tags

Categories: `apiResource` at `/categories` (reads public; writes admin-only). Tags: read-only
`GET /tags` and `GET /tags/{tag}`; filter posts with `GET /posts?tag={slug}`. Attach tags by
sending a `tags: []` array to the post store/update endpoints.

## Token abilities (opt-in)

Set `config('modules.blog.routes.api.abilities.write')` / `…moderate` to a Sanctum ability to require it on
write / moderation routes (via the `blog.ability` middleware). Null = no gate.

## Comments

| Method | URI | Auth | Notes |
| --- | --- | --- | --- |
| GET | `/posts/{post}/comments` | public | Approved comments only |
| POST | `/posts/{post}/comments` | guest ok | Rate limited + honeypot; optional `email`; never auto-approved by default |
| PATCH | `/comments/{comment}/approve` | admin | |
| DELETE | `/comments/{comment}` | owner/admin | |

Comments are **polymorphic** — the resource exposes `commentable_id` + `commentable_type` (the morph
alias, e.g. `blog_post`) instead of a bare `post_id`.

## Status codes

`200`, `201` (create), `204` (delete/force), `401`/`403` (auth/authz),
`404` (hidden/not found), `422` (validation), `429` (rate limit).

## Health

```
GET /api/v1/ping  →  { "ok": true, "package": "blog" }
```
