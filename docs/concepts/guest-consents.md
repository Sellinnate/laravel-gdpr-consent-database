---
title: "Guest Consents"
description: "Track consent for anonymous (not-logged-in) visitors."
type: concept
---

# Guest Consents

Anonymous visitors don't have a model to attach consents to. The package tracks them by a **session id**
(the *technical cookie code*) using the `GuestConsentManager` service.

## The manager

```php
use Selli\LaravelGdprConsentDatabase\Services\GuestConsentManager;

$guests = app(GuestConsentManager::class); // resolved from the container
```

Every method accepts an optional `$sessionId`. When omitted, the current session id is used: the manager
reads the `gdpr_session_id` cookie and, when it is missing, falls back to the framework session id and
queues the `gdpr_session_id` cookie for 30 days.

## Recording guest consent

```php
// Give consent for the current session
$guests->giveConsent('marketing-emails', ['source' => 'cookie_banner']);

// Give consent for a specific technical cookie code.
// Signature: giveConsent(string $consentTypeSlug, array $metadata = [], ?int $validityMonths = null, ?string $sessionId = null)
// The session id is the LAST argument, so the metadata and validity slots must be filled first.
$guests->giveConsent('marketing-emails', [], null, 'gdpr_abc123');

// Check
$guests->hasConsent('marketing-emails');             // current session
$guests->hasConsent('marketing-emails', 'gdpr_abc123');

// Revoke (returns bool: true when at least one record was revoked)
$guests->revokeConsent('marketing-emails');
```

## Required consents for guests

```php
$guests->hasAllRequiredConsents();      // bool
$guests->getMissingRequiredConsents();  // Collection<int, ConsentType>
$guests->getActiveConsents();           // Collection<int, UserConsent>
```

## How it works under the hood

A `guest_consents` row is created per session id (see `GuestConsent::findOrCreateForSession()`) and the
actual consents are stored in `user_consents` through the same polymorphic relationship used for
authenticated users. `GuestConsent` uses the `HasGdprConsents` trait, so calling `giveConsent()` on a
guest records the consent under that guest. Guests therefore share the full consent feature set
(versioning, expiration, audit trail).

The `guest_consents` row also stores `ip_address` and `user_agent`, captured through the package's
privacy helpers so they honour your storage/anonymisation config.

::: callout warning "Guest consent is browser-scoped, not person-scoped"
A guest record is keyed by the `gdpr_session_id` cookie. It proves *"this browser chose X at this time"* —
**not** that a verified, identifiable person did. If the visitor clears their cookies, switches browser, or
opens a private window, the original `guest_consents` row is **orphaned**: no future request will resolve
back to it, and the visitor effectively starts from scratch. Treat guest consent as a best-effort,
pre-login record, and read [/compliance/limitations] before relying on it for anything binding.
:::

## Migrating guest consent on login

When a visitor finally logs in or registers, their pre-login cookie choices live on a `guest_consents`
row, not on their account. Two things should happen at that moment:

1. **Replay** each active guest consent onto the now-authenticated user, so their banner choices aren't
   silently lost the instant they sign in.
2. **Anonymise** the old guest row, so you don't keep a second, identifiable record (with ip/user-agent)
   describing the same person sitting in `guest_consents` forever.

Do this in your login flow — for example in an event listener for Laravel's `Illuminate\Auth\Events\Login`
event, or right after `Auth::login($user)` in your controller.

```php
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Selli\LaravelGdprConsentDatabase\Services\GuestConsentManager;

class MigrateGuestConsentsOnLogin
{
    public function handle(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;

        // The guest record is keyed by the gdpr_session_id cookie.
        $sessionId = request()->cookie('gdpr_session_id');

        // No cookie => nothing to migrate (e.g. API login, or cookies were cleared).
        if (! is_string($sessionId) || $sessionId === '') {
            return;
        }

        $guests = app(GuestConsentManager::class);

        // 1. Replay the guest's *active* consents onto the authenticated user.
        foreach ($guests->getActiveConsents($sessionId) as $consent) {
            $slug = $consent->consentType?->slug;

            if ($slug === null) {
                continue; // consent type was deleted; nothing to replay
            }

            // $user uses the HasGdprConsents trait. giveConsent() supersedes any existing
            // active consent for the same group, so re-running this is safe (idempotent).
            $user->giveConsent($slug, $consent->metadata ?? []);
        }

        // 2. Anonymise the old guest row so no orphaned identifiable guest record remains.
        $guests->getGuestConsent($sessionId)->anonymizeConsents();
    }
}
```

::: callout tip "Why both steps"
Replaying preserves the visitor's pre-login decisions on the account they now own. Anonymising the guest
row prevents a duplicate, identifiable copy of those decisions (and the captured ip/user-agent) from
lingering after the person is known. Skipping step 2 leaves you holding personal data you no longer need.
:::

Register the listener in your `EventServiceProvider` (or via an attribute / `Event::listen`):

```php
use Illuminate\Auth\Events\Login;
use App\Listeners\MigrateGuestConsentsOnLogin;

protected $listen = [
    Login::class => [
        MigrateGuestConsentsOnLogin::class,
    ],
];
```

## Erasure for guests

Guests are anonymisable too — and the erasure correctly scrubs the `guest_consents` row itself:

```php
use Selli\LaravelGdprConsentDatabase\Services\GuestConsentManager;

$guest = app(GuestConsentManager::class)->getGuestConsent('gdpr_abc123');

// Returns array{token: string, consents: int, audit_logs: int}
$guest->anonymizeConsents();
```

See [/compliance/data-subject-rights] and [/compliance/limitations].
