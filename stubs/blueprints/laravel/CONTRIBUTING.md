# Contributing

Contributions are welcome and appreciated.

## Getting started

```bash
composer install
composer test        # PHPUnit (Orchestra Testbench, in-memory SQLite)
composer analyse     # PHPStan (level 5 + larastan)
composer format      # Laravel Pint
composer refactor    # Rector (dry run: vendor/bin/rector --dry-run)
```

## Guidelines

- Follow the existing architecture: HTTP/CLI layers stay thin and delegate to
  Actions and the `Blog` manager; persistence goes through the repository.
- Every write path must have a Form Request with a real `authorize()` and strict rules.
- Add or update tests for any behavioural change — the suite must stay green.
- Run Pint before committing; CI enforces style and static analysis.

## Reporting bugs

Open an issue with a minimal reproduction. For security issues, see
[SECURITY.md](SECURITY.md) instead of the public tracker.
