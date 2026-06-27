---
title: "Expiration & Renewal"
description: "Time-limited consents and periodic re-consent."
type: guide
---

# Expiration & Renewal

Some consents should not last forever. The package lets you set validity periods so consent is re-collected
periodically — good practice for marketing/analytics consents.

::: callout note "Consent never expires unless you opt in"
By **default** consents do not expire (`validity_months` is null). You decide the cadence per consent type,
and you [schedule the expire command](#step-3-schedule-the-cleanup) to close expired records. A common
choice for marketing consent is **6–13 months**; confirm the right period with your DPO.
:::

## Step 1 — Set a validity period

Set `validity_months` on the consent type; consents then receive an `expires_at` automatically.

```php
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;

ConsentType::create([
    'name' => 'Marketing Emails',
    'slug' => 'marketing-emails',
    'required' => false,
    'active' => true,
    'category' => 'other',
    'version' => '1.0',
    'validity_months' => 12, // valid for 12 months
]);

$user = auth()->user();
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

## Step 3 — Schedule the cleanup

An expired consent is *already* treated as inactive by `hasConsent()`/`activeConsents()`. To also **close**
it formally — mark it ungranted, write an `expired` [audit entry](/concepts/audit-trail) and dispatch the
`ConsentExpired` [event](/concepts/events) (e.g. to trigger a re-consent email) — run the expire command on a
schedule:

```bash
php artisan gdpr:consents:expire
```

Schedule it daily. On Laravel 11+ (`routes/console.php`):

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('gdpr:consents:expire')->daily();
```

Or on Laravel 10 (`app/Console/Kernel.php`):

```php
$schedule->command('gdpr:consents:expire')->daily();
```

The command is idempotent — already-closed consents are skipped — so running it more often is harmless.
