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
- **Rector** is configured (`rector.php`, `composer rector` / `rector-fix`) but a full pass is a **pending
  remediation** (~200 files) and is **not yet in the CI gate**. Run `composer rector` to preview it.
  Prefer `composer pint-fix` for day-to-day style; a Rector cleanup will be its own change.
