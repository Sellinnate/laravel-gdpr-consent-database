<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Selli\LaravelGdprConsentDatabase\Events\ConsentGranted;
use Selli\LaravelGdprConsentDatabase\Events\ConsentRenewed;
use Selli\LaravelGdprConsentDatabase\Events\ConsentRevoked;
use Selli\LaravelGdprConsentDatabase\Models\ConsentAuditLog;
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;
use Selli\LaravelGdprConsentDatabase\Models\UserConsent;
use Selli\LaravelGdprConsentDatabase\Services\ConsentAnonymizer;

/**
 * Adds GDPR consent management to an Eloquent model.
 *
 * @phpstan-require-extends Model
 */
trait HasGdprConsents
{
    /**
     * Get all consents for this model.
     *
     * @return MorphMany<UserConsent, $this>
     */
    public function consents(): MorphMany
    {
        return $this->morphMany(UserConsent::class, 'consentable');
    }

    /**
     * Get the immutable audit trail of every consent action for this model.
     *
     * @return MorphMany<ConsentAuditLog, $this>
     */
    public function consentAuditLogs(): MorphMany
    {
        return $this->morphMany(ConsentAuditLog::class, 'consentable')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');
    }

    /**
     * Get the active (granted, not revoked, not expired) consents for this model.
     *
     * @return Collection<int, UserConsent>
     */
    public function activeConsents(): Collection
    {
        return $this->consents()->active()->get();
    }

    /**
     * Get the expired consents for this model.
     *
     * @return Collection<int, UserConsent>
     */
    public function expiredConsents(): Collection
    {
        return $this->consents()->expired()->get();
    }

    /**
     * Get the active consents that need renewal (expired or tied to an outdated version).
     *
     * @return Collection<int, UserConsent>
     */
    public function consentsNeedingRenewal(): Collection
    {
        $activeConsents = $this->activeConsents();
        $activeConsents->load('consentType');

        return $activeConsents->filter(function (UserConsent $consent): bool {
            if (! $consent->consentType) {
                return false;
            }

            $current = $consent->consentType->currentVersion();

            // Nothing to renew towards when the group has no active version any more.
            if (! $current) {
                return false;
            }

            // Needs renewal when expired or when a newer effective version exists.
            return $consent->isExpired() || $consent->consent_version !== $current->version;
        })->values();
    }

    /**
     * Determine whether the model has an active consent for the given type.
     *
     * Matching is by consent-type *group* (slug): a consent granted on any version of the group
     * counts. When $checkVersion is true, the held consent must also be on the current version.
     *
     * @param  string|int  $consentTypeId  A consent type slug or primary key.
     * @param  bool  $checkVersion  Whether the consent must match the current version.
     */
    public function hasConsent(string|int $consentTypeId, bool $checkVersion = false): bool
    {
        $consentType = $this->resolveConsentType($consentTypeId);

        if (! $consentType) {
            return false;
        }

        $consent = $this->consents()
            ->whereIn('consent_type_id', $this->groupVersionIds($consentType))
            ->active()
            ->latest('granted_at')
            ->first();

        if (! $consent) {
            return false;
        }

        if ($checkVersion && ! $consent->isCurrentVersion()) {
            return false;
        }

        return true;
    }

    /**
     * Grant consent for the current version of the given type, superseding any previous active
     * consent for the same group.
     *
     * @param  string|int  $consentTypeId  A consent type slug or primary key.
     * @param  array<string, mixed>  $metadata  Extra data to persist with the consent.
     * @param  int|null  $validityMonths  Overrides the consent type's default validity period.
     */
    public function giveConsent(string|int $consentTypeId, array $metadata = [], ?int $validityMonths = null): UserConsent
    {
        $consentType = $this->resolveConsentTypeOrFail($consentTypeId);

        // Refuse to record fresh consent for a retired / not-yet-effective purpose: there must be
        // a currently effective version to consent to (GDPR: consent is purpose-specific).
        if (! $consentType->isEffective()) {
            throw (new ModelNotFoundException)->setModel(ConsentType::class, [$consentTypeId]);
        }

        $consent = $this->persistConsent($consentType, $metadata, $validityMonths);

        ConsentGranted::dispatch($this, $consent);

        return $consent;
    }

    /**
     * Persist a fresh consent for the current version, superseding the group. No event is dispatched.
     *
     * @param  array<string, mixed>  $metadata
     * @param  string  $auditAction  The audit action recorded for the new consent (granted / renewed).
     * @param  bool  $auditSupersede  Whether to record a revoke audit entry for superseded consents.
     */
    protected function persistConsent(
        ConsentType $consentType,
        array $metadata,
        ?int $validityMonths,
        string $auditAction = ConsentAuditLog::ACTION_GRANTED,
        bool $auditSupersede = true,
    ): UserConsent {
        return DB::transaction(function () use ($consentType, $metadata, $validityMonths, $auditAction, $auditSupersede): UserConsent {
            // Supersede any previous active consent for this group (across all versions),
            // enforcing a single active consent per group.
            $this->revokeConsentGroup($consentType, $auditSupersede);

            $expiresAt = null;
            if ($validityMonths !== null) {
                $expiresAt = now()->addMonths($validityMonths);
            } elseif ($consentType->validity_months) {
                $expiresAt = $consentType->calculateExpirationDate();
            }

            $consent = $this->consents()->create([
                'consent_type_id' => $consentType->id,
                'consent_version' => $consentType->version,
                'granted' => true,
                'granted_at' => now(),
                'expires_at' => $expiresAt,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => $metadata,
            ]);

            $consent->setRelation('consentType', $consentType);
            $this->recordConsentAudit($auditAction, $consent);

            return $consent;
        });
    }

    /**
     * Revoke every active consent for the given type's group (any version).
     *
     * Returns 0 when the consent type cannot be resolved (no exception is thrown), so it is safe
     * to call with arbitrary slugs from user input.
     *
     * @param  string|int  $consentTypeId  A consent type slug or primary key.
     * @return int The number of consent records that were revoked.
     */
    public function revokeConsent(string|int $consentTypeId): int
    {
        $consentType = $this->resolveConsentType($consentTypeId);

        if (! $consentType) {
            return 0;
        }

        $revoked = DB::transaction(fn (): Collection => $this->revokeConsentGroup($consentType));

        foreach ($revoked as $consent) {
            ConsentRevoked::dispatch($this, $consent);
        }

        return $revoked->count();
    }

    /**
     * Renew a consent by superseding the active records with a fresh consent for the current version.
     *
     * @param  string|int  $consentTypeId  A consent type slug or primary key.
     * @param  array<string, mixed>  $metadata  Extra data to persist; falls back to the previous metadata.
     */
    public function renewConsent(string|int $consentTypeId, array $metadata = []): ?UserConsent
    {
        $consentType = $this->resolveConsentType($consentTypeId);

        if (! $consentType || ! $consentType->isEffective()) {
            return null;
        }

        $newConsent = DB::transaction(function () use ($consentType, $metadata): UserConsent {
            if ($metadata === []) {
                $previous = $this->consents()
                    ->whereIn('consent_type_id', $this->groupVersionIds($consentType))
                    ->active()
                    ->latest('granted_at')
                    ->first();

                if ($previous && $previous->metadata) {
                    $metadata = $previous->metadata;
                }
            }

            // Supersede the whole group and record the renewal (a single 'renewed' audit entry,
            // not a revoke+grant pair, so the trail reads as a renewal rather than a withdrawal).
            $consent = $this->persistConsent($consentType, $metadata, null, ConsentAuditLog::ACTION_RENEWED, false);

            $this->unsetRelation('consents');

            return $consent;
        });

        ConsentRenewed::dispatch($this, $newConsent);

        return $newConsent;
    }

    /**
     * Revoke every active consent belonging to the given type's group.
     *
     * @param  bool  $recordAudit  Whether to write a revoke audit entry for each consent. Set false
     *                             when the revocation is part of a renewal (recorded as 'renewed').
     * @return Collection<int, UserConsent> The consents that were revoked.
     */
    protected function revokeConsentGroup(ConsentType $consentType, bool $recordAudit = true): Collection
    {
        $ids = $this->groupVersionIds($consentType);

        // lockForUpdate narrows the race window for the "single active consent per group" invariant
        // on engines that support row locks (MySQL/Postgres); it is a harmless no-op on SQLite.
        // Always call inside a transaction (giveConsent/renewConsent/revokeConsent already wrap this).
        $activeConsents = $this->consents()
            ->whereIn('consent_type_id', $ids)
            ->active()
            ->lockForUpdate()
            ->get();

        if ($activeConsents->isEmpty()) {
            return $activeConsents;
        }

        if ($recordAudit) {
            foreach ($activeConsents as $activeConsent) {
                $this->recordConsentAudit(ConsentAuditLog::ACTION_REVOKED, $activeConsent);
            }
        }

        $this->consents()
            ->whereIn('consent_type_id', $ids)
            ->active()
            ->update([
                'revoked_at' => now(),
                'granted' => false,
            ]);

        return $activeConsents;
    }

    /**
     * Anonymise (pseudonymise) every consent record of this model for a GDPR Art. 17 erasure
     * request. Identifying data is scrubbed while the audit proof is preserved under a pseudonym.
     *
     * @return array{token: string, consents: int, audit_logs: int}
     */
    public function anonymizeConsents(?string $token = null): array
    {
        return app(ConsentAnonymizer::class)->anonymizeModel($this, $token);
    }

    /**
     * Append an immutable audit-trail entry for a consent action.
     */
    protected function recordConsentAudit(string $action, UserConsent $consent): void
    {
        $consent->loadMissing('consentType');
        $consentType = $consent->consentType;

        // Grant/renew entries carry the consent metadata; a revoke entry must not inherit the
        // grant's context, which would misattribute it to the revocation event.
        $metadata = in_array($action, [ConsentAuditLog::ACTION_GRANTED, ConsentAuditLog::ACTION_RENEWED], true)
            ? $consent->metadata
            : null;

        // Created through the polymorphic relation so consentable_type/id are set from this model.
        $this->consentAuditLogs()->create([
            'consent_type_id' => $consent->consent_type_id,
            'consent_type_slug' => $consentType?->slug,
            'consent_version' => $consent->consent_version,
            'action' => $action,
            'occurred_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'policy_url' => $consentType?->policy_url,
            'policy_text_hash' => $consentType?->policy_text_hash,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get every consent-type version id sharing the given type's slug (its group).
     *
     * @return array<int, int>
     */
    protected function groupVersionIds(ConsentType $consentType): array
    {
        return ConsentType::query()
            ->where('slug', $consentType->slug)
            ->get()
            ->map(fn (ConsentType $type): int => $type->id)
            ->all();
    }

    /**
     * Get the required, active consent types the model is currently missing.
     *
     * @param  bool  $checkVersion  Whether a consent on an outdated version counts as missing.
     * @return Collection<int, ConsentType>
     */
    public function getMissingRequiredConsents(bool $checkVersion = false): Collection
    {
        $requiredConsentTypes = ConsentType::query()
            ->where('required', true)
            ->where('active', true)
            ->get();

        $activeConsents = $this->activeConsents();
        $activeConsents->load('consentType');

        return $requiredConsentTypes->filter(function (ConsentType $consentType) use ($activeConsents, $checkVersion): bool {
            // Match by slug group, not by primary key: a consent granted on a previous version
            // still satisfies the requirement when we are not checking the version.
            $consent = $activeConsents->first(
                fn (UserConsent $c): bool => $c->consentType !== null && $c->consentType->slug === $consentType->slug
            );

            if (! $consent) {
                return true;
            }

            return $checkVersion && ! $consent->isCurrentVersion();
        })->values();
    }

    /**
     * Determine whether the model holds every required, active consent.
     *
     * @param  bool  $checkVersion  Whether each consent must match the current version.
     */
    public function hasAllRequiredConsents(bool $checkVersion = false): bool
    {
        return $this->getMissingRequiredConsents($checkVersion)->isEmpty();
    }

    /**
     * Get the active consents that will expire within the given number of days.
     *
     * @return Collection<int, UserConsent>
     */
    public function getConsentsExpiringWithinDays(int $days = 30): Collection
    {
        $now = now();

        return $this->consents()
            ->active()
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', $now)
            ->where('expires_at', '<=', $now->copy()->addDays($days))
            ->get();
    }

    /**
     * Resolve a slug or id to a ConsentType model, or null when it cannot be found.
     */
    protected function resolveConsentType(string|int $consentTypeId): ?ConsentType
    {
        if (! is_string($consentTypeId)) {
            return ConsentType::query()->find($consentTypeId);
        }

        // A slug identifies a consent-type group. Resolve it to the current (active) version;
        // fall back to the most recent historical version when no active one exists (e.g. a
        // retired purpose still referenced for read-only checks). No LIKE matching is involved.
        return ConsentType::query()
            ->where('slug', $consentTypeId)
            ->orderByDesc('active')
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Resolve a slug or id to a ConsentType model, failing when it cannot be found.
     *
     *
     * @throws ModelNotFoundException<ConsentType>
     */
    protected function resolveConsentTypeOrFail(string|int $consentTypeId): ConsentType
    {
        $consentType = $this->resolveConsentType($consentTypeId);

        if (! $consentType) {
            throw (new ModelNotFoundException)
                ->setModel(ConsentType::class, [$consentTypeId]);
        }

        return $consentType;
    }
}
