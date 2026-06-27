---
type: concept
title: "IP Addresses & Data Minimisation"
description: "Why IP addresses are personal data, and how the package lets you not store them — or store a masked form — to honour GDPR data minimisation."
---

# IP Addresses & Data Minimisation

When someone grants, revokes or renews a consent, the package can record the IP address the
request came from as part of the [audit trail](/concepts/audit-trail). This page explains why
that IP is treated as personal data, and how `selli/laravel-gdpr-consent-database` lets you store
**less** of it — or none of it — to stay on the right side of GDPR.

## Why an IP address is personal data

> **Personal data** (GDPR Art. 4(1)) — any information relating to an identified or identifiable
> natural person. You don't need a name: anything that *can* single someone out counts.

An IP address can be tied back to a real person (for example, your ISP knows which subscriber a
given address belonged to at a given moment). EU case law and regulators treat IP addresses as
personal data, so storing one is processing personal data — and that brings GDPR obligations.

> **Data minimisation** (GDPR Art. 5(1)(c)) — personal data must be *adequate, relevant and
> limited to what is necessary* for the purpose. In plain terms: only keep what you actually need,
> and no more.

For consent records, the IP is *context* — useful as supporting evidence of where a consent came
from, but rarely something you need in full, precise form forever. Data minimisation is the reason
this package gives you a choice instead of always storing the raw address.

## Your three options

The package gives you exactly three outcomes, controlled by two config flags:

1. **Store the raw IP** — full address, e.g. `203.0.113.42`. (Default.)
2. **Store a masked IP** — anonymised form, e.g. `203.0.113.0`.
3. **Store nothing** — the `ip_address` column is left `null`.

::: callout note "Anonymisation vs. pseudonymisation"
Masking the IP here is **anonymisation of that one field** — once the last octet is zeroed, the
original address cannot be recovered from the stored value. This is different from the full erasure
flow described under [erasure](/concepts/erasure), which scrubs *all* identifying fields of a
consent record.
:::

## The configuration

Both flags live in the `privacy` section of the published config file
(`config/gdpr-consent-database.php` — see the [configuration overview](/configuration/overview)):

```php
// config/gdpr-consent-database.php

'privacy' => [
    'store_ip_address' => true,
    'anonymize_ip' => false,
],
```

| Key | Type | Default | Effect |
|---|---|---|---|
| `privacy.store_ip_address` | `bool` | `true` | When `false`, no IP is ever stored — the column is set to `null`. |
| `privacy.anonymize_ip` | `bool` | `false` | When `true` (and storing is enabled), the IP is masked before saving. |

How the two flags combine:

| `store_ip_address` | `anonymize_ip` | What gets stored |
|---|---|---|
| `true` | `false` | The **raw** IP address (default). |
| `true` | `true` | The **masked** IP address. |
| `false` | *(any value)* | **`null`** — storage is off, so anonymisation never runs. |

::: callout note "store_ip_address wins"
`store_ip_address` is checked first. If it is `false`, the package returns `null` and never looks at
`anonymize_ip`. There is no combination that stores raw data when storing is disabled.
:::

## How the masking works

The masking lives in `Selli\LaravelGdprConsentDatabase\Support\IpAddress`. The
`IpAddress::anonymize()` method keeps the network-identifying part of the address and zeroes the
rest:

- **IPv4** — the **last octet** (the fourth number) is set to `0`.
- **IPv6** — the **first 48 bits (the `/48` prefix, 6 bytes) are kept**; the remaining **80 bits
  (10 bytes) are zeroed**.

Anything that is not a valid IPv4 or IPv6 address is returned unchanged.

### Worked examples

```php
use Selli\LaravelGdprConsentDatabase\Support\IpAddress;

// IPv4: last octet zeroed
IpAddress::anonymize('203.0.113.42');   // => '203.0.113.0'
IpAddress::anonymize('198.51.100.255'); // => '198.51.100.0'

// IPv6: first 48 bits (/48 prefix) kept, last 80 bits zeroed
IpAddress::anonymize('2001:db8:85a3:8d3:1319:8a2e:370:7348'); // => '2001:db8:85a3::'
```

In the IPv6 example, the first three groups (`2001:db8:85a3`) are the kept `/48` prefix; every
group after them becomes zero, which IPv6 notation collapses to the trailing `::`.

::: callout note "Masking is not reversible"
Zeroing the host bits throws information away. Two different visitors on the same network
(`203.0.113.42` and `203.0.113.99`) both store as `203.0.113.0`, so you can no longer tell them
apart from the stored value — which is exactly the point.
:::

## Where the policy is applied

You never call `IpAddress::anonymize()` yourself. The package routes every IP it stores through
`IpAddress::forStorage()`, which applies the config flags for you. That single helper is used in
**every** place an IP could be persisted:

```php
// src/Support/IpAddress.php (simplified)

public static function forStorage(?string $ip): ?string
{
    if ($ip === null || $ip === '') {
        return null;
    }

    if (! config('gdpr-consent-database.privacy.store_ip_address', true)) {
        return null; // storage disabled
    }

    if (config('gdpr-consent-database.privacy.anonymize_ip', false)) {
        return self::anonymize($ip); // store masked
    }

    return $ip; // store raw
}
```

The helper is applied at three call sites:

1. **Authenticated consents** — when a model using the `HasGdprConsents` trait grants/renews a
   consent, the new `user_consents` row stores `IpAddress::forStorage(request()->ip())`.
2. **The audit trail** — every audit entry written for a consent action stores its IP through the
   same helper, so the `consent_audit_logs` table follows the same policy.
3. **[Guest consents](/concepts/guest-consents)** — `GuestConsent::findOrCreateForSession()` stores
   the guest's IP through `IpAddress::forStorage(request()->ip())` when the record is created.

Because all three share one helper, a single config change covers your whole consent footprint —
there is no place that quietly stores a raw IP behind your back.

## What is *not* masked: `user_agent`

The masking applies **only to the IP address**. Alongside the IP, the package also records the
request's `user_agent` string (the browser/device identifier), and that is stored **as-is** —
`request()->userAgent()` is saved verbatim, with no masking flag.

::: callout warning "User agents can be identifying too"
A user-agent string can be detailed enough to contribute to identifying someone (especially
combined with other data). The package does not offer a built-in toggle for it. If your threat model
requires it, strip or truncate the user agent in your own application before consents are recorded.
:::

## Choosing a setting

- **Keep the defaults (`true` / `false`)** if you want raw IPs as the strongest evidence of where a
  consent came from, and your privacy notice and retention policy cover storing them.
- **Turn on `anonymize_ip`** to keep a useful network-level signal while honouring data
  minimisation — a good default for most sites.
- **Turn off `store_ip_address`** if you don't need IPs at all; the audit trail still records the
  action, timestamp, version and policy snapshot without any IP.

## Relationship to erasure

Choosing *not* to store (or to mask) IPs reduces what you hold up front. When a data subject later
exercises their right to erasure, the [erasure](/concepts/erasure) flow scrubs identifying fields —
including IP addresses — from existing consent records entirely, while preserving the non-identifying
proof that a consent action took place. Data minimisation reduces the exposure before erasure; the
erasure flow removes what remains on request.
