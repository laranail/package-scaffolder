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

This package follows the [laranail conventions](https://github.com/laranail) with one recorded
deviation:

- **Test framework: PHPUnit** (not Pest). Deliberate — the suite is large and the value of a rewrite is
  low. `composer test` / `composer lint` (Pint + PHPStan + Rector) are the gate.

**Rector** is in the gate (`composer lint` runs Pint + PHPStan + Rector; CI runs a Rector dry-run) and
applies the code-quality, dead-code, early-return, mechanical PHP-8.x **and TYPE_DECLARATION** sets. A few
individual rules are skipped in `rector.php` with inline reasons — notably `StrictArrayParamDimFetchRector`
(it mistypes Laravel container closures' `$app` as `array`) and `TypedPropertyFromStrictConstructorRector`
scoped to the two `$app` holders (it narrows an Application to the base `Container`).
