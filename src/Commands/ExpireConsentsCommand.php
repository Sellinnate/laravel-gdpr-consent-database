<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Selli\LaravelGdprConsentDatabase\Events\ConsentExpired;
use Selli\LaravelGdprConsentDatabase\Models\ConsentAuditLog;
use Selli\LaravelGdprConsentDatabase\Models\UserConsent;

class ExpireConsentsCommand extends Command
{
    /** @var string */
    protected $signature = 'gdpr:consents:expire';

    /** @var string */
    protected $description = 'Close consents past their expiry date, recording an audit entry and dispatching ConsentExpired.';

    public function handle(): int
    {
        $expired = UserConsent::query()
            ->where('granted', true)
            ->whereNull('revoked_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->with('consentType')
            ->get();

        foreach ($expired as $consent) {
            DB::transaction(function () use ($consent): void {
                $consent->granted = false;
                $consent->save();

                ConsentAuditLog::create([
                    'consentable_type' => $consent->consentable_type,
                    'consentable_id' => $consent->consentable_id,
                    'consent_type_id' => $consent->consent_type_id,
                    'consent_type_slug' => $consent->consentType?->slug,
                    'consent_version' => $consent->consent_version,
                    'action' => ConsentAuditLog::ACTION_EXPIRED,
                    'occurred_at' => now(),
                    'policy_url' => $consent->consentType?->policy_url,
                    'policy_text_hash' => $consent->consentType?->policy_text_hash,
                ]);
            });

            // Dispatch only after the row is durably committed, so listener side effects
            // (emails / webhooks) never fire for a rolled-back change.
            ConsentExpired::dispatch($consent);
        }

        $this->info("Closed {$expired->count()} expired consent(s).");

        return self::SUCCESS;
    }
}
