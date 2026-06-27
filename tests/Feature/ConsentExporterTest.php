<?php

declare(strict_types=1);

use Selli\LaravelGdprConsentDatabase\Models\ConsentType;
use Selli\LaravelGdprConsentDatabase\Models\GuestConsent;
use Selli\LaravelGdprConsentDatabase\Services\ConsentExporter;
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
        'legal_basis' => 'consent',
        'purpose' => 'Marketing communications',
        'data_controller' => 'Acme Srl',
    ]);
});

test('the export includes the consent type purpose and legal basis (Art. 30)', function () {
    $user = TestUser::create(['name' => 'A', 'email' => 'art30@example.com']);
    $user->giveConsent('marketing');

    $export = app(ConsentExporter::class)->exportModel($user);

    expect($export['consents'][0])->toMatchArray([
        'purpose' => 'Marketing communications',
        'legal_basis' => 'consent',
        'data_controller' => 'Acme Srl',
    ]);
});

test('the export discloses the guest_consents row for a guest subject (Art. 15)', function () {
    app(GuestConsentManager::class)->giveConsent('marketing', [], null, 'export-guest-session');

    $export = app(ConsentExporter::class)->export(
        (new GuestConsent)->getMorphClass(),
        'export-guest-session',
    );

    expect($export)->toHaveKey('guest');
    expect($export['guest']['session_id'])->toBe('export-guest-session');
    expect($export['guest'])->toHaveKeys(['ip_address', 'user_agent', 'metadata']);
});

test('the exporter returns a subject\'s consents and audit trail', function () {
    $user = TestUser::create(['name' => 'A', 'email' => 'export@example.com']);
    $user->giveConsent('marketing', ['source' => 'signup']);
    $user->revokeConsent('marketing');

    $export = app(ConsentExporter::class)->exportModel($user);

    expect($export['subject']['type'])->toBe($user->getMorphClass());
    expect($export['consents'])->toHaveCount(1);
    expect($export['consents'][0]['consent_type'])->toBe('marketing');
    // A subject access request must disclose all personal data held, incl. IP / user agent.
    expect($export['consents'][0])->toHaveKeys(['ip_address', 'user_agent']);
    // grant + revoke
    expect($export['audit_trail'])->toHaveCount(2);
    expect($export['audit_trail'][0]['action'])->toBe('granted');
    expect($export['audit_trail'][1]['action'])->toBe('revoked');
});

test('the exporter produces valid JSON', function () {
    $user = TestUser::create(['name' => 'A', 'email' => 'json@example.com']);
    $user->giveConsent('marketing');

    $json = app(ConsentExporter::class)->toJson($user->getMorphClass(), (string) $user->getKey());

    expect(json_decode($json, true))->toBeArray()->toHaveKey('consents');
});

test('the gdpr:consents:export command outputs JSON', function () {
    $user = TestUser::create(['name' => 'A', 'email' => 'cmd-export@example.com']);
    $user->giveConsent('marketing');

    $this->artisan('gdpr:consents:export', [
        'type' => $user->getMorphClass(),
        'id' => $user->getKey(),
    ])->assertSuccessful();
});
