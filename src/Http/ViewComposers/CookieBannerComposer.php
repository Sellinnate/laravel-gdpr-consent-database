<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase\Http\ViewComposers;

use Illuminate\View\View;
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;

/**
 * Binds the active cookie consent types to the banner view, so the Blade template never runs
 * database queries itself.
 */
class CookieBannerComposer
{
    public function compose(View $view): void
    {
        // Respect an explicit `consentTypes` passed to the directive; only provide the default.
        if (! array_key_exists('consentTypes', $view->getData())) {
            $view->with('consentTypes', ConsentType::cookies());
        }
    }
}
