# Security

Security is treated as a first-class feature, not an afterthought.

## Checklist

- [x] Policy-backed Form Requests on every write (no `return true`)
- [x] Order-by allow-list (`ui.sortable`) — anti SQL-injection
- [x] Honeypot + min-submit-time + rate limiting on comments
- [x] Server-side authorship (`author_id`/`approved` never from input)
- [x] Field-whitelisted API resources (no PII / soft-deleted leakage)
- [x] Drafts hidden (404, not 403)
- [x] Write-time HTML sanitization of post bodies
- [x] Optional Sanctum token abilities on the API
- [x] CSRF on web forms; Blade output escaped by default

## HTML sanitization

Post bodies are stripped to an allow-list of tags on save
(`config('modules.blog.security.sanitize_html')` + `allowed_tags`), and the stage additionally removes
inline event handlers (`onclick=`, `onerror=`, …) and dangerous URL schemes
(`javascript:`/`vbscript:`/`data:`) that `strip_tags` would otherwise leave on allowed `<a>`/`<img>`
tags. This is a solid **dependency-free baseline**; for fully-trusted rich HTML input add a real HTML
purifier as a body stage (see [extending.md](tools/extending.md)).

Sanitization runs at the **model layer** (the always-last `BodyProcessor` stage, invoked from the
`saving` observer), so it applies to **every** writer — facade/API/CLI, [[plugins]]Filament, Nova and [[/plugins]]raw
Eloquent — and to the output of any consumer `pipe()` stage (which runs *before* it). Because the
stored body is already sanitized, the `<x-modules-blog::post>` component renders it as HTML via
`renderedBody()` (and renders Markdown on display from the preserved source when enabled). Keep
`sanitize_html` on unless you sanitize upstream.

## API token abilities (opt-in)

Set `config('modules.blog.routes.api.abilities.write')` / `…moderate` to a Sanctum ability (e.g. `blog:write`)
to require tokens to carry it on write/moderation routes (the `blog.ability` middleware also
`tokenCan()`-checks). Null (the default) is a no-op, so plain-token / session setups are
unaffected.

## Authorization

- Every mutating endpoint is a **Form Request** whose `authorize()` calls a
  policy or gate — there is no `return true`.
- Policies (`PostPolicy`, `CategoryPolicy`, `CommentPolicy`, `TagPolicy`) are
  enforced via `authorizeResource`/`authorize()`; post publishing uses the
  `publish` policy ability and comment moderation the `blog.moderate-comments` gate.
- Drafts and scheduled posts are hidden from the public by the
  `blog.published` middleware (404, not 403, so existence isn't leaked).

## Input validation

- Strict, typed rules on every field (lengths, enums, `exists`/`unique` against
  models, `alpha_dash` slugs).
- The feed's `?sort=` parameter is validated against an **allow-list**
  (`config('modules.blog.ui.sortable')`), preventing order-by injection.
- `author_id` is **prohibited** in payloads — authorship is always derived
  server-side from the authenticated user.
- `approved` is prohibited on comment submission — approval is server-controlled.

## Anti-spam

- Comment submission is **rate limited** (`blog-comments` limiter).
- A **honeypot** field plus a **minimum-submit-time** check block bots.
- Comments are **never auto-approved** unless `comments.auto_approve` is enabled.

## Output safety

- API Resources whitelist fields — no internal columns, PII or soft-deleted rows
  are exposed.
- Blade output is escaped (`{{ }}`); post bodies are escaped before `nl2br`.

## Mass-assignment

- Models use `$fillable`; writes go through DTOs and Actions, so request input
  can never set unintended columns.

Report vulnerabilities privately — see [../SECURITY.md](../SECURITY.md).
