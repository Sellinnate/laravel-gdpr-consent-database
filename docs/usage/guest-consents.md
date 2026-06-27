---
title: "Guest Consents"
description: "Track consent for anonymous (not-logged-in) visitors."
---

# Guest Consents

Anonymous visitors don't have a model to attach consents to. The package tracks them by a **session id**
(the *technical cookie code*) using the `GuestConsentManager` service.

## The manager

```php
use Selli\LaravelGdprConsentDatabase\Services\GuestConsentManager;

$guests = app(GuestConsentManager::class); // resolved from the container
```

Every method accepts an optional `$sessionId`. When omitted, the current session id is used (and persisted
in a `gdpr_session_id` cookie for 30 days).

## Recording guest consent

```php
// Give consent for the current session
$guests->giveConsent('marketing-emails', ['source' => 'cookie_banner']);

// Give consent for a specific technical cookie code
$guests->giveConsent('marketing-emails', [], null, 'gdpr_abc123');

// Check
$guests->hasConsent('marketing-emails');             // current session
$guests->hasConsent('marketing-emails', 'gdpr_abc123');

// Revoke
$guests->revokeConsent('marketing-emails');
```

## Required consents for guests

```php
$guests->hasAllRequiredConsents();      // bool
$guests->getMissingRequiredConsents();  // Collection<ConsentType>
$guests->getActiveConsents();           // Collection<UserConsent>
```

## How it works under the hood

A `guest_consents` row is created per session id and the actual consents are stored in `user_consents`
through the same polymorphic relationship used for authenticated users. Guests therefore share the full
consent feature set (versioning, expiration, audit trail).

::: callout tip "Migrating guest consent on login"
When a guest logs in, you can transfer their cookie-based choices to their account by reading the guest's
active consents and replaying them with `$user->giveConsent(...)`.
:::

## Erasure for guests

Guests are anonymisable too — and the erasure correctly scrubs the `guest_consents` row itself:

```php
$guest = $guests->getGuestConsent('gdpr_abc123');
$guest->anonymizeConsents();
```

See [Data Subject Rights](/compliance/data-subject-rights).
