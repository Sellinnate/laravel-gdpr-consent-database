---
title: "Artisan Commands"
description: "Console commands shipped with the package."
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

## Seeder: default cookie types

Not a command, but commonly run during setup:

```bash
php artisan db:seed --class="Selli\LaravelGdprConsentDatabase\Database\Seeders\CookieConsentSeeder"
```

Creates `technical-cookies`, `profiling-cookies` and `tracking-cookies` consent types in the `cookie`
category.
