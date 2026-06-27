---
title: "Data Subject Rights"
description: "Handle access, portability, erasure and withdrawal requests with the shipped ConsentExporter and ConsentAnonymizer services."
type: guide
---

# Data Subject Rights

Under the GDPR a **data subject** (the natural person the data is about) can ask you, the **data controller** (the organisation that decides why and how the data is processed), to act on their personal data. This guide shows the exact, copy-paste way to satisfy four of those rights using only the services this package ships:

- **Right of access** — Art. 15 — "what consent data do you hold about me?"
- **Right to data portability** — Art. 20 — "give it to me in a machine-readable format."
- **Right to erasure** — Art. 17 — the "right to be forgotten."
- **Right to withdraw consent** — Art. 7(3) — "stop relying on my consent, as easily as I gave it."

::: callout note "Two kinds of subject"
This package stores consent for **two** kinds of subject:

- A **host model** — typically your `App\Models\User`, identified by its primary key.
- A **guest** — an unauthenticated visitor identified by a `session_id` (the value of a technical cookie), stored in the `guest_consents` table.

Every example below works for both. See [/concepts/guest-consents](/concepts/guest-consents) for the guest model.
:::

---

## Right of access & portability (Art. 15 / Art. 20)

To answer an access or portability request you produce a single, machine-readable JSON document containing everything the package knows about the subject. Do **not** hand-roll this with `->toArray()` — use the shipped `Selli\LaravelGdprConsentDatabase\Services\ConsentExporter`. It assembles the consents, the audit trail, and (for guests) the guest row in one stable, documented shape.

### The export service

`ConsentExporter` has three public methods:

| Method | Use it when | Returns |
| --- | --- | --- |
| `exportModel(Model $subject)` | You have the subject as an Eloquent model (e.g. a `User`). | `array` |
| `export(string $type, int\|string $id)` | You only have the stored type + id (e.g. a guest `session_id`). | `array` |
| `toJson(string $type, int\|string $id, int $flags = JSON_PRETTY_PRINT)` | You want a ready-to-download JSON string. | `string` |

Resolve the service from the container with `app(ConsentExporter::class)` (or type-hint it and let Laravel inject it).

### The exact returned structure

`export()` / `exportModel()` always return an array with these keys:

- `subject` — `['type' => ..., 'id' => ...]`, the stored polymorphic type and identifier.
- `consents` — a list; **each** consent has exactly these keys:
  `consent_type`, `consent_name`, `purpose`, `legal_basis`, `data_controller`, `consent_version`, `granted`, `granted_at`, `revoked_at`, `expires_at`, `ip_address`, `user_agent`, `metadata`.
- `audit_trail` — the immutable history; each entry has:
  `action`, `consent_type`, `consent_version`, `occurred_at`, `policy_url`, `policy_text_hash`, `ip_address`, `user_agent`, `metadata`.
- `guest` — **only present when the subject is a guest** (i.e. a row exists in `guest_consents` keyed by that id). It holds `session_id`, `ip_address`, `user_agent`, `metadata`.

::: card "What a real export looks like"
```json
{
  "subject": {
    "type": "App\\Models\\User",
    "id": "42"
  },
  "consents": [
    {
      "consent_type": "marketing-emails",
      "consent_name": "Marketing emails",
      "purpose": "Send promotional newsletters",
      "legal_basis": "consent",
      "data_controller": "ACME Ltd",
      "consent_version": "2024-01",
      "granted": true,
      "granted_at": "2024-03-01T09:00:00+00:00",
      "revoked_at": null,
      "expires_at": "2025-03-01T09:00:00+00:00",
      "ip_address": "203.0.113.7",
      "user_agent": "Mozilla/5.0 ...",
      "metadata": { "source": "signup-form" }
    }
  ],
  "audit_trail": [
    {
      "action": "granted",
      "consent_type": "marketing-emails",
      "consent_version": "2024-01",
      "occurred_at": "2024-03-01T09:00:00+00:00",
      "policy_url": "https://acme.example/privacy/2024-01",
      "policy_text_hash": "9f86d081...",
      "ip_address": "203.0.113.7",
      "user_agent": "Mozilla/5.0 ...",
      "metadata": { "source": "signup-form" }
    }
  ]
}
```
For a **guest** subject the same document also carries a top-level `"guest"` object.
:::

### Worked Subject-Access-Request flow

The whole flow is: **resolve the subject → call the exporter → deliver the JSON.**

#### Case 1 — the subject is a logged-in / known user

```php
<?php

use App\Models\User;
use Selli\LaravelGdprConsentDatabase\Services\ConsentExporter;

$user = User::findOrFail(42);

// Hand the model straight to the exporter — it reads the morph type and key for you.
$export = app(ConsentExporter::class)->exportModel($user);

// $export is the array shown above: subject / consents / audit_trail (+ guest for guests).
```

#### Case 2 — the subject is a guest (by session id)

A guest has no model instance, only the `session_id` from their technical cookie. Use `export()` with the stored type and the session id:

```php
<?php

use Selli\LaravelGdprConsentDatabase\Models\GuestConsent;
use Selli\LaravelGdprConsentDatabase\Services\ConsentExporter;

$sessionId = 'sess_abc123'; // e.g. from the request cookie identifying the visitor

$export = app(ConsentExporter::class)->export(GuestConsent::class, $sessionId);

// Because a guest_consents row exists for this id, $export also contains a 'guest' key.
```

#### Deliver it as a JSON download (controller)

For portability (Art. 20) the subject is entitled to a structured, machine-readable file. Use `toJson()` and stream it as an attachment:

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Response;
use Selli\LaravelGdprConsentDatabase\Services\ConsentExporter;

class ConsentExportController extends Controller
{
    public function show(User $user, ConsentExporter $exporter): Response
    {
        // toJson() returns a pretty-printed JSON string.
        $json = $exporter->toJson($user->getMorphClass(), (string) $user->getKey());

        return response($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="consent-export-'.$user->getKey().'.json"',
        ]);
    }
}
```

::: callout warning "Authorise the request first"
This endpoint discloses personal data. Gate it behind authentication and an authorisation check (the requester must be the subject, or a verified controller staff member). The package does not do identity verification for you.
:::

### From the command line

For one-off requests or scripted fulfilment, use the `gdpr:consents:export` Artisan command. Pass the **stored type** (a morph alias or a fully-qualified class name) and the **id**:

```bash
# Print the JSON export to stdout
php artisan gdpr:consents:export "App\Models\User" 42

# Write it to a file with --path
php artisan gdpr:consents:export "App\Models\User" 42 --path=storage/app/exports/user-42.json

# A guest subject, keyed by session id
php artisan gdpr:consents:export "Selli\LaravelGdprConsentDatabase\Models\GuestConsent" sess_abc123 --path=storage/app/exports/guest.json
```

With `--path` the file is written and you get a confirmation line; without it the JSON is printed. See [/reference/commands](/reference/commands) for the full command reference.

---

## Right to erasure (Art. 17)

Erasure collides with another GDPR duty: **Art. 7(1)** requires you to be able to *demonstrate* that consent was given. If you simply deleted the records you would destroy that legal proof. The package resolves this tension by **pseudonymisation** — replacing the identifier with an irreversible token while keeping the *what* and *when* of the consent as proof.

> **Pseudonymisation** means the data can no longer be attributed to a specific person without extra information that is held separately — here, that extra information never exists, because the token is random and one-way.

### On the model

```php
<?php

use App\Models\User;

$user = User::findOrFail(42);

$result = $user->anonymizeConsents();
// $result === [
//     'token'      => 'anon_3f2a...c91',  // the irreversible pseudonym
//     'consents'   => 3,                   // user_consents rows re-keyed
//     'audit_logs' => 7,                   // consent_audit_logs rows re-keyed
// ]
```

You may pass your own token if you need a deterministic pseudonym: `$user->anonymizeConsents('case-2026-0042')`.

### From the command line

```bash
php artisan gdpr:anonymize-subject "App\Models\User" 42

# With a specific pseudonym instead of a random one
php artisan gdpr:anonymize-subject "App\Models\User" 42 --token=case-2026-0042
```

### What anonymisation actually does

In a single database transaction it:

- generates an **irreversible random token** (`anon_…`) unless you supply one;
- in `user_consents`: sets `consentable_id` to the token and **scrubs** `ip_address`, `user_agent` and `metadata` to `null`;
- in `consent_audit_logs`: same — re-keys to the token and scrubs `ip_address`, `user_agent`, `metadata`;
- for a **guest** subject: updates the matching `guest_consents` row, **rotating its `session_id` to the token** (the live session id is itself an identifier) and scrubbing `ip_address`, `user_agent`, `metadata`;
- **keeps the proof**: `action`, `consent_version`, `policy_url` and `policy_text_hash` are preserved under the pseudonym;
- writes one immutable `anonymized` audit entry recording how many rows were affected.

After this, the original identifier and the directly identifying fields are gone, but you can still prove *that a consent of version X was granted at time Y under policy Z*.

::: callout warning "Erasing the host model and PII elsewhere is your job"
The package only erases the consent data it owns (`user_consents`, `consent_audit_logs`, `guest_consents`). It does **not** delete the underlying `User` row, and it does **not** touch personal data anywhere else in your application. Deleting the host model and scrubbing PII in your own tables remains your responsibility. Note also that any personal data you stored in consent `metadata` is **cleared** — capture anything non-personal you still need *before* erasing.
:::

For the design rationale and exactly which columns survive, see [/concepts/erasure](/concepts/erasure) and [/concepts/audit-trail](/concepts/audit-trail). For what the package deliberately does *not* do, see [/compliance/limitations](/compliance/limitations).

---

## Right to withdraw consent (Art. 7(3))

Withdrawal must be **as easy as giving** consent. It is a first-class operation: `revokeConsent()` marks every active consent in a type's group as revoked (sets `revoked_at`, `granted = false`) and records a `revoked` audit entry — without erasing anything.

### Withdraw a single purpose

```php
<?php

use App\Models\User;

$user = User::findOrFail(42);

// Pass the consent-type slug. Returns the number of consent records revoked.
$count = $user->revokeConsent('marketing-emails');
```

`revokeConsent()` is safe to call with arbitrary input: if the slug does not resolve to a known consent type it simply returns `0` rather than throwing.

### Cookie banner: Reject All / Save Preferences

For a cookie banner, wire the two buttons to grant or revoke each non-essential purpose. "Reject All" withdraws everything optional; "Save Preferences" grants the ticked ones and withdraws the rest:

```php
<?php

use App\Models\User;

/** @var User $user */
/** @var array<string, bool> $choices  e.g. ['analytics' => true, 'marketing-emails' => false] */

foreach ($choices as $slug => $accepted) {
    if ($accepted) {
        $user->giveConsent($slug);   // record fresh consent
    } else {
        $user->revokeConsent($slug); // withdraw — as easy as granting
    }
}
```

"Reject All" is the same loop with every optional `$slug` set to `false`. For guests, the same grant/revoke calls are available on the guest consent model — see [/concepts/guest-consents](/concepts/guest-consents).

::: callout note "Withdrawal is not erasure"
Revoking leaves the consent record in place (now marked revoked) so the audit trail still shows it was once given and then withdrawn. To remove the identifying data as well, follow up with the [erasure](#right-to-erasure-art-17) flow above.
:::
