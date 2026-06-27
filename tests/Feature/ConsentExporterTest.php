<?php

declare(strict_types=1);

use Selli\LaravelGdprConsentDatabase\Models\ConsentType;
use Selli\LaravelGdprConsentDatabase\Services\ConsentExporter;
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
