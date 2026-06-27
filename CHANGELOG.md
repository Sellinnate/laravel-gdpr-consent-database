# Changelog

All notable changes to `laravel-gdpr-consent-database` will be documented in this file.

## v2.1.0 - 2026-06-27

Hardening from an independent GDPR-auditor review, plus a much richer documentation site. Backward
compatible (one erasure-behaviour improvement).

### Added
- **User-agent minimisation**: new `privacy.store_user_agent` config option (Art. 5(1)(c)).
- **Fuller subject export (Art. 15/20)**: `ConsentExporter` now includes the consent type's `purpose`,
  `legal_basis` and `data_controller`, and discloses the `guest_consents` row for guest subjects.
- Comprehensive docs site: a "What is GDPR consent? (start here)" page, deep **Concepts** pages explaining
  the GDPR reasoning behind every feature, **Guides**, and an honest **Scope & limitations** page.

### Changed / Fixed
- **Stronger erasure (Art. 17)**: anonymisation now rotates the `guest_consents` primary key
  (`session_id`) to the pseudonym, removing a residual identifier.
- Privacy config documents the IP-storage trade-off (proof vs minimisation); README softened to drop the
  "provably compliant" over-claim and link the limitations page.
- **CI**: fixed PHPStan failure on `Command::argument()` typing (version-robust trait) and the test matrix
  failing because Composer 2.10 blocks advisory-affected Laravel 11.x.

## v2.0.0 - 2026-06-27

🚀 Major enterprise release. **Breaking changes** — see [UPGRADE.md](UPGRADE.md).

### Added

- **Immutable audit trail** (`consent_audit_logs`) recording every consent action — proof of consent for
  GDPR Art. 7(1), with policy version/URL/hash snapshot.
- **Right to erasure** via `anonymizeConsents()` and the `gdpr:anonymize-subject` command — pseudonymises a
  subject while preserving the audit proof (Art. 17).
- **Access / portability** via `ConsentExporter` and the `gdpr:consents:export` command (Art. 15 / 20).
- **Domain events**: `ConsentGranted`, `ConsentRevoked`, `ConsentRenewed`, `ConsentExpired`.
- **`gdpr:consents:expire`** command to close expired consents.
- **GDPR Art. 30 fields** on consent types: `legal_basis`, `purpose`, `data_controller`.
- **Configurable IP anonymization** (`privacy.store_ip_address`, `privacy.anonymize_ip`).
- **Configurable routes** (`routes.enabled` / `prefix` / `name` / `middleware`).
- **`Consentable`** contract; full static analysis (PHPStan max), mutation testing, and a documentation site.

### Changed

- **Versioning redesign**: the `slug` is now a stable group identifier (one row per version,
  `unique(slug, version)`); consent operations are group-aware; no more `LIKE`-based resolution.
- Consent operations are transactional and enforce a single active consent per group.
- The cookie banner no longer queries the database from the view (ViewComposer); resolves endpoint URLs from
  config; ships accessibility (ARIA / keyboard / focus) improvements.
- `Reject All` now revokes optional cookie consents.

### Fixed

- Removed test-environment-conditional logic from production code.
- Robust version parsing; correct expiry-window filtering; honest, behaviour-asserting test suite.

## v1.0.4 - 2025-06-15

Minor fixes to Cookie Banner. It now integrate links to Cookie Policy and Privacy Policy

## v1.0.3 - 2025-06-14

Compatibility with MySql, PostgreSQL, sqlite

## v1.0.1 - 2025-06-14

This release fixes the database migration issue and the readme.md file instructions

## v1.0.0 - 2025-06-14

🎉 First stable release of `laravel-gdpr-consent-database`

This package provides a simple and customizable solution for storing and managing user consent records in compliance with GDPR regulations, using Laravel's Eloquent ORM and migrations.

### ✅ Features

- Store GDPR consent records in the database
- Associate consents with users or anonymous sessions
- Track timestamp and purpose of each consent
- Artisan command for cleanup of expired consents
- Middleware example for user consent enforcement
- Compatible with Laravel 11+
