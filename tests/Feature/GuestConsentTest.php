<?php

use Selli\LaravelGdprConsentDatabase\Models\ConsentType;
use Selli\LaravelGdprConsentDatabase\Models\GuestConsent;
use Selli\LaravelGdprConsentDatabase\Services\GuestConsentManager;

test('guest consent can be created and managed', function () {
    $sessionId = 'test-session-123';

    $marketingConsent = ConsentType::create([
        'name' => 'Marketing Email',
        'slug' => 'marketing-email',
        'description' => 'Consent for marketing emails',
        'required' => false,
        'active' => true,
        'category' => 'other',
    ]);

    $termsConsent = ConsentType::create([
        'name' => 'Terms and Conditions',
        'slug' => 'terms',
        'description' => 'Required terms acceptance',
        'required' => true,
        'active' => true,
        'category' => 'other',
    ]);

    $manager = new GuestConsentManager;

    $guest = $manager->getGuestConsent($sessionId);
    expect($guest)->toBeInstanceOf(GuestConsent::class);
    expect($guest->session_id)->toBe($sessionId);

    $consent = $manager->giveConsent('marketing-email', ['source' => 'test'], null, $sessionId);
    expect($consent)->not->toBeNull();
    expect($manager->hasConsent('marketing-email', $sessionId))->toBeTrue();

    $manager->revokeConsent('marketing-email', $sessionId);
    expect($manager->hasConsent('marketing-email', $sessionId))->toBeFalse();

    expect($manager->hasAllRequiredConsents($sessionId))->toBeFalse();
    $manager->giveConsent('terms', ['source' => 'test'], null, $sessionId);
    expect($manager->hasAllRequiredConsents($sessionId))->toBeTrue();
});

test('guest consent controller endpoints work', function () {
    ConsentType::create([
        'name' => 'Marketing',
        'slug' => 'marketing',
        'required' => false,
        'active' => true,
        'category' => 'cookie',
    ]);

    ConsentType::create([
        'name' => 'Required',
        'slug' => 'required',
        'required' => true,
        'active' => true,
        'category' => 'cookie',
    ]);

    $response = $this->postJson('/gdpr/consent/accept-all');
    $response->assertJson(['success' => true]);

    $response = $this->postJson('/gdpr/consent/reject-all');
    $response->assertJson(['success' => true]);

    $response = $this->postJson('/gdpr/consent/save-preferences', [
        'consents' => [
            'marketing' => true,
            'required' => true,
        ],
    ]);
    $response->assertJson(['success' => true]);
});

test('blade directive renders cookie banner', function () {
    $consentTypes = collect([
        ConsentType::create([
            'name' => 'Marketing',
            'slug' => 'marketing',
            'required' => false,
            'active' => true,
            'category' => 'cookie',
        ]),
    ]);

    $view = view('gdpr-consent-database::cookie-banner', [
        'consentTypes' => $consentTypes,
        'title' => 'Test Title',
        'message' => 'Test Message',
    ]);

    $html = $view->render();

    expect($html)->toContain('Test Title');
    expect($html)->toContain('Test Message');
    expect($html)->toContain('gdpr-cookie-banner');
    expect($html)->toContain('Marketing');
});

test('guest consent uses session for identification', function () {
    $sessionId1 = 'session-1';
    $sessionId2 = 'session-2';

    ConsentType::create([
        'name' => 'Marketing',
        'slug' => 'marketing',
        'required' => false,
        'active' => true,
        'category' => 'other',
    ]);

    $manager = new GuestConsentManager;

    $manager->giveConsent('marketing', ['source' => 'test'], null, $sessionId1);

    expect($manager->hasConsent('marketing', $sessionId1))->toBeTrue();
    expect($manager->hasConsent('marketing', $sessionId2))->toBeFalse();
});

test('guest consent controller endpoints work with technical cookie codes', function () {
    $technicalCookieCode = 'gdpr_test_123';

    ConsentType::create([
        'name' => 'Marketing',
        'slug' => 'marketing',
        'required' => false,
        'active' => true,
        'category' => 'cookie',
    ]);

    ConsentType::create([
        'name' => 'Required',
        'slug' => 'required',
        'required' => true,
        'active' => true,
        'category' => 'cookie',
    ]);

    $response = $this->postJson('/gdpr/consent/accept-all', [
        'technical_cookie_code' => $technicalCookieCode,
    ]);
    $response->assertJson(['success' => true]);

    $response = $this->postJson('/gdpr/consent/status', [
        'technical_cookie_code' => $technicalCookieCode,
    ]);
    $response->assertJson(['hasAnyConsent' => true]);

    $response = $this->postJson('/gdpr/consent/save-preferences', [
        'technical_cookie_code' => $technicalCookieCode,
        'consents' => [
            'marketing' => false,
            'required' => true,
        ],
    ]);
    $response->assertJson(['success' => true]);

    $response = $this->postJson('/gdpr/consent/status', [
        'technical_cookie_code' => $technicalCookieCode,
    ]);
    $response->assertJson([
        'hasAnyConsent' => true,
        'consents' => [
            'marketing' => false,
            'required' => true,
        ],
    ]);
});

test('guest consent respects required consent types', function () {
    $sessionId = 'test-session';

    ConsentType::create([
        'name' => 'Required Terms',
        'slug' => 'terms',
        'required' => true,
        'active' => true,
        'category' => 'other',
    ]);

    ConsentType::create([
        'name' => 'Optional Marketing',
        'slug' => 'marketing',
        'required' => false,
        'active' => true,
        'category' => 'other',
    ]);

    $manager = new GuestConsentManager;

    expect($manager->hasAllRequiredConsents($sessionId))->toBeFalse();

    $missingConsents = $manager->getMissingRequiredConsents($sessionId);
    expect($missingConsents)->toHaveCount(1);
    expect($missingConsents->first()->slug)->toBe('terms');

    $manager->giveConsent('terms', [], null, $sessionId);
    expect($manager->hasAllRequiredConsents($sessionId))->toBeTrue();
});
