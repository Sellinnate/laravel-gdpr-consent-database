---
title: "GDPR Mapping"
description: "How package features map to GDPR articles."
---

# GDPR Mapping

This page maps the package's features to the GDPR articles they help you satisfy.

::: callout warning "Not legal advice"
This package is a technical tool. It helps you *implement* compliance, but adopting it does not make your
application compliant on its own. Always validate your processing with your DPO or legal counsel.
:::

## Coverage

| GDPR article | Requirement | How the package helps |
|---|---|---|
| **Art. 4(11)** | Consent must be freely given, specific, informed, unambiguous | Typed, per-purpose consents; the banner has no pre-ticked optional boxes |
| **Art. 7(1)** | You must be able to **demonstrate** consent | Immutable [audit trail](/compliance/audit-trail) with version + policy snapshot |
| **Art. 7(3)** | Withdrawal as easy as giving consent | `revokeConsent()`; banner "Reject All" revokes optional cookies |
| **Art. 8** | Children's consent | Store age-verification context in `metadata` / `legal_basis` |
| **Art. 15** | Right of access | Export a subject's consents and audit trail (see [Data Subject Rights](/compliance/data-subject-rights)) |
| **Art. 17** | Right to erasure | `anonymizeConsents()` / `gdpr:anonymize-subject` — pseudonymise while keeping proof |
| **Art. 20** | Data portability | Audit trail and consents are plain Eloquent models, easily serialised to JSON/CSV |
| **Art. 30** | Records of processing activities | `legal_basis`, `purpose`, `data_controller` on each consent type |
| **ePrivacy** | Prior consent for non-essential cookies | Cookie banner + script-gating pattern |

## The two pillars

### Provability (Art. 7(1))

Every consent action is written to an **append-only** audit log that captures the exact version and policy
the subject agreed to, plus when, from which IP and user agent. Records cannot be updated or deleted through
the application. See [Audit Trail](/compliance/audit-trail).

### Erasure without losing proof (Art. 17 vs Art. 7)

Erasure and provability appear to conflict: you must delete personal data, yet keep proof of consent. The
package resolves this by **pseudonymising** — replacing the subject identifier with an irreversible token and
scrubbing IP, user agent and metadata, while preserving the action/version/policy as proof under the
pseudonym. See [Data Subject Rights](/compliance/data-subject-rights).
