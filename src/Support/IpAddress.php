<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase\Support;

/**
 * Applies the configured privacy policy to an IP address before it is persisted.
 */
class IpAddress
{
    /**
     * Resolve the IP value to store, honouring the `privacy` configuration.
     *
     * Returns null when storage is disabled, the anonymised (masked) form when anonymisation is
     * enabled, or the raw address otherwise.
     */
    public static function forStorage(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        if (! config('gdpr-consent-database.privacy.store_ip_address', true)) {
            return null;
        }

        if (config('gdpr-consent-database.privacy.anonymize_ip', false)) {
            return self::anonymize($ip);
        }

        return $ip;
    }

    /**
     * Mask an IP address: zero the last octet of an IPv4 address, or the last 80 bits of an IPv6.
     */
    public static function anonymize(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0';

            return implode('.', $parts);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $packed = inet_pton($ip);

            if ($packed === false) {
                return $ip;
            }

            // Keep the first 48 bits (6 bytes), zero the remaining 80 bits (10 bytes).
            $masked = substr($packed, 0, 6).str_repeat("\0", 10);

            return (string) inet_ntop($masked);
        }

        return $ip;
    }
}
