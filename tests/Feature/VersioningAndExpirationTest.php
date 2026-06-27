<?php

use Carbon\Carbon;
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;
use Selli\LaravelGdprConsentDatabase\Tests\Models\TestUser;

test('consent type versioning works correctly', function () {
    // Create a consent type
    $consentType = ConsentType::create([
        'name' => 'Privacy Policy',
        'slug' => 'privacy-policy',
        'description' => 'Privacy Policy consent',
        'required' => true,
        'active' => true,
        'category' => 'other',
        'version' => '1.0',
    ]);

    // Create a new version
    $newVersion = $consentType->createNewVersion([
        'description' => 'Updated Privacy Policy consent',
    ]);

    // Check that the original version is now inactive
    $consentType->refresh();
    expect($consentType->active)->toBeFalse();
    expect($consentType->effective_until)->not->toBeNull();

    // The new version keeps the SAME (stable) slug — the slug identifies the group.
    expect($newVersion->version)->toBe('1.1');
    expect($newVersion->slug)->toBe('privacy-policy');
    expect($newVersion->active)->toBeTrue();
    expect($newVersion->description)->toBe('Updated Privacy Policy consent');
    expect($newVersion->effective_from)->not->toBeNull();
    expect($newVersion->effective_until)->toBeNull();

    // There is exactly one active version for the slug group, and it is the new one.
    expect(ConsentType::where('slug', 'privacy-policy')->where('active', true)->count())->toBe(1);
    expect($consentType->currentVersion()->id)->toBe($newVersion->id);
});

test('consent expiration works correctly', function () {
    // Create a consent type with validity period
    $consentType = ConsentType::create([
        'name' => 'Marketing Emails',
        'slug' => 'marketing-emails',
        'description' => 'Marketing emails consent',
        'required' => false,
        'active' => true,
        'category' => 'other',
        'version' => '1.0',
        'validity_months' => 12,
    ]);

    // Create a user
    $user = TestUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    // Give consent
    $consent = $user->giveConsent('marketing-emails');

    // Check that expiration date is set correctly
    expect($consent->expires_at->format('Y-m-d'))->toBe(now()->addMonths(12)->format('Y-m-d'));

    // Check that the consent is active
    expect($user->hasConsent('marketing-emails'))->toBeTrue();

    // Fast forward to just before expiration
    Carbon::setTestNow(now()->addMonths(12)->subDay());
    expect($user->hasConsent('marketing-emails'))->toBeTrue();

    // Fast forward to after expiration
    Carbon::setTestNow(now()->addMonths(12)->addDay());
    expect($user->hasConsent('marketing-emails'))->toBeFalse();

    // Reset time
    Carbon::setTestNow();
});

test('consent version checking works correctly', function () {
    // Create a consent type
    $consentType = ConsentType::create([
        'name' => 'Terms of Service',
        'slug' => 'terms',
        'description' => 'Terms of Service consent',
        'required' => true,
        'active' => true,
        'category' => 'other',
        'version' => '1.0',
    ]);

    // Create a user
    $user = TestUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    // Give consent to the current version (1.0)
    $user->giveConsent('terms');

    expect($user->hasConsent('terms'))->toBeTrue();
    expect($user->hasConsent('terms', checkVersion: true))->toBeTrue();

    // Publish a new version 1.1 (stable slug)
    $consentType->createNewVersion([
        'description' => 'Updated Terms of Service consent',
    ]);

    $user->unsetRelation('consents');

    // The user still holds a consent for the group (an older version) → true without version check.
    expect($user->hasConsent('terms'))->toBeTrue();
    // But it is no longer the current version → false with version check.
    expect($user->hasConsent('terms', checkVersion: true))->toBeFalse();

    // Without version check the required consent is satisfied (held on a prior version)...
    expect($user->hasAllRequiredConsents(false))->toBeTrue();
    expect($user->getMissingRequiredConsents(false))->toHaveCount(0);

    // ...but with version check it must be re-consented.
    expect($user->hasAllRequiredConsents(true))->toBeFalse();
    expect($user->getMissingRequiredConsents(true))->toHaveCount(1);
});

test('consent renewal works correctly', function () {
    // Create a consent type
    $consentType = ConsentType::create([
        'name' => 'Newsletter',
        'slug' => 'newsletter',
        'description' => 'Newsletter consent',
        'required' => false,
        'active' => true,
        'category' => 'other',
        'version' => '1.0',
    ]);

    // Create a user
    $user = TestUser::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    // Give consent to version 1.0 with metadata
    $user->giveConsent('newsletter', ['source' => 'registration']);

    // Publish version 1.1 (stable slug)
    $consentType->createNewVersion(['description' => 'Updated Newsletter consent']);
    $user->unsetRelation('consents');

    // The held consent (1.0) is now outdated → it must be reported as needing renewal.
    expect($user->consentsNeedingRenewal())->toHaveCount(1);

    // Renew using the stable slug; the renewed consent is on the current version 1.1.
    $renewedConsent = $user->renewConsent('newsletter', ['source' => 'renewal']);

    expect($renewedConsent)->not->toBeNull();
    expect($renewedConsent->consent_version)->toBe('1.1');
    expect($renewedConsent->metadata)->toBe(['source' => 'renewal']);

    // After renewal nothing needs renewal and exactly one active consent remains.
    $user->unsetRelation('consents');
    expect($user->consentsNeedingRenewal())->toHaveCount(0);
    expect($user->consents()->active()->count())->toBe(1);
});

test('expiring consents can be retrieved within the requested window', function () {
    Carbon::setTestNow('2026-01-01 00:00:00');

    ConsentType::create([
        'name' => 'Data Processing',
        'slug' => 'data-processing',
        'description' => 'Data Processing consent',
        'required' => true,
        'active' => true,
        'category' => 'other',
        'version' => '1.0',
    ]);

    $user = TestUser::create(['name' => 'Test User', 'email' => 'test@example.com']);

    // Consent expiring in exactly 15 days.
    $consent = $user->giveConsent('data-processing');
    $consent->expires_at = now()->addDays(15);
    $consent->save();
    $user->unsetRelation('consents');

    // 15 days falls inside a 30-day window...
    expect($user->getConsentsExpiringWithinDays(30))->toHaveCount(1);
    // ...but outside a 10-day window.
    expect($user->getConsentsExpiringWithinDays(10))->toHaveCount(0);

    // After 10 days pass, the same consent (now 5 days from expiry) falls inside the 10-day window.
    Carbon::setTestNow(now()->addDays(10));
    expect($user->getConsentsExpiringWithinDays(10))->toHaveCount(1);

    Carbon::setTestNow();
});

test('already expired consents are not reported as expiring', function () {
    Carbon::setTestNow('2026-01-01 00:00:00');

    ConsentType::create([
        'name' => 'Analytics',
        'slug' => 'analytics',
        'required' => false,
        'active' => true,
        'category' => 'cookie',
        'version' => '1.0',
    ]);

    $user = TestUser::create(['name' => 'Test User', 'email' => 'expired@example.com']);

    $consent = $user->giveConsent('analytics');
    $consent->expires_at = now()->subDay(); // already expired
    $consent->save();
    $user->unsetRelation('consents');

    // An expired consent is no longer "active", so it must not appear as expiring-soon.
    expect($user->getConsentsExpiringWithinDays(30))->toHaveCount(0);
    expect($user->expiredConsents())->toHaveCount(1);

    Carbon::setTestNow();
});

test('consents expiring beyond the window are excluded', function () {
    Carbon::setTestNow('2026-01-01 00:00:00');

    ConsentType::create([
        'name' => 'Newsletter',
        'slug' => 'newsletter',
        'required' => false,
        'active' => true,
        'category' => 'other',
        'version' => '1.0',
    ]);

    $user = TestUser::create(['name' => 'Test User', 'email' => 'far@example.com']);

    $consent = $user->giveConsent('newsletter');
    $consent->expires_at = now()->addDays(60);
    $consent->save();
    $user->unsetRelation('consents');

    expect($user->getConsentsExpiringWithinDays(30))->toHaveCount(0);
    expect($user->getConsentsExpiringWithinDays(90))->toHaveCount(1);

    Carbon::setTestNow();
});
