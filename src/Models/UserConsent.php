<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Selli\LaravelGdprConsentDatabase\Database\Factories\UserConsentFactory;

/**
 * @property int $id
 * @property string $consentable_type
 * @property string $consentable_id
 * @property int $consent_type_id
 * @property string|null $consent_version
 * @property bool $granted
 * @property Carbon|null $granted_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $expires_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ConsentType|null $consentType
 *
 * @method static \Illuminate\Database\Eloquent\Builder<UserConsent> active()
 * @method static \Illuminate\Database\Eloquent\Builder<UserConsent> revoked()
 * @method static \Illuminate\Database\Eloquent\Builder<UserConsent> expired()
 */
class UserConsent extends Model
{
    /** @use HasFactory<UserConsentFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
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
     * Get the attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'granted' => 'boolean',
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the consent type that owns the user consent.
     *
     * @return BelongsTo<ConsentType, $this>
     */
    public function consentType(): BelongsTo
    {
        return $this->belongsTo(ConsentType::class);
    }

    /**
     * Get the parent consentable model.
     *
     * @return MorphTo<Model, $this>
     */
    public function consentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include active (granted, not revoked, not expired) consents.
     *
     * @param  Builder<UserConsent>  $query
     * @return Builder<UserConsent>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('granted', true)
            ->whereNull('revoked_at')
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope a query to only include revoked consents.
     *
     * @param  Builder<UserConsent>  $query
     * @return Builder<UserConsent>
     */
    public function scopeRevoked(Builder $query): Builder
    {
        return $query->whereNotNull('revoked_at');
    }

    /**
     * Scope a query to only include expired (but not revoked) consents.
     *
     * @param  Builder<UserConsent>  $query
     * @return Builder<UserConsent>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->whereNull('revoked_at');
    }

    /**
     * Determine whether the consent has passed its expiration date.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && now()->greaterThan($this->expires_at);
    }

    /**
     * Determine whether the consent matches the current version of its consent type.
     */
    public function isCurrentVersion(): bool
    {
        if (! $this->relationLoaded('consentType')) {
            $this->load('consentType');
        }

        if (! $this->consentType) {
            return false;
        }

        $currentVersion = $this->consentType->currentVersion();

        if (! $currentVersion) {
            return false;
        }

        return $this->consent_version === $currentVersion->version;
    }

    /**
     * Determine whether the consent needs renewal (expired or tied to an outdated version).
     */
    public function needsRenewal(): bool
    {
        return $this->isExpired() || ! $this->isCurrentVersion();
    }

    /**
     * Get the number of whole days until the consent expires, or null when it never expires.
     *
     * Rounds up, so a consent expiring in less than 24 hours reports 1 (not 0). Returns 0 only
     * when the consent has already expired.
     */
    public function daysUntilExpiration(): ?int
    {
        if ($this->expires_at === null) {
            return null;
        }

        return (int) max(0, ceil(now()->diffInDays($this->expires_at, false)));
    }
}
