<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Selli\LaravelGdprConsentDatabase\Database\Factories\ConsentTypeFactory;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $required
 * @property bool $active
 * @property string $category
 * @property string|null $legal_basis
 * @property string|null $purpose
 * @property string|null $data_controller
 * @property string|null $policy_url
 * @property string|null $policy_text_hash
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
        'legal_basis',
        'purpose',
        'data_controller',
        'policy_url',
        'policy_text_hash',
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
     * Create a new version of this consent type.
     *
     * The slug is stable across versions (it identifies the consent-type group). The currently
     * active version(s) of the group are deactivated and a brand-new, active version row is created.
     * The whole operation is transactional so the "single active version per slug" invariant holds.
     *
     * @param  array<string, mixed>  $attributes  Attributes to override on the new version.
     */
    public function createNewVersion(array $attributes = []): static
    {
        return DB::transaction(function () use ($attributes): static {
            $nextVersion = $this->nextVersionNumber();

            // Deactivate every currently-active version of this group.
            static::query()
                ->where('slug', $this->slug)
                ->where('active', true)
                ->update(['active' => false, 'effective_until' => now()]);

            /** @var static $newConsentType */
            $newConsentType = $this->replicate();
            $newConsentType->fill(array_merge([
                'version' => $nextVersion,
                'active' => true,
                'effective_from' => now(),
                'effective_until' => null,
            ], $attributes));
            $newConsentType->save();

            return $newConsentType;
        });
    }

    /**
     * Compute the next minor version number for this consent-type group.
     *
     * Derived from the highest existing version sharing this slug (not just `$this`), so it is
     * robust to being called on an older version and never collides with an existing one.
     * Non-numeric segments are treated as 0. Examples within a group:
     * {"1.0"} -> "1.1", {"1.0","1.1"} -> "1.2", {"2.4"} -> "2.5", {"3.0.7"} -> "3.1".
     */
    public function nextVersionNumber(): string
    {
        $maxMajor = 0;
        $maxMinor = 0;

        foreach (static::query()->where('slug', $this->slug)->get() as $sibling) {
            $parts = explode('.', $sibling->version);
            $major = (int) $parts[0];
            $minor = (int) ($parts[1] ?? '0');

            if ($major > $maxMajor || ($major === $maxMajor && $minor > $maxMinor)) {
                $maxMajor = $major;
                $maxMinor = $minor;
            }
        }

        $maxMajor = max($maxMajor, 1);

        return $maxMajor.'.'.($maxMinor + 1);
    }

    /**
     * Get the current (active) version of this consent type's group, by slug.
     */
    public function currentVersion(): ?ConsentType
    {
        return static::query()
            ->where('slug', $this->slug)
            ->where('active', true)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
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
     * Get every active (current-version) consent type in the cookie category.
     *
     * @return Collection<int, static>
     */
    public static function cookies(): Collection
    {
        return static::query()
            ->where('category', 'cookie')
            ->where('active', true)
            ->get();
    }
}
