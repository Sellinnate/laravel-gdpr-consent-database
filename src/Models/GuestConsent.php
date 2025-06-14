<?php

namespace Selli\LaravelGdprConsentDatabase\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Selli\LaravelGdprConsentDatabase\Traits\HasGdprConsents;

class GuestConsent extends Model
{
    use HasFactory, HasGdprConsents;

    protected $fillable = [
        'session_id',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'json',
    ];

    public function getKeyName()
    {
        return 'session_id';
    }

    public function getIncrementing()
    {
        return false;
    }

    public function getKeyType()
    {
        return 'string';
    }

    public static function findOrCreateForSession($sessionId = null)
    {
        $sessionId = $sessionId ?: session()->getId();

        return static::firstOrCreate([
            'session_id' => $sessionId,
        ], [
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => [],
        ]);
    }
}
