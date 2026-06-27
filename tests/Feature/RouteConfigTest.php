<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

test('the cookie consent routes are registered with the configured names', function () {
    expect(Route::has('gdpr.consent.accept-all'))->toBeTrue();
    expect(Route::has('gdpr.consent.reject-all'))->toBeTrue();
    expect(Route::has('gdpr.consent.save-preferences'))->toBeTrue();
    expect(Route::has('gdpr.consent.status'))->toBeTrue();
});

test('the routes use the configured prefix', function () {
    $route = Route::getRoutes()->getByName('gdpr.consent.accept-all');

    expect($route)->not->toBeNull();
    expect($route->uri())->toBe('gdpr/consent/accept-all');
});
