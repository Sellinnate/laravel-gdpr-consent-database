<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Selli\LaravelGdprConsentDatabase\Models\UserConsent;
use Selli\LaravelGdprConsentDatabase\Traits\HasGdprConsents;

/**
 * Implemented by any model that can hold GDPR consents.
 *
 * The {@see HasGdprConsents} trait provides a full implementation; add `implements Consentable` to
 * your model to type-hint consent operations against this contract.
 */
interface Consentable
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function giveConsent(string|int $consentTypeId, array $metadata = [], ?int $validityMonths = null): UserConsent;

    public function revokeConsent(string|int $consentTypeId): int;

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function renewConsent(string|int $consentTypeId, array $metadata = []): ?UserConsent;

    public function hasConsent(string|int $consentTypeId, bool $checkVersion = false): bool;

    /**
     * @return Collection<int, UserConsent>
     */
    public function activeConsents(): Collection;

    public function hasAllRequiredConsents(bool $checkVersion = false): bool;

    /**
     * @return array{token: string, consents: int, audit_logs: int}
     */
    public function anonymizeConsents(?string $token = null): array;
}
