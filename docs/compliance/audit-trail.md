---
title: "Audit Trail"
description: "The immutable record of every consent action."
---

# Audit Trail

Every consent action — grant, revoke, renew, anonymise — is recorded immutably in the
`consent_audit_logs` table. This is your **proof of consent** for GDPR Art. 7(1).

## What gets recorded

Each entry is a self-contained snapshot:

| Column | Meaning |
|---|---|
| `consentable_type` / `consentable_id` | The subject (user, guest, …) |
| `consent_type_id` | The exact version row (null if the type was later deleted) |
| `consent_type_slug` | The stable slug (kept as proof even after deletion) |
| `consent_version` | The version the subject agreed to |
| `action` | `granted`, `revoked`, `renewed` or `anonymized` |
| `occurred_at` | When the action happened |
| `ip_address` / `user_agent` | Request context |
| `policy_url` / `policy_text_hash` | Snapshot of exactly what was shown |
| `metadata` | Grant context (present on `granted` only) |

## Reading the trail

```php
$user->consentAuditLogs();           // Collection<ConsentAuditLog>, most recent first
$user->consentAuditLogs()->count();

use Selli\LaravelGdprConsentDatabase\Models\ConsentAuditLog;

ConsentAuditLog::where('action', 'granted')->latest('occurred_at')->get();
```

## Immutability

Audit entries cannot be changed or removed through Eloquent:

```php
$log = ConsentAuditLog::first();

$log->action = 'tampered';
$log->save();   // throws RuntimeException

$log->delete(); // throws RuntimeException
```

::: callout note "Application-level guard"
Immutability is enforced in the model (the `updating`/`deleting` events throw). This protects against
accidental mutation by application code. It is **not** a database-level guarantee — the only sanctioned
exception is the erasure path. For stronger tamper-evidence, revoke `UPDATE`/`DELETE` on the table at the
database level, or add a per-row hash chain.
:::

## Surviving consent-type deletion

The `consent_type_id` foreign key uses `nullOnDelete`. If you ever delete a consent type, the audit entries
survive — `consent_type_id` becomes `null`, but the snapshotted `consent_type_slug`, `consent_version` and
`policy_url` remain as proof.

## What an entry looks like

```php
[
    'action'            => 'granted',
    'consent_type_slug' => 'privacy-policy',
    'consent_version'   => '1.1',
    'policy_url'        => 'https://example.com/privacy/v2',
    'policy_text_hash'  => 'sha256:…',
    'occurred_at'       => '2026-06-27 10:00:00',
    'ip_address'        => '203.0.113.4',
    'metadata'          => ['source' => 'signup'],
]
```
