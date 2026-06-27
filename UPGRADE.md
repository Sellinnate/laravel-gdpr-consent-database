# Upgrade Guide

## Upgrading to 2.x (enterprise) from 1.x

Version 2.x is a **major, breaking** release that hardens the package for enterprise GDPR use. Read this
guide before upgrading a production application.

### TL;DR

- The `consent_types.slug` is now a **stable group identifier** and is no longer unique on its own.
- Consent **versioning** no longer mints `slug-v1-1` slugs; the slug stays stable across versions.
- A new **immutable audit trail** (`consent_audit_logs`) records every consent action.
- New **compliance fields** on `consent_types` (legal basis, purpose, data controller, policy snapshot).
- A new **anonymisation** path supports GDPR Art. 17 erasure without destroying proof of consent.

### 1. Database changes

Publish and run the new migrations:

```bash
php artisan vendor:publish --tag="gdpr-consent-database-migrations"
php artisan migrate
```

The following schema changes apply. For an **existing** database you must reconcile them manually
(the package migrations target a fresh install):

| Table | Change |
|---|---|
| `consent_types` | `slug` unique index **dropped**; replaced with a plain index + `unique(slug, version)` and index `(slug, active)` |
| `consent_types` | new nullable columns: `legal_basis`, `purpose`, `data_controller`, `policy_url`, `policy_text_hash` |
| `consent_audit_logs` | **new table** (append-only audit trail) |

Example reconciliation migration for an existing app:

```php
Schema::table('consent_types', function (Blueprint $table) {
    $table->dropUnique(['slug']);
    $table->index('slug');
    $table->unique(['slug', 'version']);
    $table->index(['slug', 'active']);
    $table->string('legal_basis')->nullable();
    $table->text('purpose')->nullable();
    $table->string('data_controller')->nullable();
    $table->string('policy_url')->nullable();
    $table->string('policy_text_hash')->nullable();
});
```

### 2. Versioning behaviour changed

**Before (1.x):** `createNewVersion()` created a new row with a *new* slug (`terms-v1-1`).

**After (2.x):** the slug is stable. `createNewVersion()` deactivates the current active version and inserts
a new active row with the **same slug** and an incremented `version`.

If your code referenced versioned slugs (e.g. `hasConsent('terms-v1-1')`), switch to the **stable slug**
(`hasConsent('terms')`) — it always resolves to the current version, and consent checks match across all
versions of the group.

### 3. Consent records are now group-aware

- `giveConsent('terms')` supersedes **any** active consent for the `terms` group (any version), guaranteeing a
  single active consent per group.
- `giveConsent()` now **throws** `ModelNotFoundException` if the consent type has no currently effective
  (active) version — you can no longer record consent for a retired purpose.

### 4. Audit trail

Every `giveConsent` / `revokeConsent` / `renewConsent` now writes an immutable row to `consent_audit_logs`.
Retrieve a subject's trail via `$model->consentAuditLogs()`. Records cannot be updated or deleted through
Eloquent.

### 5. Erasure (Art. 17)

To honour an erasure request without destroying consent proof:

```php
$user->anonymizeConsents();
// or
php artisan gdpr:anonymize-subject "App\Models\User" 42
```

This pseudonymises the subject and scrubs IP, user agent and metadata, while keeping the action/version/policy
proof under a random token.

### 6. Foreign keys

`consent_audit_logs.consent_type_id` uses `nullOnDelete`, so deleting a consent type preserves the audit
proof. `user_consents.consent_type_id` still cascades on delete (current-state). Ensure foreign key
enforcement is enabled on your connection (default on MySQL/Postgres; add `PRAGMA foreign_keys=ON` for SQLite).
