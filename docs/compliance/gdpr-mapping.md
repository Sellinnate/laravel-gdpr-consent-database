---
title: "GDPR Mapping"
description: "How package features map to GDPR articles."
type: reference
---

# GDPR Mapping

This page maps the package's features to the GDPR articles they help you satisfy.

::: callout warning "Not legal advice"
This package is a technical tool. It helps you *implement* compliance, but adopting it does not make your
application compliant on its own. Always validate your processing with your DPO or legal counsel.
:::

## Coverage

Legend: ✅ the package provides a mechanism · 🟡 partial — you must do part of the work · ⛔ out of scope
(see [Scope & limitations](/compliance/limitations)).

| GDPR article | Requirement | The package | How |
|---|---|---|---|
| **Art. 4(11)** | Consent freely given, specific, informed, unambiguous | ✅ | Typed, per-purpose consents; banner has no pre-ticked optional boxes |
| **Art. 5(1)(c)** | Data minimisation | ✅ | [IP & user-agent](/concepts/data-and-privacy) can be masked or not stored |
| **Art. 7(1)** | Demonstrate consent | ✅ | Immutable [audit trail](/concepts/audit-trail) with version + policy snapshot |
| **Art. 7(3)** | Withdrawal as easy as giving | ✅ | `revokeConsent()`; banner *Reject All* revokes optional cookies |
| **Art. 8** | Children's consent | ⛔ | **No age-gate or parental-consent mechanism.** Implement age verification yourself; you may record the outcome in consent `metadata`. |
| **Art. 15** | Right of access | ✅ | [`ConsentExporter`](/compliance/data-subject-rights) exports consents + audit trail (+ the guest row) |
| **Art. 17** | Right to erasure | ✅ | [`anonymizeConsents()`](/concepts/erasure) — pseudonymise while keeping proof |
| **Art. 20** | Data portability | ✅ | Structured JSON export via `ConsentExporter::toJson()` |
| **Art. 21 / 18** | Object / restriction | ⛔ | Out of scope — this package handles consent, not objection/restriction workflows |
| **Art. 25** | By design & **by default** | 🟡 | Optional consents are off by default; IP/UA minimisation is opt-in (a documented trade-off) |
| **Art. 30** | Records of processing | 🟡 | `legal_basis`, `purpose`, `data_controller` columns — **you populate them** |
| **Art. 32** | Security of processing | ✅ | CSRF, rate-limiting (`throttle`), query-builder (no SQLi), input validation |
| **ePrivacy** | Prior consent for non-essential cookies | 🟡 | The banner **records** the choice; **you must block non-essential scripts** until consent — see [Cookie banner & ePrivacy](/concepts/cookie-banner) |

## The two pillars

### Provability (Art. 7(1))

Every consent action is written to an **append-only** audit log that captures the exact version and policy
the subject agreed to, plus when, from which IP and user agent. Records cannot be updated or deleted through
the application. See [Audit Trail](/concepts/audit-trail).

### Erasure without losing proof (Art. 17 vs Art. 7)

Erasure and provability appear to conflict: you must delete personal data, yet keep proof of consent. The
package resolves this by **pseudonymising** — replacing the subject identifier with an irreversible token and
scrubbing IP, user agent and metadata, while preserving the action/version/policy as proof under the
pseudonym. See [Data Subject Rights](/compliance/data-subject-rights).
