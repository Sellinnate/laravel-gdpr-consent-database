---
title: "Data Subject Rights"
description: "Access, portability and erasure of consent data."
---

# Data Subject Rights

## Right of access & portability (Art. 15 / Art. 20)

A subject's consents and audit trail are plain Eloquent models, so producing an export is straightforward:

```php
$export = [
    'consents' => $user->consents()->with('consentType')->get()->toArray(),
    'audit'    => $user->consentAuditLogs()->get()->toArray(),
];

return response()->json($export);
```

You can shape this into JSON, CSV or a PDF as your access/portability request workflow requires.

## Right to erasure (Art. 17)

Erasure conflicts with the duty to demonstrate consent (Art. 7(1)): you must remove personal data, yet keep
proof. The package resolves this by **pseudonymising** the subject.

```php
// On the model
$result = $user->anonymizeConsents();
// [ 'token' => 'anon_…', 'consents' => 3, 'audit_logs' => 7 ]
```

```bash
# Or via Artisan
php artisan gdpr:anonymize-subject "App\Models\User" 42
```

What anonymisation does:

- replaces the subject identifier with an **irreversible random token** (the pseudonym);
- **scrubs** `ip_address`, `user_agent` and `metadata` from `user_consents`, `consent_audit_logs` and (for
  guests) the `guest_consents` row;
- **preserves** the action, version and policy snapshot under the pseudonym as proof;
- writes an immutable `anonymized` marker entry.

After anonymisation the original subject has no retrievable consent records, and the remaining data can no
longer be linked back to the natural person.

::: callout warning "Erasing the subject model is your responsibility"
The package erases the consent data it owns. Deleting the underlying `User` (or other subject) record, and
any PII elsewhere in your app, remains your responsibility.
:::

::: callout note "Metadata is cleared"
Because callers may store personal data in consent `metadata`, anonymisation clears it. If you rely on
non-personal metadata for analytics, capture it elsewhere before erasure.
:::

## Right to withdraw consent (Art. 7(3))

Withdrawal is a first-class operation and must be as easy as granting:

```php
$user->revokeConsent('marketing-emails');
```

For cookies, the banner's **Reject All** and **Save Preferences** provide one-click withdrawal.
