<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Selli\LaravelGdprConsentDatabase\Models\UserConsent;

/**
 * Dispatched when an expired consent is closed by the gdpr:consents:expire command.
 */
class ConsentExpired
{
    use Dispatchable;

    public function __construct(
        public readonly UserConsent $consent,
    ) {}
}
