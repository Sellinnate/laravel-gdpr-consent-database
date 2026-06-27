<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Selli\LaravelGdprConsentDatabase\Models\ConsentAuditLog;
use Selli\LaravelGdprConsentDatabase\Models\UserConsent;

/**
 * Produces a machine-readable export of a subject's consent data, supporting the right of access
 * (GDPR Art. 15) and data portability (Art. 20).
 */
class ConsentExporter
{
    /**
     * Build a structured export of every consent and audit-trail entry for a subject.
     *
     * @return array<string, mixed>
     */
    public function export(string $consentableType, int|string $consentableId): array
    {
        $id = (string) $consentableId;

        $consents = UserConsent::query()
            ->where('consentable_type', $consentableType)
            ->where('consentable_id', $id)
            ->with('consentType')
            ->orderBy('granted_at')
            ->get()
            ->map(fn (UserConsent $consent): array => [
                'consent_type' => $consent->consentType?->slug,
                'consent_name' => $consent->consentType?->name,
                'purpose' => $consent->consentType?->purpose,
                'legal_basis' => $consent->consentType?->legal_basis,
                'data_controller' => $consent->consentType?->data_controller,
                'consent_version' => $consent->consent_version,
                'granted' => $consent->granted,
                'granted_at' => $consent->granted_at?->toIso8601String(),
                'revoked_at' => $consent->revoked_at?->toIso8601String(),
                'expires_at' => $consent->expires_at?->toIso8601String(),
                'ip_address' => $consent->ip_address,
                'user_agent' => $consent->user_agent,
                'metadata' => $consent->metadata,
            ])
            ->all();

        $audit = ConsentAuditLog::query()
            ->where('consentable_type', $consentableType)
            ->where('consentable_id', $id)
            ->orderBy('occurred_at')
            ->get()
            ->map(fn (ConsentAuditLog $log): array => [
                'action' => $log->action,
                'consent_type' => $log->consent_type_slug,
                'consent_version' => $log->consent_version,
                'occurred_at' => $log->occurred_at->toIso8601String(),
                'policy_url' => $log->policy_url,
                'policy_text_hash' => $log->policy_text_hash,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'metadata' => $log->metadata,
            ])
            ->all();

        $export = [
            'subject' => ['type' => $consentableType, 'id' => $id],
            'consents' => $consents,
            'audit_trail' => $audit,
        ];

        // When the subject is a guest, its own row (keyed by the technical-cookie session id) holds
        // personal data the controller must also disclose for an Art. 15 access request.
        $guest = DB::table('guest_consents')->where('session_id', $id)->first();

        if ($guest !== null) {
            $metadata = $guest->metadata ?? null;

            $export['guest'] = [
                'session_id' => $guest->session_id,
                'ip_address' => $guest->ip_address,
                'user_agent' => $guest->user_agent,
                'metadata' => is_string($metadata) ? json_decode($metadata, true) : $metadata,
            ];
        }

        return $export;
    }

    /**
     * Build an export for a concrete model instance.
     *
     * @return array<string, mixed>
     */
    public function exportModel(Model $subject): array
    {
        $key = $subject->getKey();

        return $this->export($subject->getMorphClass(), is_scalar($key) ? (string) $key : '');
    }

    /**
     * Export a subject as a JSON string.
     */
    public function toJson(string $consentableType, int|string $consentableId, int $flags = JSON_PRETTY_PRINT): string
    {
        return (string) json_encode($this->export($consentableType, $consentableId), $flags | JSON_THROW_ON_ERROR);
    }
}
