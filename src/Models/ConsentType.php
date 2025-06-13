<?php

namespace Selli\LaravelGdprConsentDatabase\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

    /**
     * Get the user consents for this consent type.
     */
    public function userConsents(): HasMany
    {
        return $this->hasMany(UserConsent::class);
    }
}
