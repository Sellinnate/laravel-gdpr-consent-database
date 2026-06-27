---
title: Events & integration
type: concept
---

# Events & integration

This package fires Laravel **events** at the key moments in a consent's life. An *event* is just a small, immutable object that says "this thing happened" — it carries data but contains no behaviour. You react to events by registering **listeners**: your own code that runs in response.

Use events when you want side effects that are *not* part of recording the consent itself. Sending a welcome email, unsubscribing someone from a newsletter, pinging an external CRM, writing an analytics row — none of that belongs inside the consent-storage logic, but all of it should happen *because of* a consent change. Events are the clean seam between "the package stored the fact" and "your app reacts to it".

> [!NOTE]
> All four events live in the `Selli\LaravelGdprConsentDatabase\Events` namespace. They use Laravel's `Dispatchable` trait, so each one is fired with `EventName::dispatch(...)`. You never construct or dispatch these yourself — the package does it for you.

## The four events at a glance

| Event | Fires when… | Payload (public readonly properties) |
| --- | --- | --- |
| `ConsentGranted` | `giveConsent()` records a fresh consent | `Model $consentable`, `UserConsent $consent` |
| `ConsentRevoked` | `revokeConsent()` withdraws a consent (one per revoked record) | `Model $consentable`, `UserConsent $consent` |
| `ConsentRenewed` | `renewConsent()` re-confirms onto the current version | `Model $consentable`, `UserConsent $consent` |
| `ConsentExpired` | `gdpr:consents:expire` closes a past-expiry consent | `UserConsent $consent` |

Two terms used above:

- **`$consentable`** — the model that owns the consent (the *subject*), e.g. your `User`. It is typed as the generic `Illuminate\Database\Eloquent\Model` because any model using the `HasGdprConsents` trait can own consents.
- **`$consent`** — the `UserConsent` row that was affected: the single source of truth for what was granted, on which version, when, and (for revoke/expire) when it was closed.

> [!IMPORTANT]
> **Notice the asymmetry:** three events carry **both** `$consentable` and `$consent`, but `ConsentExpired` carries **only `$consent`**. Expiry is driven by a scheduled command that sweeps consent rows directly, not by an action on a specific subject model, so there is no `$consentable` already in hand to pass along. If a listener needs the subject for an expired consent, load it from the relation: `$event->consent->consentable`.

## When each event fires (the details)

### `ConsentGranted`

Dispatched at the end of [`giveConsent()`](/guides/recording-consent), after the consent row is persisted:

```php
$consent = $this->persistConsent($consentType, $metadata, $validityMonths);

ConsentGranted::dispatch($this, $consent);
```

This is the event for a *new* grant — a subject agreeing to a purpose they were not actively consented to (or re-agreeing after a prior consent was revoked or expired).

> [!WARNING]
> Granting a consent **supersedes** any previous active consent for the same consent-type group — the old record is closed so there is only ever one active consent per group. **That supersede does NOT emit `ConsentRevoked`.** Superseding is part of *granting* (the subject is renewing their agreement, not withdrawing it), so emitting a revoke event would wrongly tell your listeners "this person opted out". You will see exactly one `ConsentGranted` from a `giveConsent()` call, and no revoke event — even if an older consent was closed underneath. See [versioning](/concepts/versioning) for how groups and supersession work.

### `ConsentRevoked`

Dispatched only from an **explicit** [`revokeConsent()`](/guides/recording-consent) call — when the subject actively withdraws their agreement. `revokeConsent()` may close several active records for the same group, so the event fires **once per revoked record**:

```php
$revoked = DB::transaction(fn (): Collection => $this->revokeConsentGroup($consentType));

foreach ($revoked as $consent) {
    ConsentRevoked::dispatch($this, $consent);
}
```

If there was nothing active to revoke, no event fires. This event means a genuine opt-out — the right moment to stop processing the subject's data for that purpose.

### `ConsentRenewed`

Dispatched at the end of [`renewConsent()`](/guides/recording-consent) — when an existing consent is re-confirmed onto the **current** version of the consent type:

```php
ConsentRenewed::dispatch($this, $newConsent);
```

> [!IMPORTANT]
> Renewing does **not** fire `ConsentGranted`. Renewal is its own distinct signal: the subject already consented and is re-affirming (typically after a policy version change or an approaching expiry), rather than agreeing for the first time. Internally the renewal supersedes the old record without firing `ConsentRevoked`, exactly like a grant. If your listeners need to treat first-time grants and renewals differently, this is why the package gives them separate events.

### `ConsentExpired`

Dispatched by the `gdpr:consents:expire` console command, which closes every consent whose `expires_at` is in the past:

```php
ConsentExpired::dispatch($consent);
```

This is the only event not triggered by a method on your subject model — it is driven by the scheduled command. See the [expiration guide](/guides/expiration) for how to schedule it.

## Events fire AFTER the database transaction commits

Every dispatch above happens **after** the surrounding `DB::transaction(...)` has committed:

- `giveConsent()`, `revokeConsent()` and `renewConsent()` do their database writes inside a transaction, then dispatch once the transaction has returned.
- `ExpireConsentsCommand` wraps each consent's update + audit entry in a transaction, and dispatches `ConsentExpired` only *after* that transaction closes — its own comment says so:

```php
DB::transaction(function () use ($consent): void {
    $consent->granted = false;
    $consent->save();
    // ... write audit entry ...
});

// Dispatch only after the row is durably committed, so listener side effects
// (emails / webhooks) never fire for a rolled-back change.
ConsentExpired::dispatch($consent);
```

> [!NOTE]
> **Why this matters.** If the transaction rolls back (a constraint violation, a deadlock, an exception in the same block), the consent change never reaches the database — and because the dispatch sits *after* the commit, your listener never runs. You will never send a "thanks for opting in" email for a grant that was rolled back, and never tell a CRM someone withdrew when the withdrawal didn't actually persist. Listener side effects and database state stay consistent: if the listener ran, the change is durably saved.

This is a deliberate design choice and it means you do **not** need to wrap your listeners in Laravel's `afterCommit` machinery — the package already guarantees you are post-commit.

## How to listen

You react to an event by registering a listener. There are two common styles.

### Option 1 — a closure with `Event::listen`

Good for quick, one-off reactions. Register this in a service provider's `boot()` method (e.g. `app/Providers/AppServiceProvider.php`):

```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Selli\LaravelGdprConsentDatabase\Events\ConsentGranted;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(function (ConsentGranted $event): void {
            // $event->consentable is the subject (e.g. the User).
            // $event->consent is the UserConsent row just created.
            logger()->info('Consent granted', [
                'subject_id' => $event->consentable->getKey(),
                'consent_id' => $event->consent->id,
                'version'    => $event->consent->consent_version,
            ]);
        });
    }
}
```

### Option 2 — a dedicated listener class

Preferred once a reaction grows beyond a couple of lines, or when you want it queued. Create the class:

```php
<?php

namespace App\Listeners;

use App\Models\MailingList;
use Selli\LaravelGdprConsentDatabase\Events\ConsentRevoked;

class UnsubscribeFromMailingList
{
    public function handle(ConsentRevoked $event): void
    {
        // The subject actively withdrew consent: stop processing their data
        // for this purpose. Here we remove them from the mailing list.
        MailingList::unsubscribe(
            email: $event->consentable->email,
            reason: 'consent_revoked',
            consentType: $event->consent->consent_type_id,
        );
    }
}
```

Then map the event to the listener. On Laravel 11+ the framework auto-discovers `handle()` listeners in `app/Listeners`; if you prefer to be explicit, register it in `App\Providers\EventServiceProvider`:

```php
<?php

namespace App\Providers;

use App\Listeners\UnsubscribeFromMailingList;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Selli\LaravelGdprConsentDatabase\Events\ConsentRevoked;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ConsentRevoked::class => [
            UnsubscribeFromMailingList::class,
        ],
    ];
}
```

> [!TIP]
> To run a listener on a queue instead of inline, have it implement `Illuminate\Contracts\Queue\ShouldQueue`. Because the package only dispatches *after* commit, a queued listener is safe — by the time the job runs, the consent change is already durably stored.

## A realistic use case for each event

| Event | A natural reaction |
| --- | --- |
| `ConsentGranted` | Add the subject to the relevant audience/segment; send a confirmation receipt of what they agreed to. |
| `ConsentRevoked` | Unsubscribe them from the mailing list and stop the related processing immediately. |
| `ConsentRenewed` | Refresh "last confirmed" timestamps in your CRM; clear any "please re-confirm" banner. |
| `ConsentExpired` | Send a re-consent email inviting the subject to renew (load the subject via `$event->consent->consentable`). |

```php
<?php

namespace App\Listeners;

use App\Mail\ReConsentInvitation;
use Illuminate\Support\Facades\Mail;
use Selli\LaravelGdprConsentDatabase\Events\ConsentExpired;

class SendReConsentEmail
{
    public function handle(ConsentExpired $event): void
    {
        // ConsentExpired carries only $consent, so reach the subject through the relation.
        $subject = $event->consent->consentable;

        Mail::to($subject->email)->send(
            new ReConsentInvitation($event->consent)
        );
    }
}
```

## Summary

- Four events: `ConsentGranted`, `ConsentRevoked`, `ConsentRenewed`, `ConsentExpired`.
- Three carry `$consentable` + `$consent`; `ConsentExpired` carries only `$consent` — reach the subject with `$event->consent->consentable`.
- Grants and renewals are separate signals; superseding an old consent during a grant or renewal does **not** fire `ConsentRevoked` — only an explicit `revokeConsent()` does.
- All events dispatch **after** the database transaction commits, so listeners never react to a rolled-back change.

Related reading: [recording consent](/guides/recording-consent), the [expiration guide](/guides/expiration), and [versioning](/concepts/versioning).
