---
title: "Versioning"
description: "Handle policy changes with consent type versions."
---

# Versioning

When a policy changes, GDPR requires you to obtain **fresh consent**. This package versions consent types so
you can detect exactly who is still on an outdated version.

## The stable-slug model

The `slug` is a **stable group identifier**. A consent type "group" is all the rows sharing a slug; each row
is one version. At any time **exactly one** row per slug is `active` — that is the current version.

```
slug = "privacy-policy"
├── version 1.0  (active = false)   ← superseded
└── version 1.1  (active = true)    ← current
```

::: callout info "Your code always uses the stable slug"
You never reference `privacy-policy-v1-1`. You always use `privacy-policy`, and the package resolves it to
the current version automatically.
:::

## Publishing a new version

```php
$type = ConsentType::where('slug', 'privacy-policy')->where('active', true)->first();

$newVersion = $type->createNewVersion([
    'description' => 'Updated privacy policy',
    'policy_url'  => 'https://example.com/privacy/v2',
]);

$newVersion->version; // "1.1"
$newVersion->slug;    // "privacy-policy" (unchanged)
```

`createNewVersion()` runs in a transaction and:

1. deactivates the currently active version(s) of the group;
2. creates a new active row with the same slug and an incremented version;
3. returns the new version.

## Detecting who must re-consent

```php
// Has the user consented to ANY version?
$user->hasConsent('privacy-policy');                 // true (still holds v1.0)

// Has the user consented to the CURRENT version?
$user->hasConsent('privacy-policy', checkVersion: true); // false → must re-consent

// All required consents on their current versions?
$user->hasAllRequiredConsents(checkVersion: true);   // false

// Which of the user's active consents are outdated/expired?
$user->consentsNeedingRenewal();                     // Collection<UserConsent>
```

## Renewing

```php
// Re-consent to the current version (preserves previous metadata unless you pass new metadata)
$renewed = $user->renewConsent('privacy-policy');
$renewed->consent_version; // "1.1"
```

`renewConsent()` supersedes the old consent and records a fresh consent (and audit entries) for the current
version. It returns `null` if the type is not currently effective.

## Version numbering

`createNewVersion()` computes the next version from the **highest** existing version in the group, so it is
safe to call from any version and never collides:

| Existing versions | Next |
|---|---|
| `1.0` | `1.1` |
| `1.0`, `1.1` | `1.2` |
| `2.4` | `2.5` |

You can also pass an explicit `version` in the override array if you manage versions yourself.
