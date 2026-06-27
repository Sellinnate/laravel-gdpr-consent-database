---
title: "Consent Types"
description: "Define what users can consent to."
type: concept
---

# Consent Types

A **consent type** is a row in the `consent_types` table describing one thing a user can agree to:
Terms & Conditions, a Privacy Policy, a cookie category, a newsletter, etc.

## Creating a consent type

```php
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;

ConsentType::create([
    'name'        => 'Marketing Emails',
    'slug'        => 'marketing-emails',   // stable identifier, used everywhere in your code
    'description' => 'Consent to receive marketing communications',
    'required'    => false,                // optional consent
    'active'      => true,                 // currently offered
    'category'    => 'other',              // 'cookie' or 'other'
    'version'     => '1.0',
]);
```

## Fields

| Field | Type | Purpose |
|---|---|---|
| `name` | string | Human-readable name |
| `slug` | string | **Stable** identifier — your code references this. Not unique on its own (see [Versioning](/concepts/versioning)) |
| `description` | string? | What the user is agreeing to |
| `required` | bool | Whether the consent is mandatory for compliance |
| `active` | bool | Whether this is the current, offered version |
| `category` | string | `cookie` (shown in the banner) or `other` |
| `version` | string | `MAJOR.MINOR` version string |
| `validity_months` | int? | Auto-expiry period (see [Expiration](/guides/expiration)) |
| `effective_from` / `effective_until` | datetime? | Time window during which the version is effective |
| `legal_basis` | string? | GDPR Art. 30 — e.g. `consent`, `contract`, `legal_obligation` |
| `purpose` | string? | GDPR Art. 30 — the processing purpose |
| `data_controller` | string? | GDPR Art. 30 — the controller entity |
| `policy_url` | string? | URL of the policy text shown for this version |
| `policy_text_hash` | string? | Hash of the exact policy text shown (proof) |
| `metadata` | array? | Free-form extra data |

::: callout tip "Recommended: record the legal basis"
For full GDPR Art. 30 record-keeping, set `legal_basis`, `purpose` and `data_controller`. These are
snapshotted into the [audit trail](/concepts/audit-trail) when consent is given.
:::

## Required vs optional

```php
// Required: blocks hasAllRequiredConsents() until granted
ConsentType::create([
    'name' => 'Terms and Conditions', 'slug' => 'terms',
    'required' => true, 'active' => true, 'category' => 'other',
]);

// Optional: opt-in, never blocks the user
ConsentType::create([
    'name' => 'Newsletter', 'slug' => 'newsletter',
    'required' => false, 'active' => true, 'category' => 'other',
]);
```

## Cookie categories

Consent types with `category = 'cookie'` are the ones rendered by the
[Cookie Banner](/concepts/cookie-banner). Everything else (`category = 'other'`) is managed through your own
forms and preference screens.

```php
ConsentType::cookies(); // collection of active cookie-category consent types
```

## Seeding default cookie types

The package ships a seeder with sensible cookie defaults:

```bash
php artisan db:seed --class="Selli\LaravelGdprConsentDatabase\Database\Seeders\CookieConsentSeeder"
```
