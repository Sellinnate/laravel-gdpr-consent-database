---
title: "Scope & limitations"
description: "An honest statement of what this package does — and, crucially, what it does not do — for GDPR compliance."
type: concept
---

# Scope & limitations

A compliance tool that hides its limitations is a liability. This page states plainly what the package
**does**, what it **deliberately leaves to you**, and what is **out of scope** — so you (and your DPO) can
decide where it fits.

::: callout warning "Not legal advice, not a compliance button"
This package is a **consent-management backbone**. Installing it does not make your application compliant.
Compliance depends on your purposes, your policies, your other processing, and decisions only your Data
Protection Officer / legal counsel can make. These docs help you implement consent *correctly*; they are not
legal advice.
:::

## What the package does well

- **Records consent provably** — typed, [versioned](/concepts/versioning) consents with an immutable
  [audit trail](/concepts/audit-trail) (who / what / which version / when / from where). This is the
  Art. 7(1) "demonstrate consent" requirement.
- **Makes withdrawal as easy as consent** (Art. 7(3)) — `revokeConsent()` and the banner's *Reject All*.
- **Expires and re-collects** consent ([expiration & renewal](/guides/expiration)).
- **Honours erasure without losing proof** (Art. 17) via [pseudonymisation](/concepts/erasure).
- **Exports a subject's data** (Art. 15/20) via the [data-subject-rights](/compliance/data-subject-rights) tooling.
- **Minimises data** ([IP & user-agent options](/concepts/data-and-privacy)).
- **Captures Art. 30 fields** (`legal_basis`, `purpose`, `data_controller`).

## What you must do yourself (the package can't)

::: card "1. Block non-essential scripts until consent (ePrivacy)"
The cookie banner **records** the visitor's choice; it does **not** load or block third-party scripts. Under
the ePrivacy Directive, non-essential cookies/trackers must not fire **before** consent. You must gate your
own scripts on the recorded choice — see the pattern in [Cookie banner & ePrivacy](/concepts/cookie-banner).
The package gives you the *signal* (the consent state); the gating is yours.
:::

::: card "2. Populate the policy snapshot (`policy_text_hash`)"
The audit log can store a `policy_url` and a `policy_text_hash` — a fingerprint of the exact policy text the
subject was shown. The package stores whatever you put on the `ConsentType`; it does **not** compute the hash
for you. If you want that proof, hash your policy text and set `policy_text_hash` on the consent type version.
:::

::: card "3. Choose your privacy defaults"
The full IP is stored by default (stronger Art. 7(1) proof). If your DPIA prefers minimisation, enable
`anonymize_ip` / disable `store_ip_address` / `store_user_agent`. The package gives you the switches; the
choice (and its DPIA justification) is yours. See [IP & data minimisation](/concepts/data-and-privacy).
:::

::: card "4. Set validity periods"
Consents do not expire unless you set `validity_months` on the consent type, and the
`gdpr:consents:expire` command must be **scheduled** by you. See [Expiration & renewal](/guides/expiration).
:::

::: card "5. Erase the rest of the subject's data"
`anonymizeConsents()` pseudonymises the consent records the package owns. Deleting the underlying `User`
model and any PII elsewhere in your application is your responsibility.
:::

## Out of scope (by design)

| Area | Status | Why / what to do |
|---|---|---|
| **Art. 8 — children's consent** | Not provided | There is no age-gate or parental-consent mechanism. If you process children's data, implement age verification yourself (you may record the outcome in consent `metadata`). |
| **Art. 21 — right to object / Art. 18 — restriction** | Out of scope | This package handles *consent*, not objection/restriction workflows for other legal bases. |
| **Consent for non-cookie purposes via the banner** | By design | The banner only handles `category = 'cookie'` types; manage other purposes through your own forms. |
| **Strong identity proof for guests** | Limited | Guest consent is tied to a browser/session (a `gdpr_session_id` cookie), not a verified identity — it proves "this browser chose X", not "this named person did". Clearing the cookie orphans the record. |
| **Database-level audit immutability** | Application-level only | The audit log blocks edits/deletes via Eloquent events, not DB permissions. For tamper-evidence beyond accidental change, revoke `UPDATE`/`DELETE` on the table at the database level or add a hash chain. The erasure path intentionally bypasses the guard. |

## "Required" cookies and consent

The banner can include `required` cookie types. Strictly-necessary cookies are **exempt from consent** under
ePrivacy — their lawful basis is necessity, not consent. Treat a `required` cookie type as an
*acknowledgement that necessary cookies are used*, not as consent you rely on. Do not present optional,
consent-based cookies as required.
