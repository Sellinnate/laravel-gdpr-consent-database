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
        if (!$sessionId) {
            // Avvia la sessione se non è già stata avviata
            if (!session()->isStarted()) {
                session()->start();
            }
            
            // Usa l'ID di sessione corrente o crea un ID di sessione persistente nei cookie
            $sessionId = request()->cookie('gdpr_session_id') ?: session()->getId();
            
            // Assicurati che l'ID di sessione sia salvato in un cookie persistente
            if (!request()->cookie('gdpr_session_id')) {
                cookie()->queue('gdpr_session_id', $sessionId, 43200); // 30 giorni
            }
        }

        return static::firstOrCreate([
            'session_id' => $sessionId,
        ], [
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => [],
        ]);
    }
}
