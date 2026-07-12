# Security Policy

## Reporting a vulnerability

Please do **not** open public issues for security vulnerabilities. Instead,
email the maintainer privately with:

- a description of the vulnerability and its impact,
- steps to reproduce, and
- any suggested remediation.

You will receive an acknowledgement, and a fix will be released as soon as
practical with credit unless you prefer to remain anonymous.

## Security features baked into this package

- All write endpoints are protected by Form Requests with real `authorize()`
  policy checks and strict validation.
- The post feed's `?sort=` parameter is validated against a config allow-list
  to prevent order-by injection.
- Comment submission is rate limited and protected by a honeypot plus a
  minimum-submit-time check; comments are never auto-approved by default.
- API resources whitelist output fields; soft-deleted and unpublished content
  is not exposed to unauthorized viewers.
- The author of a post/comment is always derived server-side, never from the
  request payload (`author_id` is rejected if supplied).

## Supported versions

The latest released minor version receives security fixes.
