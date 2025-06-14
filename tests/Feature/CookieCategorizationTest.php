<?php

use Illuminate\Http\Request;
use Selli\LaravelGdprConsentDatabase\Http\Controllers\GuestConsentController;
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;
use Selli\LaravelGdprConsentDatabase\Services\GuestConsentManager;

test('cookie banner only shows cookie category consent types', function () {
    $cookieConsent = ConsentType::create([
        'name' => 'Technical Cookies',
        'slug' => 'technical-cookies',
        'description' => 'Essential cookies',
        'required' => true,
        'active' => true,
        'category' => 'cookie',
    ]);

    $marketingConsent = ConsentType::create([
        'name' => 'Marketing Emails',
        'slug' => 'marketing-emails',
        'description' => 'Marketing communications',
        'required' => false,
        'active' => true,
        'category' => 'other',
    ]);

    $controller = new GuestConsentController(app(GuestConsentManager::class));
    $response = $controller->getConsentStatus(new Request);
    $data = $response->getData(true);

    expect($data['consentTypes'])->toHaveCount(1);
    expect($data['consentTypes'][0]['slug'])->toBe('technical-cookies');
    expect($data['consentTypes'][0]['category'])->toBe('cookie');
});

test('accept all only processes cookie category consents', function () {
    ConsentType::create([
        'name' => 'Technical Cookies',
        'slug' => 'technical-cookies',
        'category' => 'cookie',
        'active' => true,
    ]);

    ConsentType::create([
        'name' => 'Marketing Emails',
        'slug' => 'marketing-emails',
        'category' => 'other',
        'active' => true,
    ]);

    $controller = new GuestConsentController(app(GuestConsentManager::class));
    $response = $controller->acceptAll(new Request);

    expect($response->getData(true)['success'])->toBeTrue();

    $guestManager = app(GuestConsentManager::class);
    expect($guestManager->hasConsent('technical-cookies'))->toBeTrue();
    expect($guestManager->hasConsent('marketing-emails'))->toBeFalse();
});

test('reject all only processes required cookie category consents', function () {
    ConsentType::create([
        'name' => 'Technical Cookies',
        'slug' => 'technical-cookies',
        'category' => 'cookie',
        'required' => true,
        'active' => true,
    ]);

    ConsentType::create([
        'name' => 'Profiling Cookies',
        'slug' => 'profiling-cookies',
        'category' => 'cookie',
        'required' => false,
        'active' => true,
    ]);

    ConsentType::create([
        'name' => 'Marketing Emails',
        'slug' => 'marketing-emails',
        'category' => 'other',
        'required' => true,
        'active' => true,
    ]);

    $controller = new GuestConsentController(app(GuestConsentManager::class));
    $response = $controller->rejectAll(new Request);

    expect($response->getData(true)['success'])->toBeTrue();

    $guestManager = app(GuestConsentManager::class);
    expect($guestManager->hasConsent('technical-cookies'))->toBeTrue();
    expect($guestManager->hasConsent('profiling-cookies'))->toBeFalse();
    expect($guestManager->hasConsent('marketing-emails'))->toBeFalse();
});

test('factory can create consent types with cookie category', function () {
    $cookieConsent = ConsentType::factory()->cookie()->create();
    $otherConsent = ConsentType::factory()->other()->create();

    expect($cookieConsent->category)->toBe('cookie');
    expect($otherConsent->category)->toBe('other');
});

test('consent type model includes category in fillable fields', function () {
    $consentType = new ConsentType;
    expect($consentType->getFillable())->toContain('category');
});
