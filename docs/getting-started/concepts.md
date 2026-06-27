---
title: "Core Concepts"
description: "The data model and terminology behind the package."
---

# Core Concepts

Understanding three entities is enough to use the package effectively.

## The four tables

::: card "consent_types — what users can consent to"
A catalogue of consent definitions. Each row is a thing a user may agree to: Terms, Marketing, a cookie
category, etc. Multiple rows can share a `slug` — one per [version](/usage/versioning).
:::

::: card "user_consents — the current-state consent records"
One row per active/superseded consent, attached **polymorphically** to any model via `consentable_type` /
`consentable_id`. Stores `granted`, `granted_at`, `revoked_at`, `expires_at`, `consent_version` and context
fields.
:::

::: card "guest_consents — anonymous visitors"
Tracks consent for users who are not logged in, keyed by a `session_id` (the *technical cookie code*). Guest
consents reuse the same `user_consents` storage through the polymorphic relationship.
:::

::: card "consent_audit_logs — the immutable proof"
An append-only trail of every consent action (granted / revoked / renewed / anonymized), capturing the exact
version and policy shown. This is your GDPR Art. 7(1) evidence. See [Audit Trail](/compliance/audit-trail).
:::

See the full [Database Schema](/reference/database-schema) for every column.

## Polymorphic consents

Because `user_consents` is polymorphic, **any** model can hold consents — not just `User`. Add the
`HasGdprConsents` trait and the model gains the full consent API:

```php
class TeamMember extends Model
{
    use Selli\LaravelGdprConsentDatabase\Traits\HasGdprConsents;
}
```

## Required vs optional

- **Required** consent types must be granted for the user to be considered compliant
  (`hasAllRequiredConsents()`).
- **Optional** consent types are opt-in and never block the user.

## Versioning (at a glance)

The `slug` is a **stable group key**. When a policy changes you publish a new version (same slug, bumped
`version`); existing consents stay tied to the version they were granted under, so you can detect exactly who
needs to re-consent. See [Versioning](/usage/versioning) for the full workflow.

## Expiration (at a glance)

A consent type can declare `validity_months`. Consents then carry an `expires_at` timestamp and are treated
as inactive once expired — supporting periodic re-consent. See [Expiration & Renewal](/usage/expiration).

## Auditability (at a glance)

Every action is written to an immutable audit log, capturing the version and policy the subject agreed to —
your proof of consent. See [Audit Trail](/compliance/audit-trail).

## Guest vs authenticated

| | Authenticated | Guest |
|---|---|---|
| Identified by | model (`User`, …) | `session_id` / technical cookie code |
| API entry point | `HasGdprConsents` trait on the model | `GuestConsentManager` service |
| Typical trigger | forms, preference centre | cookie banner |
