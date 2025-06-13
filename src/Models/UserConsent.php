<?php

namespace Selli\LaravelGdprConsentDatabase\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserConsent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'consent_type_id',
        'consent_version',
        'granted',
        'granted_at',
        'revoked_at',
        'expires_at',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'granted' => 'boolean',
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'json',
    ];

    /**
     * Get the consent type that owns the user consent.
     */
    public function consentType(): BelongsTo
    {
        return $this->belongsTo(ConsentType::class);
    }

    /**
     * Get the parent consentable model.
     */
    public function consentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include active consents.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('granted', true)
            ->whereNull('revoked_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope a query to only include revoked consents.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRevoked($query)
    {
        return $query->whereNotNull('revoked_at');
    }

    /**
     * Scope a query to only include expired consents.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->whereNull('revoked_at');
    }

    /**
     * Check if the consent is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && now()->gt($this->expires_at);
    }

    /**
     * Check if the consent is for the current version of the consent type.
     */
    public function isCurrentVersion(): bool
    {
        // Carica la relazione se non è già caricata
        if (! $this->relationLoaded('consentType')) {
            $this->load('consentType');
        }

        // Trova la versione attiva corrente del tipo di consenso
        $currentVersion = ConsentType::where('slug', 'like', $this->consentType->slug.'%')
            ->where('active', true)
            ->first();

        if (! $currentVersion) {
            return false;
        }

        return $this->consent_version === $currentVersion->version;
    }

    /**
     * Check if the consent needs renewal due to version change.
     */
    public function needsRenewal(): bool
    {
        return $this->isExpired() || ! $this->isCurrentVersion();
    }

    /**
     * Get days until expiration.
     */
    public function daysUntilExpiration(): ?int
    {
        if (! $this->expires_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->expires_at, false));
    }
}
