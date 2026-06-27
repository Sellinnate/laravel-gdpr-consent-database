---
title: "Core Concepts"
description: "The data model and terminology behind the package."
---

# Core Concepts

Understanding three entities is enough to use the package effectively.

## The three tables

::: card "consent_types — what users can consent to"
A catalogue of consent definitions. Each row is a thing a user may agree to: Terms, Marketing, a cookie
category, etc.

Key columns: `name`, `slug` (stable identifier), `description`, `required`, `active`, `category`
(`cookie` / `other`), `version`, `validity_months`, `effective_from`, `effective_until`, `metadata`.
:::

::: card "user_consents — the actual consent records"
One row per consent action, attached **polymorphically** to any model via `consentable_type` /
`consentable_id`. Stores `granted`, `granted_at`, `revoked_at`, `expires_at`, `consent_version` and audit
fields `ip_address`, `user_agent`, `metadata`.
:::

::: card "guest_consents — anonymous visitors"
Tracks consent for users who are not logged in, keyed by a `session_id` (the *technical cookie code*). Guest
consents reuse the same `user_consents` storage through the polymorphic relationship.
:::

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

When a policy changes you create a **new version** of a consent type. Existing consents remain tied to the
version they were granted under, so you can detect exactly who needs to re-consent. See the dedicated
versioning guide (coming up in the docs) for the full workflow.

## Expiration (at a glance)

A consent type can declare `validity_months`. Consents then carry an `expires_at` timestamp and are treated
as inactive once expired — supporting periodic re-consent requirements.

## Guest vs authenticated

| | Authenticated | Guest |
|---|---|---|
| Identified by | model (`User`, …) | `session_id` / technical cookie code |
| API entry point | `HasGdprConsents` trait on the model | `GuestConsentManager` service |
| Typical trigger | forms, preference centre | cookie banner |
