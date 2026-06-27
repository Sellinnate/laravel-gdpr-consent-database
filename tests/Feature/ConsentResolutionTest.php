<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;
use Selli\LaravelGdprConsentDatabase\Tests\Models\TestUser;

test('a slug that is a prefix of another type does not resolve to the longer slug (Phase 1 review #1)', function () {
    // Only the longer slug exists; the short one is a typo / non-existent purpose.
    ConsentType::create([
        'name' => 'Marketing Emails',
        'slug' => 'marketing-emails',
        'required' => false,
        'active' => true,
        'category' => 'other',
        'version' => '1.0',
    ]);

    $user = TestUser::create(['name' => 'Prefix', 'email' => 'prefix@example.com']);

    // Giving consent for the non-existent "marketing" must NOT silently grant "marketing-emails".
    expect(fn () => $user->giveConsent('marketing'))
        ->toThrow(ModelNotFoundException::class);

    // And checking it must report false, not leak the longer slug's status.
    expect($user->hasConsent('marketing'))->toBeFalse();
});

test('an exact slug still resolves correctly', function () {
    ConsentType::create([
        'name' => 'Marketing Emails',
        'slug' => 'marketing-emails',
        'required' => false,
        'active' => true,
        'category' => 'other',
        'version' => '1.0',
    ]);

    $user = TestUser::create(['name' => 'Exact', 'email' => 'exact@example.com']);

    $user->giveConsent('marketing-emails');

    expect($user->hasConsent('marketing-emails'))->toBeTrue();
});

test('a stable slug resolves to the current active version after re-versioning', function () {
    $type = ConsentType::create([
        'name' => 'Privacy Policy',
        'slug' => 'privacy-policy',
        'required' => false,
        'active' => true,
        'category' => 'other',
        'version' => '1.0',
    ]);

    $type->createNewVersion(['description' => 'v2']);

    $user = TestUser::create(['name' => 'Versioned', 'email' => 'versioned@example.com']);

    // The stable slug always resolves to the current (active) version of the group.
    $consent = $user->giveConsent('privacy-policy');

    expect($consent->consent_version)->toBe('1.1');
});

test('giveConsent refuses a retired (inactive) consent type (Phase 2 review)', function () {
    ConsentType::create([
        'name' => 'Retired Purpose',
        'slug' => 'retired',
        'required' => false,
        'active' => false, // retired: no effective version
        'category' => 'other',
        'version' => '1.0',
    ]);

    $user = TestUser::create(['name' => 'R', 'email' => 'retired@example.com']);

    expect(fn () => $user->giveConsent('retired'))->toThrow(ModelNotFoundException::class);
    expect($user->consents()->count())->toBe(0);
});

test('daysUntilExpiration rounds up sub-day remainders and never returns null for a dated consent', function () {
    Carbon::setTestNow('2026-01-01 00:00:00');

    ConsentType::create([
        'name' => 'Session',
        'slug' => 'session',
        'required' => false,
        'active' => true,
        'category' => 'cookie',
        'version' => '1.0',
    ]);

    $user = TestUser::create(['name' => 'Days', 'email' => 'days@example.com']);

    $consent = $user->giveConsent('session');

    // Never expires -> null.
    expect($consent->daysUntilExpiration())->toBeNull();

    // 12 hours left must report 1 day, not 0 (which means already expired).
    $consent->expires_at = now()->addHours(12);
    $consent->save();
    expect($consent->daysUntilExpiration())->toBe(1);

    // Already expired -> 0.
    $consent->expires_at = now()->subHour();
    $consent->save();
    expect($consent->daysUntilExpiration())->toBe(0);

    Carbon::setTestNow();
});
