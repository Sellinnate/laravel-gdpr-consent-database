---
title: "Laravel GDPR Consent Manager"
description: "Enterprise-grade GDPR consent management for Laravel applications."
type: concept
---

![Laravel GDPR Consent Manager](/assets/images/banner.png)

# Laravel GDPR Consent Manager

**Consent & privacy for Laravel.** A complete, enterprise-grade toolkit to manage GDPR consent in your
Laravel applications: typed consents, versioning, expiration, guest (cookie-based) consents, an immutable
audit trail and a fully customisable cookie banner.

::: callout tip "New here? Start at zero."
Never dealt with GDPR consent before? Read **[What is GDPR consent? (start here)](/getting-started/what-is-gdpr-consent)** —
it explains the law and the mental model from scratch, no prior knowledge assumed. Then
**[Installation](/getting-started/installation)** → **[Quick Start](/getting-started/quick-start)**.
:::

## How these docs are organised

::: card "Find what you need"
- **[Getting Started](/getting-started/what-is-gdpr-consent)** — from zero to your first recorded consent.
- **[Concepts](/concepts/architecture)** — *how it works and why*, mapped to the GDPR articles behind each
  feature (data model, versioning, the audit trail, erasure, cookies, IP minimisation, events).
- **[Guides](/guides/recording-consent)** — task-focused recipes you can copy-paste.
- **[Compliance](/compliance/gdpr-mapping)** — the article-by-article mapping and data-subject rights.
- **[Reference](/reference/commands)** — config, commands and the database schema.
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
