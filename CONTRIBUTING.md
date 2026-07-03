# CONTRIBUTING

Contributions are welcome, and are accepted via pull requests.
Please review these guidelines before submitting any pull requests.

## Process

1. Fork the project
1. Create a new branch
1. Code, test, commit and push
1. Open a pull request detailing your changes.

## Guidelines

* Please ensure the coding style running `composer pcf`.
* * Pull requests should be accompanied by passing tests.
* Please remember ensure you commit to the correct major version, IE v11 for Laravel 11.

## Setup

Clone your fork, then install the dev dependencies:
```bash
composer install
```
## PHP CS Fixer

Run php-cs-fixer:
```bash
composer pcf
```
## Tests

Run all tests:
```bash
composer test
```

Check coverage:
```bash
composer test-coverage
```

## laranail conventions

This package follows the [laranail conventions](https://github.com/laranail) with two recorded
deviations:

- **Test framework: PHPUnit** (not Pest). Deliberate — the suite is large and the value of a rewrite is
  low. `composer test` / `composer lint` (Pint + PHPStan) are the gate.
- **Rector** is in the gate (`composer lint` runs Pint + PHPStan + Rector; CI runs a Rector dry-run). It
  applies the code-quality, dead-code, early-return and mechanical PHP-8.x sets. `SetList::TYPE_DECLARATION`
  is **intentionally omitted** — bulk param/return/property typing on the module engine's interfaces and
  base classes breaks the suite (the types don't line up across implementers), so it's a coordinated
  typing project tracked separately, not a mechanical pass. A few individual rules are skipped in
  `rector.php` with reasons.
