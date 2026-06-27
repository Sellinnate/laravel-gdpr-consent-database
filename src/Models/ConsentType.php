<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Selli\LaravelGdprConsentDatabase\Database\Factories\ConsentTypeFactory;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $required
 * @property bool $active
 * @property string $category
 * @property array<string, mixed>|null $metadata
 * @property string $version
 * @property int|null $validity_months
 * @property Carbon|null $effective_from
 * @property Carbon|null $effective_until
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ConsentType extends Model
{
    /** @use HasFactory<ConsentTypeFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'required',
        'active',
        'category',
        'metadata',
        'version',
        'validity_months',
        'effective_from',
        'effective_until',
    ];

    /**
     * Get the attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'active' => 'boolean',
            'validity_months' => 'integer',
            'metadata' => 'array',
            'effective_from' => 'datetime',
            'effective_until' => 'datetime',
        ];
    }

    /**
     * Get the user consents recorded against this consent type.
     *
     * @return HasMany<UserConsent, $this>
     */
    public function userConsents(): HasMany
    {
        return $this->hasMany(UserConsent::class);
    }

    /**
     * Determine whether this consent type is currently effective.
     */
    public function isEffective(): bool
    {
        $now = now();

        if ($this->effective_from && $now->lessThan($this->effective_from)) {
            return false;
        }

        if ($this->effective_until && $now->greaterThan($this->effective_until)) {
            return false;
        }

        return $this->active;
    }

    /**
     * Create a new version of this consent type, deactivating the current one.
     *
     * @param  array<string, mixed>  $attributes  Attributes to override on the new version.
     */
    public function createNewVersion(array $attributes = []): ConsentType
    {
        $this->active = false;
        $this->effective_until = now();
        $this->save();

        $newVersion = $this->nextVersionNumber();
        $uniqueSlug = $this->slug.'-v'.str_replace('.', '-', $newVersion);

        $newConsentType = $this->replicate(['slug'])->fill([
            'slug' => $uniqueSlug,
            'version' => $newVersion,
            'active' => true,
            'effective_from' => now(),
            'effective_until' => null,
        ]);

        $newConsentType->fill($attributes);
        $newConsentType->save();

        return $newConsentType;
    }

    /**
     * Compute the next minor version number, robust to missing or extra version segments.
     *
     * Examples: "1" -> "1.1", "1.0" -> "1.1", "2.4" -> "2.5", "3.0.7" -> "3.1".
     */
    public function nextVersionNumber(): string
    {
        $versionParts = explode('.', (string) $this->version);
        $major = $versionParts[0] !== '' ? $versionParts[0] : '1';
        $minor = (int) ($versionParts[1] ?? '0');

        return $major.'.'.($minor + 1);
    }

    /**
     * Calculate the expiration date based on the validity period, or null when there is none.
     */
    public function calculateExpirationDate(?Carbon $from = null): ?Carbon
    {
        if (! $this->validity_months) {
            return null;
        }

        return ($from ?? now())->copy()->addMonths($this->validity_months);
    }

    /**
     * Get every consent type in the cookie category.
     *
     * @return Collection<int, static>
     */
    public static function cookies(): Collection
    {
        return static::query()->where('category', 'cookie')->get();
    }
}
