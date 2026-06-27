<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Selli\LaravelGdprConsentDatabase\Models\UserConsent;

/**
 * Dispatched when a subject grants consent for a type.
 */
class ConsentGranted
{
    use Dispatchable;

    public function __construct(
        public readonly Model $consentable,
        public readonly UserConsent $consent,
    ) {}
}
