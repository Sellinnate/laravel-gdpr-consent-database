---
title: "User Consents"
description: "Grant, check and revoke consent on any model."
---

# User Consents

Add the `HasGdprConsents` trait to a model and it gains the full consent API. Although it is usually the
`User`, **any** Eloquent model can hold consents.

```php
use Selli\LaravelGdprConsentDatabase\Traits\HasGdprConsents;

class User extends Authenticatable
{
    use HasGdprConsents;
}
```

## Granting consent

```php
// By slug (recommended) or by consent type id
$user->giveConsent('marketing-emails');

// With metadata captured alongside the consent
$user->giveConsent('marketing-emails', ['source' => 'registration_form']);

// Override the validity period (months)
$user->giveConsent('marketing-emails', [], 6);
```

`giveConsent()`:

- resolves the slug to the **current active version** of the consent type;
- **supersedes** any existing active consent for that group (you always have exactly one active consent
  per type);
- captures `ip_address`, `user_agent`, the `consent_version` and the timestamp;
- writes an immutable [audit-trail](/compliance/audit-trail) entry;
- runs in a database transaction.

::: callout warning "Retired purposes are rejected"
If a consent type has no currently effective (active) version, `giveConsent()` throws
`ModelNotFoundException`. You cannot record consent for a purpose you no longer offer.
:::

## Checking consent

```php
if ($user->hasConsent('marketing-emails')) {
    // user has an active consent for any version of this type
}

// Require the consent to be on the CURRENT version
if ($user->hasConsent('privacy-policy', checkVersion: true)) {
    // ...
}
```

## Revoking consent

```php
$user->revokeConsent('marketing-emails');
```

Revocation marks the active consent(s) as revoked and writes a `revoked` audit entry. It never deletes
data. Revoking a non-existent slug is a safe no-op (returns `0`).

## Working with collections

```php
$user->activeConsents();        // Collection<UserConsent> — granted, not revoked, not expired
$user->expiredConsents();       // Collection<UserConsent> — past their expiry date
$user->consentsNeedingRenewal();// Collection<UserConsent> — expired or on an outdated version
```

## Required consents

```php
if (! $user->hasAllRequiredConsents()) {
    $missing = $user->getMissingRequiredConsents(); // Collection<ConsentType>
    // redirect the user to a consent screen listing $missing
}

// Version-aware variants
$user->hasAllRequiredConsents(checkVersion: true);
$user->getMissingRequiredConsents(checkVersion: true);
```

## The audit trail

Every action is recorded immutably:

```php
$user->consentAuditLogs(); // Collection<ConsentAuditLog>, most recent first
```

See [Audit Trail](/compliance/audit-trail) for the full picture.

## Method reference

| Method | Returns | Description |
|---|---|---|
| `giveConsent($type, $metadata = [], $validityMonths = null)` | `UserConsent` | Grant / supersede |
| `revokeConsent($type)` | `int` | Number of consents revoked |
| `renewConsent($type, $metadata = [])` | `?UserConsent` | Re-consent to the current version |
| `hasConsent($type, $checkVersion = false)` | `bool` | Active consent exists |
| `activeConsents()` | `Collection` | Active consents |
| `expiredConsents()` | `Collection` | Expired consents |
| `consentsNeedingRenewal()` | `Collection` | Outdated/expired consents |
| `getMissingRequiredConsents($checkVersion = false)` | `Collection` | Missing required types |
| `hasAllRequiredConsents($checkVersion = false)` | `bool` | All required satisfied |
| `getConsentsExpiringWithinDays($days = 30)` | `Collection` | Soon-to-expire consents |
| `consentAuditLogs()` | `MorphMany` | Immutable audit trail |
| `anonymizeConsents($token = null)` | `array` | Erasure (see [Data Subject Rights](/compliance/data-subject-rights)) |
