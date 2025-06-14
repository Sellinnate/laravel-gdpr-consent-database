<?php

namespace Selli\LaravelGdprConsentDatabase\Services;

use Selli\LaravelGdprConsentDatabase\Models\GuestConsent;

class GuestConsentManager
{
    public function getGuestConsent($sessionId = null): GuestConsent
    {
        return GuestConsent::findOrCreateForSession($sessionId);
    }

    public function hasConsent($consentTypeSlug, $sessionId = null, $checkVersion = false): bool
    {
        $guest = $this->getGuestConsent($sessionId);

        return $guest->hasConsent($consentTypeSlug, $checkVersion);
    }

    public function giveConsent($consentTypeSlug, array $metadata = [], $validityMonths = null, $sessionId = null)
    {
        $guest = $this->getGuestConsent($sessionId);

        return $guest->giveConsent($consentTypeSlug, $metadata, $validityMonths);
    }

    public function revokeConsent($consentTypeSlug, $sessionId = null): bool
    {
        $guest = $this->getGuestConsent($sessionId);

        return $guest->revokeConsent($consentTypeSlug);
    }

    public function getActiveConsents($sessionId = null)
    {
        $guest = $this->getGuestConsent($sessionId);

        return $guest->activeConsents();
    }

    public function getMissingRequiredConsents($sessionId = null, $checkVersion = false)
    {
        $guest = $this->getGuestConsent($sessionId);

        return $guest->getMissingRequiredConsents($checkVersion);
    }

    public function hasAllRequiredConsents($sessionId = null, $checkVersion = false): bool
    {
        $guest = $this->getGuestConsent($sessionId);

        return $guest->hasAllRequiredConsents($checkVersion);
    }
}
