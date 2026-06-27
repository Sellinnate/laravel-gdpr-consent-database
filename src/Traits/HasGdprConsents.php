<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;
use Selli\LaravelGdprConsentDatabase\Models\UserConsent;

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
            // If the consent type is no longer effective, there is nothing to renew.
            if (! $consent->consentType || ! $consent->consentType->isEffective()) {
                return false;
            }

            return $consent->needsRenewal();
        })->values();
    }

    /**
     * Determine whether the model has an active consent for the given type.
     *
     * @param  string|int  $consentTypeId  A consent type slug or primary key.
     * @param  bool  $checkVersion  Whether the consent must match the current version.
     */
    public function hasConsent(string|int $consentTypeId, bool $checkVersion = false): bool
    {
        $resolvedId = $this->resolveConsentTypeId($consentTypeId);

        if ($resolvedId === null) {
            return false;
        }

        $consent = $this->consents()
            ->where('consent_type_id', $resolvedId)
            ->active()
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
     * Grant consent for the given type, superseding any previous active consent.
     *
     * @param  string|int  $consentTypeId  A consent type slug or primary key.
     * @param  array<string, mixed>  $metadata  Extra data to persist with the consent.
     * @param  int|null  $validityMonths  Overrides the consent type's default validity period.
     */
    public function giveConsent(string|int $consentTypeId, array $metadata = [], ?int $validityMonths = null): UserConsent
    {
        $consentType = $this->resolveConsentTypeOrFail($consentTypeId);

        return DB::transaction(function () use ($consentType, $metadata, $validityMonths): UserConsent {
            // Supersede any previous active consent for this type.
            $this->revokeConsent($consentType->id);

            $expiresAt = null;
            if ($validityMonths !== null) {
                $expiresAt = now()->addMonths($validityMonths);
            } elseif ($consentType->validity_months) {
                $expiresAt = $consentType->calculateExpirationDate();
            }

            return $this->consents()->create([
                'consent_type_id' => $consentType->id,
                'consent_version' => $consentType->version,
                'granted' => true,
                'granted_at' => now(),
                'expires_at' => $expiresAt,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => $metadata,
            ]);
        });
    }

    /**
     * Revoke every active consent for the given type.
     *
     * @param  string|int  $consentTypeId  A consent type slug or primary key.
     * @return int The number of consent records that were revoked.
     */
    public function revokeConsent(string|int $consentTypeId): int
    {
        $resolvedId = is_string($consentTypeId)
            ? $this->resolveConsentTypeOrFail($consentTypeId)->id
            : $consentTypeId;

        return $this->consents()
            ->where('consent_type_id', $resolvedId)
            ->active()
            ->update([
                'revoked_at' => now(),
                'granted' => false,
            ]);
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

        return DB::transaction(function () use ($consentType, $metadata): UserConsent {
            $existingConsents = $this->consents()
                ->where('consent_type_id', $consentType->id)
                ->active()
                ->get();

            foreach ($existingConsents as $existingConsent) {
                $existingConsent->granted = false;
                $existingConsent->revoked_at = now();
                $existingConsent->save();

                // Preserve the previous metadata when no new metadata is supplied.
                if ($metadata === [] && $existingConsent->metadata) {
                    $metadata = $existingConsent->metadata;
                }
            }

            $newConsent = $this->giveConsent($consentType->id, $metadata);

            $this->unsetRelation('consents');

            return $newConsent;
        });
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
            $consent = $activeConsents->firstWhere('consent_type_id', $consentType->id);

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
     * Resolve a slug or id to the consent type's primary key, or null when it cannot be found.
     */
    protected function resolveConsentTypeId(string|int $consentTypeId): int|string|null
    {
        if (! is_string($consentTypeId)) {
            return $consentTypeId;
        }

        return $this->resolveConsentType($consentTypeId)?->id;
    }

    /**
     * Resolve a slug or id to a ConsentType model, or null when it cannot be found.
     */
    protected function resolveConsentType(string|int $consentTypeId): ?ConsentType
    {
        if (! is_string($consentTypeId)) {
            return ConsentType::query()->find($consentTypeId);
        }

        $consentType = ConsentType::query()->where('slug', $consentTypeId)->first();

        if ($consentType) {
            return $consentType;
        }

        // Only fall back to base-slug resolution when the input explicitly carries a version
        // suffix (e.g. "terms-v1-2"). This avoids accidentally matching a different consent
        // type whose slug merely shares a prefix (e.g. "marketing" vs "marketing-emails").
        if (preg_match('/^(?<base>.+)-v\d+-\d+$/', $consentTypeId, $matches) !== 1) {
            return null;
        }

        return ConsentType::query()
            ->where('slug', 'like', $matches['base'].'-v%')
            ->where('active', true)
            ->orderByDesc('effective_from')
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
