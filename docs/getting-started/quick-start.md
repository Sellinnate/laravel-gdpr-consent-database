---
title: "Quick Start"
description: "Create your first consent type and record user consent in minutes."
---

# Quick Start

This guide assumes you have completed the **[Installation](/getting-started/installation)** and added the
`HasGdprConsents` trait to your `User` model.

## Step 1 — Create consent types

A **consent type** describes *what* a user can consent to. Create them once (e.g. in a seeder or migration):

```php
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;

// A required consent (e.g. Terms & Conditions)
ConsentType::create([
    'name'        => 'Terms and Conditions',
    'slug'        => 'terms',
    'description' => 'Agreement to the website terms and conditions',
    'required'    => true,
    'active'      => true,
    'category'    => 'other',
]);

// An optional marketing consent
ConsentType::create([
    'name'        => 'Marketing Emails',
    'slug'        => 'marketing-emails',
    'description' => 'Consent to receive marketing communications',
    'required'    => false,
    'active'      => true,
    'category'    => 'other',
]);
```

::: callout info "category: cookie vs other"
Use `category => 'cookie'` for consents that should appear in the **cookie banner**, and `'other'` for
consents handled elsewhere (registration forms, preference centres, …).
:::

## Step 2 — Record and check consent

Every method is available directly on your model through the trait:

```php
$user = auth()->user();

// Give consent (by slug or by consent type id)
$user->giveConsent('marketing-emails');

// Give consent with extra metadata
$user->giveConsent('marketing-emails', ['source' => 'registration_form']);

// Check consent
if ($user->hasConsent('marketing-emails')) {
    // user opted in
}

// Revoke consent
$user->revokeConsent('marketing-emails');
```

## Step 3 — Enforce required consents

```php
if (! $user->hasAllRequiredConsents()) {
    $missing = $user->getMissingRequiredConsents();
    // redirect the user to a consent screen, listing $missing
}
```

## Step 4 — Show the cookie banner

Add the Blade directive to your layout (typically before `</body>`):

```blade
@gdprCookieBanner
```

Make sure your layout exposes the CSRF token so the banner's AJAX calls work:

```blade
<meta name="csrf-token" content="{{ csrf_token() }}">
```

The banner automatically lists every **active** consent type with `category = 'cookie'` and posts the
visitor's choices to the package's built-in endpoints.

::: callout tip "Next"
Learn the data model and terminology in **[Core Concepts](/getting-started/concepts)**, or customise the
look & feel in **[Configuration](/configuration/overview)**.
:::
