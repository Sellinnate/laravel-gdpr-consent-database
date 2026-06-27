<?php

declare(strict_types=1);

use Selli\LaravelGdprConsentDatabase\Models\ConsentType;
use Selli\LaravelGdprConsentDatabase\Tests\Models\TestUser;

/**
 * Honest coverage for the version-aware methods in the single-version case (no createNewVersion).
 * The multi-version behaviour is redesigned and tested in Phase 2.
 */
test('hasConsent with version check returns true when only one version exists', function () {
    ConsentType::create([
        'name' => 'Terms',
        'slug' => 'terms',
        'required' => true,
        'active' => true,
        'category' => 'other',
        'version' => '1.0',
    ]);

    $user = TestUser::create(['name' => 'V', 'email' => 'v@example.com']);
    $user->giveConsent('terms');

    expect($user->hasConsent('terms'))->toBeTrue();
    expect($user->hasConsent('terms', checkVersion: true))->toBeTrue();
});

test('required consent checks pass with version check when consent is current', function () {
    ConsentType::create([
        'name' => 'Terms',
        'slug' => 'terms',
        'required' => true,
        'active' => true,
        'category' => 'other',
        'version' => '1.0',
    ]);

    $user = TestUser::create(['name' => 'V', 'email' => 'v2@example.com']);

    // Before consenting: missing.
    expect($user->getMissingRequiredConsents(checkVersion: true))->toHaveCount(1);
    expect($user->hasAllRequiredConsents(checkVersion: true))->toBeFalse();

    $user->giveConsent('terms');
    $user->unsetRelation('consents');

    // After consenting to the only/current version: satisfied.
    expect($user->getMissingRequiredConsents(checkVersion: true))->toHaveCount(0);
    expect($user->hasAllRequiredConsents(checkVersion: true))->toBeTrue();
});

test('renewConsent supersedes the active consent and returns a fresh record', function () {
    ConsentType::create([
        'name' => 'Newsletter',
        'slug' => 'newsletter',
        'required' => false,
        'active' => true,
        'category' => 'other',
        'version' => '1.0',
    ]);

    $user = TestUser::create(['name' => 'V', 'email' => 'v3@example.com']);

    $original = $user->giveConsent('newsletter', ['source' => 'signup']);
    $user->unsetRelation('consents');

    $renewed = $user->renewConsent('newsletter');

    expect($renewed)->not->toBeNull();
    expect($renewed->id)->not->toBe($original->id);
    expect($renewed->consent_version)->toBe('1.0');
    // Metadata is preserved from the previous consent when none is supplied.
    expect($renewed->metadata)->toBe(['source' => 'signup']);

    // Exactly one active consent remains.
    $user->unsetRelation('consents');
    expect($user->consents()->active()->count())->toBe(1);

    // The original record is now revoked.
    expect($original->fresh()->granted)->toBeFalse();
});

test('renewConsent returns null for an inactive consent type', function () {
    ConsentType::create([
        'name' => 'Old',
        'slug' => 'old',
        'required' => false,
        'active' => false,
        'category' => 'other',
        'version' => '1.0',
    ]);

    $user = TestUser::create(['name' => 'V', 'email' => 'v4@example.com']);

    expect($user->renewConsent('old'))->toBeNull();
});

test('consentsNeedingRenewal is empty when every consent is current', function () {
    ConsentType::create([
        'name' => 'Terms',
        'slug' => 'terms',
        'required' => true,
        'active' => true,
        'category' => 'other',
        'version' => '1.0',
    ]);

    $user = TestUser::create(['name' => 'V', 'email' => 'v5@example.com']);
    $user->giveConsent('terms');

    expect($user->consentsNeedingRenewal())->toHaveCount(0);
});
