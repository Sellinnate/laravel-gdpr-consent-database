<?php

namespace Selli\LaravelGdprConsentDatabase\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Selli\LaravelGdprConsentDatabase\Models\UserConsent;
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;

trait HasGdprConsents
{
    /**
     * Get all consents for this model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function consents(): MorphMany
    {
        return $this->morphMany(UserConsent::class, 'consentable');
    }

    /**
     * Get active consents for this model.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function activeConsents()
    {
        return $this->consents()->active()->get();
    }

    /**
     * Check if the model has given consent for a specific type.
     *
     * @param  string|int  $consentTypeId
     * @return bool
     */
    public function hasConsent($consentTypeId)
    {
        if (is_string($consentTypeId)) {
            $consentType = ConsentType::where('slug', $consentTypeId)->first();
            if (!$consentType) {
                return false;
            }
            $consentTypeId = $consentType->id;
        }

        return $this->consents()
            ->where('consent_type_id', $consentTypeId)
            ->active()
            ->exists();
    }

    /**
     * Give consent for a specific type.
     *
     * @param  string|int  $consentTypeId
     * @param  array  $metadata
     * @return \Selli\LaravelGdprConsentDatabase\Models\UserConsent
     */
    public function giveConsent($consentTypeId, array $metadata = [])
    {
        if (is_string($consentTypeId)) {
            $consentType = ConsentType::where('slug', $consentTypeId)->firstOrFail();
            $consentTypeId = $consentType->id;
        }

        // Revoca eventuali consensi precedenti per questo tipo
        $this->revokeConsent($consentTypeId);

        // Crea un nuovo consenso
        return $this->consents()->create([
            'consent_type_id' => $consentTypeId,
            'granted' => true,
            'granted_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Revoke consent for a specific type.
     *
     * @param  string|int  $consentTypeId
     * @return bool
     */
    public function revokeConsent($consentTypeId)
    {
        if (is_string($consentTypeId)) {
            $consentType = ConsentType::where('slug', $consentTypeId)->firstOrFail();
            $consentTypeId = $consentType->id;
        }

        return $this->consents()
            ->where('consent_type_id', $consentTypeId)
            ->active()
            ->update([
                'revoked_at' => now(),
                'granted' => false,
            ]);
    }

    /**
     * Get all required consent types that the model has not consented to.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMissingRequiredConsents()
    {
        $requiredConsentTypes = ConsentType::where('required', true)
            ->where('active', true)
            ->get();
        
        $missingConsents = collect();
        
        foreach ($requiredConsentTypes as $consentType) {
            if (!$this->hasConsent($consentType->id)) {
                $missingConsents->push($consentType);
            }
        }
        
        return $missingConsents;
    }

    /**
     * Check if the model has all required consents.
     *
     * @return bool
     */
    public function hasAllRequiredConsents()
    {
        return $this->getMissingRequiredConsents()->isEmpty();
    }
}
