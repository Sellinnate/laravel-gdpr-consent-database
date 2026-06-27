---
title: "Laravel GDPR Consent Manager"
description: "Enterprise-grade GDPR consent management for Laravel applications."
---

![Laravel GDPR Consent Manager](/assets/images/banner.png)

# Laravel GDPR Consent Manager

**Consent & privacy for Laravel.** A complete, enterprise-grade toolkit to manage GDPR consent in your
Laravel applications: typed consents, versioning, expiration, guest (cookie-based) consents, an immutable
audit trail and a fully customisable cookie banner.

::: callout tip "New here?"
Start with **[Installation](/getting-started/installation)**, then follow the
**[Quick Start](/getting-started/quick-start)**. If you want to understand the data model first, read
**[Core Concepts](/getting-started/concepts)**.
:::

## Why this package

::: card "Built for compliance, not just cookies"
GDPR is about **provable, specific, informed and freely-given consent** — and the right to withdraw it as
easily as it was given. This package models those requirements directly: every consent is typed, versioned,
timestamped and attributable, so you can demonstrate compliance (Art. 7(1)) instead of hoping for it.
:::

## Feature overview

- **Typed consents** — define exactly what users consent to (`ConsentType`) with required/optional flags.
- **Polymorphic** — attach consents to *any* model (`User`, `Member`, …) via the `HasGdprConsents` trait.
- **Versioning** — when your policy changes, create a new version and detect who needs to re-consent.
- **Expiration** — set validity periods; consents expire automatically and can be renewed.
- **Guest consents** — track consent for anonymous visitors via session / technical cookie code.
- **Cookie banner** — a configurable Blade banner with AJAX accept / reject / save-preferences endpoints.
- **Auditable** — IP address, user agent, metadata and the exact policy version captured per consent.

## Requirements

| Requirement | Version |
|---|---|
| PHP | `^8.2` |
| Laravel | `^11.0` or `^12.0` |

## License

Released under the **MIT License**.
