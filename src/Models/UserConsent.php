<?php

namespace Selli\LaravelGdprConsentDatabase\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'granted',
        'granted_at',
        'revoked_at',
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
        'metadata' => 'json',
    ];

    /**
     * Get the consent type that owns the user consent.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function consentType(): BelongsTo
    {
        return $this->belongsTo(ConsentType::class);
    }

    /**
     * Get the parent consentable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
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
            ->whereNull('revoked_at');
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
}
