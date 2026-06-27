# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| 2.x | ✅ |
| 1.x | ❌ |

## Reporting a Vulnerability

If you discover a security vulnerability, please **do not open a public issue**. Instead, report it
privately so it can be addressed before disclosure:

- Use GitHub's [private vulnerability reporting](https://github.com/sellinnate/laravel-gdpr-consent-database/security/advisories/new), or
- email the maintainer at `calabresefilippo@gmail.com`.

Please include:

- a description of the vulnerability and its impact;
- steps to reproduce (a failing test or proof-of-concept is ideal);
- the affected version(s).

You can expect an acknowledgement within a few business days. Once the issue is confirmed, a fix will be
prepared and released, and you will be credited (unless you prefer to remain anonymous).

## Scope notes

This package handles **personal data** (consent records, IP addresses, audit trails). Of particular interest:

- the immutability guarantees of the audit trail (`consent_audit_logs`);
- the anonymisation / erasure path (`ConsentAnonymizer`);
- the cookie banner endpoints and their CSRF protection.
