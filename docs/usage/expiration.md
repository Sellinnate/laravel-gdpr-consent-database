---
title: "Expiration & Renewal"
description: "Time-limited consents and periodic re-consent."
---

# Expiration & Renewal

Some consents should not last forever. The package supports validity periods and automatic expiry.

## Setting a validity period

Set `validity_months` on the consent type; consents then receive an `expires_at` automatically.

```php
ConsentType::create([
    'name' => 'Marketing Emails',
    'slug' => 'marketing-emails',
    'required' => false,
    'active' => true,
    'category' => 'other',
    'version' => '1.0',
    'validity_months' => 12, // valid for 12 months
]);

$user->giveConsent('marketing-emails');             // expires in 12 months
$user->giveConsent('marketing-emails', [], 6);      // override: expires in 6 months
```

## How expiry affects checks

An expired consent is no longer **active**: `hasConsent()` returns `false` and it drops out of
`activeConsents()`.

```php
use Carbon\Carbon;

$user->giveConsent('marketing-emails'); // 12-month validity
$user->hasConsent('marketing-emails');  // true

Carbon::setTestNow(now()->addMonths(13));
$user->hasConsent('marketing-emails');  // false (expired)
```

## Finding consents that are about to expire

Ideal for sending re-consent reminders:

```php
// Active consents expiring within the next 30 days
$user->getConsentsExpiringWithinDays(30);

// On a single consent
$consent->daysUntilExpiration(); // int days remaining (rounds up), 0 if expired, null if no expiry
$consent->isExpired();           // bool
```

## Renewing an expired consent

```php
$user->renewConsent('marketing-emails');
```

This supersedes the expired consent with a fresh one for the current version, resetting `expires_at`.

## Inspecting expired consents

```php
$user->expiredConsents();        // Collection<UserConsent>
$user->consentsNeedingRenewal(); // expired OR outdated-version consents
```
