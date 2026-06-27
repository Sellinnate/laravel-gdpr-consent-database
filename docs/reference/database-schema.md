---
title: "Database Schema"
description: "Tables created by the package."
type: reference
---

# Database Schema

The package creates four tables.

## `consent_types`

The catalogue of things users can consent to. Multiple rows can share a `slug` (one per version).

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `name` | string | |
| `slug` | string | Stable group identifier (indexed, **not** unique alone) |
| `description` | text? | |
| `required` | bool | |
| `active` | bool | One active row per slug = current version |
| `category` | string | `cookie` / `other` |
| `legal_basis` | string? | Art. 30 |
| `purpose` | text? | Art. 30 |
| `data_controller` | string? | Art. 30 |
| `policy_url` | string? | Policy shown for this version |
| `policy_text_hash` | string? | Hash of the policy text |
| `version` | string | `MAJOR.MINOR` |
| `validity_months` | int? | Auto-expiry |
| `effective_from` / `effective_until` | datetime? | Effective window |
| `metadata` | json? | |
| `timestamps` | | |

Indexes: `unique(slug, version)`, `index(slug, active)`.

## `user_consents`

The current-state consent records, attached polymorphically to any subject.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `consentable_type` / `consentable_id` | string | Polymorphic subject |
| `consent_type_id` | bigint | FK → `consent_types` (cascade on delete) |
| `consent_version` | string? | Version at grant time |
| `granted` | bool | |
| `granted_at` / `revoked_at` / `expires_at` | timestamp? | |
| `ip_address` / `user_agent` | string? | Audit context |
| `metadata` | json? | |
| `timestamps` | | |

## `guest_consents`

Anonymous visitor records, keyed by session id (technical cookie code).

| Column | Type | Notes |
|---|---|---|
| `session_id` | string | PK |
| `ip_address` / `user_agent` | string? | |
| `metadata` | json? | |
| `timestamps` | | |

## `consent_audit_logs`

The append-only, immutable audit trail. See [Audit Trail](/concepts/audit-trail).

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `consentable_type` / `consentable_id` | string | Polymorphic subject |
| `consent_type_id` | bigint? | FK → `consent_types` (**nullOnDelete** — proof survives) |
| `consent_type_slug` | string? | Snapshot |
| `consent_version` | string? | Snapshot |
| `action` | string | `granted` / `revoked` / `renewed` / `anonymized` |
| `occurred_at` | timestamp | |
| `ip_address` / `user_agent` | string? | |
| `policy_url` / `policy_text_hash` | string? | Snapshot of what was shown |
| `metadata` | json? | |
| `created_at` | timestamp | (no `updated_at` — records are immutable) |

Indexes: `(consentable_type, consentable_id)`, `(consent_type_id, action)`.
