<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Selli\LaravelGdprConsentDatabase\Models\ConsentAuditLog;

/**
 * Reconciles the right to erasure (GDPR Art. 17) with the duty to demonstrate consent (Art. 7(1)).
 *
 * Instead of deleting consent records — which would destroy the legal proof — a subject is
 * *pseudonymised*: their identifier is replaced with an irreversible token and all directly
 * identifying data (IP address, user agent) is scrubbed, while the proof of what was consented to
 * and when is preserved under the pseudonym.
 */
class ConsentAnonymizer
{
    /**
     * Anonymise every consent record belonging to a subject.
     *
     * @param  string  $consentableType  The stored polymorphic type (morph alias or class name).
     * @param  int|string  $consentableId  The subject identifier.
     * @param  string|null  $token  Optional pseudonym; a random one is generated when omitted.
     * @return array{token: string, consents: int, audit_logs: int}
     */
    public function anonymize(string $consentableType, int|string $consentableId, ?string $token = null): array
    {
        $token ??= 'anon_'.bin2hex(random_bytes(16));
        $id = (string) $consentableId;

        return DB::transaction(function () use ($consentableType, $id, $token): array {
            // Bypass the audit log's Eloquent immutability guard intentionally: erasure is a legal
            // obligation that overrides tamper-protection, and this is the single controlled path
            // allowed to scrub identifying data while keeping the proof intact. `metadata` is also
            // cleared because callers may have stored personal data in it.
            $consents = DB::table('user_consents')
                ->where('consentable_type', $consentableType)
                ->where('consentable_id', $id)
                ->update([
                    'consentable_id' => $token,
                    'ip_address' => null,
                    'user_agent' => null,
                    'metadata' => null,
                ]);

            $auditLogs = DB::table('consent_audit_logs')
                ->where('consentable_type', $consentableType)
                ->where('consentable_id', $id)
                ->update([
                    'consentable_id' => $token,
                    'ip_address' => null,
                    'user_agent' => null,
                    'metadata' => null,
                ]);

            // The subject may itself be a GuestConsent, whose own row holds identifying data AND is
            // keyed by `session_id` (the live technical-cookie value — itself an identifier). Scrub
            // the data and rotate the key to the pseudonym so no residual identifier remains and the
            // row stays consistently linked to the (already re-keyed) child records.
            DB::table('guest_consents')
                ->where('session_id', $id)
                ->update([
                    'session_id' => $token,
                    'ip_address' => null,
                    'user_agent' => null,
                    'metadata' => null,
                ]);

            // Record an immutable marker (under the pseudonym) that anonymisation took place.
            ConsentAuditLog::create([
                'consentable_type' => $consentableType,
                'consentable_id' => $token,
                'consent_type_id' => null,
                'consent_type_slug' => null,
                'consent_version' => null,
                'action' => ConsentAuditLog::ACTION_ANONYMIZED,
                'occurred_at' => now(),
                'ip_address' => null,
                'user_agent' => null,
                'metadata' => [
                    'consents_anonymized' => $consents,
                    'audit_logs_anonymized' => $auditLogs,
                ],
            ]);

            return [
                'token' => $token,
                'consents' => $consents,
                'audit_logs' => $auditLogs,
            ];
        });
    }

    /**
     * Anonymise a concrete model instance using its morph type and key.
     *
     * @return array{token: string, consents: int, audit_logs: int}
     */
    public function anonymizeModel(Model $subject, ?string $token = null): array
    {
        $key = $subject->getKey();

        return $this->anonymize($subject->getMorphClass(), is_scalar($key) ? (string) $key : '', $token);
    }
}
