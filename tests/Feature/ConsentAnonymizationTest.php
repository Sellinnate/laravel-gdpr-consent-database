<?php

declare(strict_types=1);

use Selli\LaravelGdprConsentDatabase\Models\ConsentAuditLog;
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;
use Selli\LaravelGdprConsentDatabase\Models\GuestConsent;
use Selli\LaravelGdprConsentDatabase\Models\UserConsent;
use Selli\LaravelGdprConsentDatabase\Services\GuestConsentManager;
use Selli\LaravelGdprConsentDatabase\Tests\Models\TestUser;

beforeEach(function () {
    ConsentType::create([
        'name' => 'Marketing',
        'slug' => 'marketing',
        'required' => false,
        'active' => true,
        'category' => 'other',
        'version' => '1.0',
        'policy_url' => 'https://example.com/policy',
    ]);
});

test('anonymizeConsents pseudonymises the subject and scrubs identifying data', function () {
    $user = TestUser::create(['name' => 'A', 'email' => 'anon@example.com']);
    $user->giveConsent('marketing');

    $result = $user->anonymizeConsents();

    // The original subject no longer has any consent records.
    expect($user->consents()->count())->toBe(0);
    expect($user->consentAuditLogs()->count())->toBe(0);

    // Records now live under the pseudonym, with IP/user-agent scrubbed.
    $consent = UserConsent::query()->where('consentable_id', $result['token'])->first();
    expect($consent)->not->toBeNull();
    expect($consent->ip_address)->toBeNull();
    expect($consent->user_agent)->toBeNull();

    expect($result['consents'])->toBe(1);
    expect($result['token'])->toStartWith('anon_');
});

test('anonymisation preserves the consent proof under the pseudonym', function () {
    $user = TestUser::create(['name' => 'A', 'email' => 'proof@example.com']);
    $user->giveConsent('marketing');

    $result = $user->anonymizeConsents();

    $grantLog = ConsentAuditLog::query()
        ->where('consentable_id', $result['token'])
        ->where('action', 'granted')
        ->first();

    // Proof of what was consented to is intact; identifying data is gone.
    expect($grantLog)->not->toBeNull();
    expect($grantLog->consent_type_slug)->toBe('marketing');
    expect($grantLog->consent_version)->toBe('1.0');
    expect($grantLog->policy_url)->toBe('https://example.com/policy');
    expect($grantLog->ip_address)->toBeNull();
    expect($grantLog->user_agent)->toBeNull();
});

test('anonymisation records an immutable anonymized marker', function () {
    $user = TestUser::create(['name' => 'A', 'email' => 'marker@example.com']);
    $user->giveConsent('marketing');

    $result = $user->anonymizeConsents();

    $marker = ConsentAuditLog::query()
        ->where('consentable_id', $result['token'])
        ->where('action', 'anonymized')
        ->first();

    expect($marker)->not->toBeNull();
    expect($marker->metadata)->toMatchArray(['consents_anonymized' => 1]);
});

test('anonymising a guest scrubs the guest_consents row itself (Phase 2 review)', function () {
    $manager = app(GuestConsentManager::class);
    $sessionId = 'guest-session-erase';

    $manager->giveConsent('marketing', ['source' => 'banner'], null, $sessionId);

    $guest = GuestConsent::find($sessionId);
    // Sanity: the guest row holds identifying data before erasure.
    expect($guest->metadata)->not->toBeNull();

    $guest->anonymizeConsents();

    $guest->refresh();
    expect($guest->ip_address)->toBeNull();
    expect($guest->user_agent)->toBeNull();
    expect($guest->metadata)->toBeNull();
});

test('anonymisation clears personal data stored in consent metadata (Phase 2 review)', function () {
    $user = TestUser::create(['name' => 'A', 'email' => 'meta@example.com']);
    $user->giveConsent('marketing', ['email' => 'meta@example.com', 'source' => 'signup']);

    $result = $user->anonymizeConsents();

    $consent = UserConsent::query()->where('consentable_id', $result['token'])->first();
    expect($consent->metadata)->toBeNull();
});

test('the gdpr:anonymize-subject command anonymises a subject', function () {
    $user = TestUser::create(['name' => 'A', 'email' => 'cmd@example.com']);
    $user->giveConsent('marketing');

    $this->artisan('gdpr:anonymize-subject', [
        'type' => $user->getMorphClass(),
        'id' => $user->getKey(),
        '--token' => 'anon_fixed_token',
    ])->assertSuccessful();

    expect(UserConsent::query()->where('consentable_id', 'anon_fixed_token')->count())->toBe(1);
    expect($user->consents()->count())->toBe(0);
});
