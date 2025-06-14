<?php

use Illuminate\Support\Facades\Route;
use Selli\LaravelGdprConsentDatabase\Http\Controllers\GuestConsentController;

Route::prefix('gdpr/consent')
    ->name('gdpr.consent.')
    ->group(function () {
        Route::post('accept-all', [GuestConsentController::class, 'acceptAll'])
            ->name('accept-all');
        Route::post('reject-all', [GuestConsentController::class, 'rejectAll'])
            ->name('reject-all');
        Route::post('save-preferences', [GuestConsentController::class, 'savePreferences'])
            ->name('save-preferences');
        Route::post('status', [GuestConsentController::class, 'getConsentStatus'])
            ->name('status');
    });
