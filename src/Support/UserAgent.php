<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase\Support;

/**
 * Applies the configured privacy policy to a user-agent string before it is persisted.
 *
 * The user agent is part of the consent context but is less evidentially critical than the IP,
 * so it can be omitted entirely for data minimisation (GDPR Art. 5(1)(c)).
 */
class UserAgent
{
    /**
     * Resolve the user-agent value to store, honouring the `privacy.store_user_agent` configuration.
     */
    public static function forStorage(?string $userAgent): ?string
    {
        if ($userAgent === null || $userAgent === '') {
            return null;
        }

        if (! config('gdpr-consent-database.privacy.store_user_agent', true)) {
            return null;
        }

        return $userAgent;
    }
}
