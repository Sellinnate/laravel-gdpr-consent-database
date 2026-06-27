<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Selli\LaravelGdprConsentDatabase\Database\Factories\GuestConsentFactory;
use Selli\LaravelGdprConsentDatabase\Traits\HasGdprConsents;

/**
 * @property string $session_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class GuestConsent extends Model
{
    /** @use HasFactory<GuestConsentFactory> */
    use HasFactory;

    use HasGdprConsents;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'session_id',
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
            'metadata' => 'array',
        ];
    }

    public function getKeyName(): string
    {
        return 'session_id';
    }

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    /**
     * Resolve the guest consent record for the given session id, creating it when missing.
     *
     * When no session id is supplied, the current session id (persisted in a `gdpr_session_id`
     * cookie for 30 days) is used.
     */
    public static function findOrCreateForSession(?string $sessionId = null): self
    {
        if ($sessionId === null || $sessionId === '') {
            if (! session()->isStarted()) {
                session()->start();
            }

            $cookieValue = request()->cookie('gdpr_session_id');
            $sessionId = is_string($cookieValue) && $cookieValue !== '' ? $cookieValue : session()->getId();

            if (! is_string($cookieValue) || $cookieValue === '') {
                cookie()->queue('gdpr_session_id', $sessionId, 43200); // 30 days
            }
        }

        return static::firstOrCreate(
            ['session_id' => $sessionId],
            [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => [],
            ]
        );
    }
}
