---
title: "Configuration Overview"
description: "Every configuration option for the cookie banner and package behaviour."
type: reference
---

# Configuration Overview

Publish the config file first:

```bash
php artisan vendor:publish --tag="gdpr-consent-database-config"
```

This creates `config/gdpr-consent-database.php`.

## `routes` — endpoint registration

Control the guest cookie-consent endpoints:

| Key | Default | Description |
|---|---|---|
| `enabled` | `true` | Register the package routes at all |
| `prefix` | `gdpr/consent` | URL prefix for the endpoints |
| `name` | `gdpr.consent.` | Route name prefix |
| `middleware` | `['web', 'throttle:60,1']` | Middleware applied to the group: `web` for session + CSRF, `throttle` to rate-limit the public endpoints |

```php
'routes' => [
    'enabled' => true,
    'prefix' => 'gdpr/consent',
    'name' => 'gdpr.consent.',
    'middleware' => ['web', 'throttle:60,1'],
],
```

::: callout warning "Keep the throttle"
The consent endpoints are public. The default middleware includes `throttle:60,1` (rate limiting). If you
override `middleware`, keep `throttle` (and `web`) — dropping them removes that protection.
:::

Set `enabled` to `false` if you want to register your own routes pointing at
`Selli\LaravelGdprConsentDatabase\Http\Controllers\GuestConsentController`. When routes are disabled, remove
the `@gdprCookieBanner` directive (it degrades gracefully but its endpoints will not exist).

## `privacy` — IP address & user-agent handling

IP address and user agent are personal data. Control how they are stored:

| Key | Default | Description |
|---|---|---|
| `store_ip_address` | `true` | Store the IP at all |
| `anonymize_ip` | `false` | Store a masked IP (IPv4 last octet zeroed, IPv6 last 80 bits / `/48` kept) |
| `store_user_agent` | `true` | Store the user agent at all |

```php
'privacy' => [
    'store_ip_address' => true,
    'anonymize_ip' => false,
    'store_user_agent' => true,
],
```

This applies everywhere the package stores these fields: user consents, guest consents and the audit trail.
Example masked IPs: `203.0.113.42 → 203.0.113.0`, `2001:db8:1234:… → 2001:db8:1234::`.

::: callout note "A deliberate trade-off (Art. 5(1)(c) vs Art. 7(1))"
The full IP is stored **by default** because it strengthens your proof of *where* consent was given
(Art. 7(1)). If your DPIA favours data minimisation (Art. 5(1)(c) / privacy-by-default, Art. 25), enable
`anonymize_ip`, set `store_ip_address` to `false`, and/or set `store_user_agent` to `false`. There is no
single "correct" setting — it is a documented choice you make. See
[IP & data minimisation](/concepts/data-and-privacy).
:::

## Cookie banner options

The remaining options control the **cookie banner** appearance and copy.

## `text` — banner copy

Every label shown in the banner can be overridden:

| Key | Default | Description |
|---|---|---|
| `title` | `Cookie Consent` | Banner heading |
| `message` | *(long text)* | Intro paragraph (supports HTML) |
| `accept_text` | `Accept All` | Accept-all button |
| `reject_text` | `Reject All` | Reject-all button |
| `details_text` | `Cookie Details` | Open details button |
| `back_text` | `Back` | Back button in details |
| `save_text` | `Save Preferences` | Save preferences button |
| `icon_text` | `Cookie Settings` | Floating settings icon label |
| `details_header` | `Cookie Categories` | Details section heading |
| `required_text` | `(Required)` | Suffix shown on required items |

## `colors` — banner theme

| Key | Default |
|---|---|
| `banner_background` | `#fff` |
| `banner_border` | `#ddd` |
| `banner_shadow` | `rgba(0,0,0,0.1)` |
| `text_primary` | `#333` |
| `text_secondary` | `#666` |
| `button_primary_bg` | `#007cba` |
| `button_primary_hover` | `#005a87` |
| `button_secondary_bg` | `#f1f1f1` |
| `button_secondary_hover` | `#e1e1e1` |
| `details_border` | `#eee` |

## `icon` — floating settings icon

| Key | Default | Notes |
|---|---|---|
| `position` | `right` | `right`, `left`, `top`, `bottom` |
| `display` | `icon-with-text` | `icon-only` or `icon-with-text` |
| `background` | `#007cba` | |
| `background_hover` | `#005a87` | |

## Overriding per-invocation

Any text option can also be passed directly to the Blade directive, taking precedence over the config:

```blade
@gdprCookieBanner([
    'title'   => 'Cookie Preferences',
    'message' => 'We use cookies to improve your experience.',
])
```

::: callout note "Not yet configurable"
The routes (prefix / name / middleware) and privacy options above are fully configurable. **Custom model
classes and table names are not** — if you need to swap a model, extend it and rebind it in the container.
This page is kept in sync with every release.
:::
