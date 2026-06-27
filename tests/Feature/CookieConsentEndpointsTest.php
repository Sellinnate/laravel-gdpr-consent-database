<?php

declare(strict_types=1);

use Selli\LaravelGdprConsentDatabase\Models\ConsentType;
use Selli\LaravelGdprConsentDatabase\Services\GuestConsentManager;

beforeEach(function () {
    ConsentType::create([
        'name' => 'Technical',
        'slug' => 'technical',
        'required' => true,
        'active' => true,
        'category' => 'cookie',
    ]);

    ConsentType::create([
        'name' => 'Analytics',
        'slug' => 'analytics',
        'required' => false,
        'active' => true,
        'category' => 'cookie',
    ]);
});

test('reject-all revokes previously granted optional cookie consents (C2 regression)', function () {
    $code = 'gdpr_reject_regression';

    // The visitor first accepts everything: both technical and analytics become granted.
    $this->postJson('/gdpr/consent/accept-all', ['technical_cookie_code' => $code])
        ->assertJson(['success' => true]);

    $manager = app(GuestConsentManager::class);
    expect($manager->hasConsent('technical', $code))->toBeTrue();
    expect($manager->hasConsent('analytics', $code))->toBeTrue();

    // Then the visitor rejects all: the optional analytics consent MUST be revoked,
    // while the required technical consent stays granted.
    $this->postJson('/gdpr/consent/reject-all', ['technical_cookie_code' => $code])
        ->assertJson(['success' => true]);

    expect($manager->hasConsent('technical', $code))->toBeTrue();
    expect($manager->hasConsent('analytics', $code))->toBeFalse();
});

test('reject-all reported via status endpoint reflects revoked optional consents', function () {
    $code = 'gdpr_reject_status';

    $this->postJson('/gdpr/consent/accept-all', ['technical_cookie_code' => $code]);
    $this->postJson('/gdpr/consent/reject-all', ['technical_cookie_code' => $code]);

    $this->postJson('/gdpr/consent/status', ['technical_cookie_code' => $code])
        ->assertJson([
            'consents' => [
                'technical' => true,
                'analytics' => false,
            ],
        ]);
});

test('accept-all grants every active cookie consent type', function () {
    $code = 'gdpr_accept_all';

    $this->postJson('/gdpr/consent/accept-all', ['technical_cookie_code' => $code])
        ->assertJson(['success' => true]);

    $manager = app(GuestConsentManager::class);
    expect($manager->hasConsent('technical', $code))->toBeTrue();
    expect($manager->hasConsent('analytics', $code))->toBeTrue();
});

test('save-preferences grants checked and revokes unchecked cookie consents', function () {
    $code = 'gdpr_save_prefs';

    // Start from everything granted.
    $this->postJson('/gdpr/consent/accept-all', ['technical_cookie_code' => $code]);

    // Save a granular choice: keep technical, drop analytics.
    $this->postJson('/gdpr/consent/save-preferences', [
        'technical_cookie_code' => $code,
        'consents' => [
            'technical' => true,
            'analytics' => false,
        ],
    ])->assertJson(['success' => true]);

    $manager = app(GuestConsentManager::class);
    expect($manager->hasConsent('technical', $code))->toBeTrue();
    expect($manager->hasConsent('analytics', $code))->toBeFalse();
});
