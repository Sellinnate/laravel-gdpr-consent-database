---
title: "Installation"
description: "Install and set up the Laravel GDPR Consent Manager package."
---

# Installation

This page walks you through installing the package from scratch. No prior knowledge of the package is
assumed.

## 1. Require the package

Install via Composer:

```bash
composer require selli/laravel-gdpr-consent-database
```

The package uses Laravel's **auto-discovery**, so the service provider is registered automatically. There is
nothing to add to `config/app.php`.

## 2. Publish & run the migrations

The package ships database migrations for the consent tables. Publish them, then migrate:

```bash
php artisan vendor:publish --tag="gdpr-consent-database-migrations"
php artisan migrate
```

This creates three tables: `consent_types`, `user_consents` and `guest_consents`
(see **[Core Concepts](/getting-started/concepts)** for what each one stores).

## 3. Publish the configuration (optional)

To customise texts, colours and the cookie banner, publish the config file:

```bash
php artisan vendor:publish --tag="gdpr-consent-database-config"
```

This creates `config/gdpr-consent-database.php`. See **[Configuration](/configuration/overview)** for every
available option.

## 4. Publish the views (optional)

If you want to fully customise the cookie banner markup:

```bash
php artisan vendor:publish --tag="gdpr-consent-database-views"
```

The banner view is published to `resources/views/vendor/gdpr-consent-database/cookie-banner.blade.php`.

## 5. Add the trait to your model

Add the `HasGdprConsents` trait to any model that should hold consents — typically your `User`:

```php
use Selli\LaravelGdprConsentDatabase\Traits\HasGdprConsents;

class User extends Authenticatable
{
    use HasGdprConsents;

    // ... rest of your model
}
```

::: callout success "Done!"
You are ready to go. Continue with the **[Quick Start](/getting-started/quick-start)** to create your first
consent type and record a consent.
:::

## Verifying the install

You can confirm everything is wired up with Tinker:

```bash
php artisan tinker
```

```php
Selli\LaravelGdprConsentDatabase\Models\ConsentType::count(); // 0 (no error = success)
```
