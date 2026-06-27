---
title: "Artisan Commands"
description: "Console commands shipped with the package."
type: reference
---

# Artisan Commands

## `gdpr:anonymize-subject`

Pseudonymise all consent records of a subject for a GDPR Art. 17 erasure request, preserving the audit proof.

```bash
php artisan gdpr:anonymize-subject {type} {id} [--token=]
```

| Argument / Option | Description |
|---|---|
| `type` | The stored consentable type — a morph alias or the fully-qualified class name (e.g. `App\Models\User`) |
| `id` | The subject identifier |
| `--token` | Optional fixed pseudonym; a random one is generated when omitted |

Example:

```bash
php artisan gdpr:anonymize-subject "App\Models\User" 42
```

Output:

```
Subject anonymised.
+--------------------+---------------------+-----------------------+
| Pseudonym          | Consents anonymised | Audit logs anonymised |
+--------------------+---------------------+-----------------------+
| anon_9f8c…         | 3                   | 7                     |
+--------------------+---------------------+-----------------------+
```

See [Data Subject Rights](/compliance/data-subject-rights) for details.

## `gdpr:consents:export`

Export a subject's consents and audit trail as JSON (GDPR Art. 15 / 20).

```bash
php artisan gdpr:consents:export {type} {id} [--path=]
```

| Argument / Option | Description |
|---|---|
| `type` | The consentable type (morph alias or class name) |
| `id` | The subject identifier |
| `--path` | Write the JSON to a file instead of stdout |

```bash
php artisan gdpr:consents:export "App\Models\User" 42 --path=storage/exports/user-42.json
```

## `gdpr:consents:expire`

Close consents that are past their expiry date: marks them ungranted, writes an `expired` audit entry and
dispatches the `ConsentExpired` event. Idempotent — already-closed consents are skipped. Schedule it daily:

```php
// app/Console/Kernel.php (or routes/console.php on Laravel 11+)
$schedule->command('gdpr:consents:expire')->daily();
```

```bash
php artisan gdpr:consents:expire
```

## Events

The package dispatches domain events you can listen to:

| Event | When |
|---|---|
| `ConsentGranted` | A subject grants consent |
| `ConsentRevoked` | A subject explicitly withdraws consent |
| `ConsentRenewed` | A subject renews onto the current version |
| `ConsentExpired` | `gdpr:consents:expire` closes an expired consent |

```php
use Selli\LaravelGdprConsentDatabase\Events\ConsentGranted;

Event::listen(ConsentGranted::class, function (ConsentGranted $event) {
    // $event->consentable, $event->consent
});
```

## Seeder: default cookie types

Not a command, but commonly run during setup:

```bash
php artisan db:seed --class="Selli\LaravelGdprConsentDatabase\Database\Seeders\CookieConsentSeeder"
```

Creates `technical-cookies`, `profiling-cookies` and `tracking-cookies` consent types in the `cookie`
category.
