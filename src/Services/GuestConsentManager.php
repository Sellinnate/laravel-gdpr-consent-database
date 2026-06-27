<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase\Services;

use Illuminate\Database\Eloquent\Collection;
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;
use Selli\LaravelGdprConsentDatabase\Models\GuestConsent;
use Selli\LaravelGdprConsentDatabase\Models\UserConsent;

class GuestConsentManager
{
    /**
     * Resolve (or create) the guest consent record for the given session/technical cookie code.
     */
    public function getGuestConsent(?string $sessionId = null): GuestConsent
    {
        return GuestConsent::findOrCreateForSession($sessionId);
    }

    /**
     * Determine whether the guest has an active consent for the given type.
     */
    public function hasConsent(string $consentTypeSlug, ?string $sessionId = null, bool $checkVersion = false): bool
    {
        return $this->getGuestConsent($sessionId)->hasConsent($consentTypeSlug, $checkVersion);
    }

    /**
     * Grant consent for the guest.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function giveConsent(string $consentTypeSlug, array $metadata = [], ?int $validityMonths = null, ?string $sessionId = null): UserConsent
    {
        return $this->getGuestConsent($sessionId)->giveConsent($consentTypeSlug, $metadata, $validityMonths);
    }

    /**
     * Revoke a guest consent. Returns true when at least one record was revoked.
     */
    public function revokeConsent(string $consentTypeSlug, ?string $sessionId = null): bool
    {
        return $this->getGuestConsent($sessionId)->revokeConsent($consentTypeSlug) > 0;
    }

    /**
     * Get the guest's active consents.
     *
     * @return Collection<int, UserConsent>
     */
    public function getActiveConsents(?string $sessionId = null): Collection
    {
        return $this->getGuestConsent($sessionId)->activeConsents();
    }

    /**
     * Get the required consent types the guest is still missing.
     *
     * @return Collection<int, ConsentType>
     */
    public function getMissingRequiredConsents(?string $sessionId = null, bool $checkVersion = false): Collection
    {
        return $this->getGuestConsent($sessionId)->getMissingRequiredConsents($checkVersion);
    }

    /**
     * Determine whether the guest holds every required consent.
     */
    public function hasAllRequiredConsents(?string $sessionId = null, bool $checkVersion = false): bool
    {
        return $this->getGuestConsent($sessionId)->hasAllRequiredConsents($checkVersion);
    }
}
