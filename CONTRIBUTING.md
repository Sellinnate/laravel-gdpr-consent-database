# Contributing

Thank you for considering contributing to **Laravel GDPR Consent Database**! This document explains how
to set up your environment and the quality bar every contribution must meet.

## Local setup

```bash
git clone https://github.com/sellinnate/laravel-gdpr-consent-database.git
cd laravel-gdpr-consent-database
composer install
```

## Quality gates

Every pull request must pass the full quality pipeline locally before being opened:

| Command | Purpose | Requirement |
|---|---|---|
| `composer test` | Pest test suite | All green |
| `composer test-coverage` | Code coverage | ≥ 90% (target) |
| `composer analyse` | PHPStan (level max + Larastan) | No errors |
| `composer format` | Laravel Pint code style | No diff |
| `composer mutate` | Mutation testing (Pest) | MSI ≥ 85% (informational in CI) |

> **Coverage driver:** coverage requires `pcov` or `xdebug`. With Xdebug installed but not loaded you can run:
> `XDEBUG_MODE=coverage vendor/bin/pest --coverage`.

## Conventions

- **PHP 8.2+**, `declare(strict_types=1)` in every `src/` file.
- **English only** for code, comments, PHPDoc and user-facing documentation.
- **TDD for bug fixes:** add a regression test that fails on the current code *before* fixing it.
- **No environment-conditional logic** (`app()->environment(...)`) inside domain code.
- **No tautological assertions** (`expect($x)->toBe($x)`); every test must assert real behaviour.
- The PHPStan baseline (`phpstan-baseline.neon`) only ever shrinks — never add new entries.

## Commit & PR

- Keep commits focused and descriptive.
- Reference the related roadmap item / issue when relevant.
- Update both documentation fronts when behaviour changes: the public docs site (`docs/`) and the README.

## Reporting security issues

Please do not open public issues for security vulnerabilities. See [SECURITY.md](SECURITY.md).
