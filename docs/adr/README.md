# Architectural Decision Records (ADRs) — package-scaffolder

This directory holds package-specific ADRs (numbered from `0100`+) and
cross-references the suite-wide ADRs that live in `package-tools`.

## Suite-wide ADRs (canonical in `laranail/package-tools/docs/adr/`)

| ID | Title | Applies to package-scaffolder |
|---|---|---|
| 0001 | [Five packages, not one](https://github.com/laranail/package-tools/blob/main/docs/adr/0001-five-packages.md) | Yes |
| 0002 | [Polyrepo, no monorepo](https://github.com/laranail/package-tools/blob/main/docs/adr/0002-polyrepo.md) | Yes |
| 0003 | [Targets PHP 8.3+ / Laravel 13+](https://github.com/laranail/package-tools/blob/main/docs/adr/0003-php-83-laravel-13.md) | Yes |
| 0005 | [Fluent return: `$this` chain, `static` terminal](https://github.com/laranail/package-tools/blob/main/docs/adr/0005-fluent-return-convention.md) | Yes |
| 0007 | [Tooling pure PHP/Composer; bash banished](https://github.com/laranail/package-tools/blob/main/docs/adr/0007-bash-banished.md) | Yes (CI gate enforces) |
| 0008 | [Plans live in `.plans/`](https://github.com/laranail/package-tools/blob/main/docs/adr/0008-plans-in-dot-plans.md) | Yes (`.plans/CLEANUP-MASTER-PLAN.md` is canonical) |

## Package-specific ADRs (this repo)

_None yet. Number from `0100` upward._

## Lifecycle

ADRs move through `proposed → accepted → superseded`. Once **accepted**,
an ADR is never edited; if the decision changes, a new ADR supersedes
the old one. See [adr.github.io](https://adr.github.io/) for the
full lifecycle and Michael Nygard's template.
