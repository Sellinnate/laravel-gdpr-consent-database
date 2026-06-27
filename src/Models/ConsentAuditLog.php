<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Append-only audit record of a single consent action — the source of truth for GDPR Art. 7(1)
 * demonstrability.
 *
 * Immutability is enforced at the application level: any attempt to update or delete a record
 * through Eloquent throws, so ordinary application code cannot rewrite the trail. This is a guard
 * against accidental mutation, not a database-level guarantee — the only sanctioned exception is the
 * erasure path (ConsentAnonymizer), which deliberately scrubs identifying columns via the query
 * builder to satisfy Art. 17. For stronger tamper-evidence, revoke UPDATE/DELETE on the table at the
 * database level or add a per-row hash chain in your application.
 *
 * @property int $id
 * @property string $consentable_type
 * @property string $consentable_id
 * @property int|null $consent_type_id
 * @property string|null $consent_type_slug
 * @property string|null $consent_version
 * @property string $action
 * @property Carbon $occurred_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $policy_url
 * @property string|null $policy_text_hash
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property-read ConsentType|null $consentType
 */
class ConsentAuditLog extends Model
{
    public const ACTION_GRANTED = 'granted';

    public const ACTION_REVOKED = 'revoked';

    public const ACTION_RENEWED = 'renewed';

    public const ACTION_EXPIRED = 'expired';

    public const ACTION_ANONYMIZED = 'anonymized';

    /**
     * Only created_at is maintained; the record is immutable, so there is no updated_at.
     */
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'consentable_type',
        'consentable_id',
        'consent_type_id',
        'consent_type_slug',
        'consent_version',
        'action',
        'occurred_at',
        'ip_address',
        'user_agent',
        'policy_url',
        'policy_text_hash',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Enforce append-only semantics at the model level.
        static::updating(function (): void {
            throw new RuntimeException('Consent audit logs are immutable and cannot be updated.');
        });

        static::deleting(function (): void {
            throw new RuntimeException('Consent audit logs are immutable and cannot be deleted.');
        });
    }

    /**
     * The consent type version this entry refers to (may be null once that type is deleted).
     *
     * @return BelongsTo<ConsentType, $this>
     */
    public function consentType(): BelongsTo
    {
        return $this->belongsTo(ConsentType::class);
    }

    /**
     * The subject (user, guest, …) the consent action belongs to.
     *
     * @return MorphTo<Model, $this>
     */
    public function consentable(): MorphTo
    {
        return $this->morphTo();
    }
}
