<?php

namespace Selli\LaravelGdprConsentDatabase\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Selli\LaravelGdprConsentDatabase\Models\UserConsent;

class ConsentType extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'required',
        'active',
        'metadata',
        'version',
        'validity_months',
        'effective_from',
        'effective_until',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'required' => 'boolean',
        'active' => 'boolean',
        'metadata' => 'json',
        'effective_from' => 'datetime',
        'effective_until' => 'datetime',
    ];

    /**
     * Get the user consents for this consent type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userConsents(): HasMany
    {
        return $this->hasMany(UserConsent::class);
    }
    
    /**
     * Check if this consent type is currently effective.
     *
     * @return bool
     */
    public function isEffective(): bool
    {
        $now = now();
        
        if ($this->effective_from && $now->lt($this->effective_from)) {
            return false;
        }
        
        if ($this->effective_until && $now->gt($this->effective_until)) {
            return false;
        }
        
        return $this->active;
    }
    
    /**
     * Create a new version of this consent type.
     *
     * @param array $attributes
     * @return \Selli\LaravelGdprConsentDatabase\Models\ConsentType
     */
    public function createNewVersion(array $attributes = []): ConsentType
    {
        // Set the current version as inactive
        $this->active = false;
        $this->effective_until = now();
        $this->save();
        
        // Create a new version with incremented version number
        $versionParts = explode('.', $this->version);
        $minorVersion = (int) $versionParts[1] + 1;
        $newVersion = $versionParts[0] . '.' . $minorVersion;
        
        // Generate a unique slug by appending the version
        $uniqueSlug = $this->slug . '-v' . str_replace('.', '-', $newVersion);
        
        $newConsentType = $this->replicate(['slug'])->fill([
            'slug' => $uniqueSlug,
            'version' => $newVersion,
            'active' => true,
            'effective_from' => now(),
            'effective_until' => null,
        ]);
        
        // Apply any additional attributes
        $newConsentType->fill($attributes);
        $newConsentType->save();
        
        return $newConsentType;
    }
    
    /**
     * Calculate the expiration date based on validity months.
     *
     * @param \Carbon\Carbon|null $from
     * @return \Carbon\Carbon|null
     */
    public function calculateExpirationDate($from = null)
    {
        if (!$this->validity_months) {
            return null;
        }
        
        $from = $from ?: now();
        return $from->copy()->addMonths($this->validity_months);
    }
}
