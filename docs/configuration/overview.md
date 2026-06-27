---
title: "Configuration Overview"
description: "Every configuration option for the cookie banner and package behaviour."
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
| `middleware` | `['web']` | Middleware applied to the group (keep `web` for session + CSRF) |

```php
'routes' => [
    'enabled' => true,
    'prefix' => 'gdpr/consent',
    'name' => 'gdpr.consent.',
    'middleware' => ['web'],
],
```

Set `enabled` to `false` if you want to register your own routes pointing at
`Selli\LaravelGdprConsentDatabase\Http\Controllers\GuestConsentController`.

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

::: callout note "More options coming"
Configuration for model classes, table names, routes and middleware is being added as part of the
enterprise roadmap. This page is kept in sync with every release.
:::
