<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase\Services;

use Illuminate\Database\Eloquent\Model;
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

        return [
            'subject' => ['type' => $consentableType, 'id' => $id],
            'consents' => $consents,
            'audit_trail' => $audit,
        ];
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
