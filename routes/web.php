<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Selli\LaravelGdprConsentDatabase\Http\Controllers\GuestConsentController;

// Registered by the service provider inside a configurable group (prefix / name / middleware).
Route::post('accept-all', [GuestConsentController::class, 'acceptAll'])->name('accept-all');
Route::post('reject-all', [GuestConsentController::class, 'rejectAll'])->name('reject-all');
Route::post('save-preferences', [GuestConsentController::class, 'savePreferences'])->name('save-preferences');
Route::post('status', [GuestConsentController::class, 'getConsentStatus'])->name('status');
