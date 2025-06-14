<?php

use Selli\LaravelGdprConsentDatabase\Models\ConsentType;
use Selli\LaravelGdprConsentDatabase\Models\GuestConsent;
use Selli\LaravelGdprConsentDatabase\Services\GuestConsentManager;

test('technical cookie code is used for guest consent identification', function () {
    $technicalCookieCode = 'gdpr_test_cookie_123';
    
    $marketingConsent = ConsentType::create([
        'name' => 'Marketing Email',
        'slug' => 'marketing-email',
        'description' => 'Consent for marketing emails',
        'required' => false,
        'active' => true,
        'category' => 'cookie',
    ]);

    $manager = new GuestConsentManager;
    
    $guest = $manager->getGuestConsent($technicalCookieCode);
    expect($guest)->toBeInstanceOf(GuestConsent::class);
    expect($guest->session_id)->toBe($technicalCookieCode);

    $consent = $manager->giveConsent('marketing-email', ['source' => 'test'], null, $technicalCookieCode);
    expect($consent)->not->toBeNull();
    expect($manager->hasConsent('marketing-email', $technicalCookieCode))->toBeTrue();

    $manager->revokeConsent('marketing-email', $technicalCookieCode);
    expect($manager->hasConsent('marketing-email', $technicalCookieCode))->toBeFalse();
});

test('accept all endpoint accepts technical cookie code', function () {
    $technicalCookieCode = 'gdpr_test_cookie_456';
    
    ConsentType::create([
        'name' => 'Technical Cookies',
        'slug' => 'technical',
        'required' => true,
        'active' => true,
        'category' => 'cookie',
    ]);

    ConsentType::create([
        'name' => 'Marketing Cookies',
        'slug' => 'marketing',
        'required' => false,
        'active' => true,
        'category' => 'cookie',
    ]);

    $response = $this->postJson('/gdpr/consent/accept-all', [
        'technical_cookie_code' => $technicalCookieCode
    ]);
    
    $response->assertJson(['success' => true]);
    
    $manager = new GuestConsentManager;
    expect($manager->hasConsent('technical', $technicalCookieCode))->toBeTrue();
    expect($manager->hasConsent('marketing', $technicalCookieCode))->toBeTrue();
});

test('reject all endpoint accepts technical cookie code', function () {
    $technicalCookieCode = 'gdpr_test_cookie_789';
    
    ConsentType::create([
        'name' => 'Technical Cookies',
        'slug' => 'technical',
        'required' => true,
        'active' => true,
        'category' => 'cookie',
    ]);

    ConsentType::create([
        'name' => 'Marketing Cookies',
        'slug' => 'marketing',
        'required' => false,
        'active' => true,
        'category' => 'cookie',
    ]);

    $response = $this->postJson('/gdpr/consent/reject-all', [
        'technical_cookie_code' => $technicalCookieCode
    ]);
    
    $response->assertJson(['success' => true]);
    
    $manager = new GuestConsentManager;
    expect($manager->hasConsent('technical', $technicalCookieCode))->toBeTrue();
    expect($manager->hasConsent('marketing', $technicalCookieCode))->toBeFalse();
});

test('save preferences endpoint accepts technical cookie code', function () {
    $technicalCookieCode = 'gdpr_test_cookie_101';
    
    ConsentType::create([
        'name' => 'Technical Cookies',
        'slug' => 'technical',
        'required' => true,
        'active' => true,
        'category' => 'cookie',
    ]);

    ConsentType::create([
        'name' => 'Marketing Cookies',
        'slug' => 'marketing',
        'required' => false,
        'active' => true,
        'category' => 'cookie',
    ]);

    $response = $this->postJson('/gdpr/consent/save-preferences', [
        'technical_cookie_code' => $technicalCookieCode,
        'consents' => [
            'technical' => true,
            'marketing' => false,
        ]
    ]);
    
    $response->assertJson(['success' => true]);
    
    $manager = new GuestConsentManager;
    expect($manager->hasConsent('technical', $technicalCookieCode))->toBeTrue();
    expect($manager->hasConsent('marketing', $technicalCookieCode))->toBeFalse();
});

test('consent status endpoint accepts technical cookie code', function () {
    $technicalCookieCode = 'gdpr_test_cookie_202';
    
    ConsentType::create([
        'name' => 'Technical Cookies',
        'slug' => 'technical',
        'required' => true,
        'active' => true,
        'category' => 'cookie',
    ]);

    ConsentType::create([
        'name' => 'Marketing Cookies',
        'slug' => 'marketing',
        'required' => false,
        'active' => true,
        'category' => 'cookie',
    ]);

    $manager = new GuestConsentManager;
    $manager->giveConsent('technical', ['source' => 'test'], null, $technicalCookieCode);

    $response = $this->postJson('/gdpr/consent/status', [
        'technical_cookie_code' => $technicalCookieCode
    ]);
    
    $response->assertJson([
        'hasAnyConsent' => true,
        'consents' => [
            'technical' => true,
            'marketing' => false,
        ]
    ]);
});

test('technical cookie code falls back to gdpr_session_id cookie', function () {
    $sessionId = 'fallback_session_123';
    
    ConsentType::create([
        'name' => 'Technical Cookies',
        'slug' => 'technical',
        'required' => true,
        'active' => true,
        'category' => 'cookie',
    ]);

    $response = $this->postJson('/gdpr/consent/accept-all', []);
    
    $response->assertJson(['success' => true]);
    
    $manager = new GuestConsentManager;
    $guest = $manager->getGuestConsent();
    expect($manager->hasConsent('technical', $guest->session_id))->toBeTrue();
});

test('different technical cookie codes maintain separate consent states', function () {
    $cookieCode1 = 'gdpr_user_1';
    $cookieCode2 = 'gdpr_user_2';
    
    ConsentType::create([
        'name' => 'Marketing Cookies',
        'slug' => 'marketing',
        'required' => false,
        'active' => true,
        'category' => 'cookie',
    ]);

    $manager = new GuestConsentManager;
    
    $manager->giveConsent('marketing', ['source' => 'test'], null, $cookieCode1);
    
    expect($manager->hasConsent('marketing', $cookieCode1))->toBeTrue();
    expect($manager->hasConsent('marketing', $cookieCode2))->toBeFalse();
    
    $manager->giveConsent('marketing', ['source' => 'test'], null, $cookieCode2);
    
    expect($manager->hasConsent('marketing', $cookieCode1))->toBeTrue();
    expect($manager->hasConsent('marketing', $cookieCode2))->toBeTrue();
    
    $manager->revokeConsent('marketing', $cookieCode1);
    
    expect($manager->hasConsent('marketing', $cookieCode1))->toBeFalse();
    expect($manager->hasConsent('marketing', $cookieCode2))->toBeTrue();
});
