<?php

declare(strict_types=1);

use Selli\LaravelGdprConsentDatabase\Models\ConsentAuditLog;
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;
use Selli\LaravelGdprConsentDatabase\Tests\Models\TestUser;

function makePolicyType(): ConsentType
{
    return ConsentType::create([
        'name' => 'Privacy Policy',
        'slug' => 'privacy-policy',
        'required' => true,
        'active' => true,
        'category' => 'other',
        'version' => '1.0',
        'policy_url' => 'https://example.com/privacy/v1',
        'policy_text_hash' => 'abc123hash',
        'legal_basis' => 'consent',
        'purpose' => 'Marketing analytics',
        'data_controller' => 'Acme Srl',
    ]);
}

test('granting consent appends an immutable granted audit entry with a policy snapshot', function () {
    makePolicyType();
    $user = TestUser::create(['name' => 'A', 'email' => 'a@example.com']);

    $user->giveConsent('privacy-policy', ['source' => 'signup']);

    $log = ConsentAuditLog::query()->where('action', 'granted')->latest('id')->first();

    expect($log)->not->toBeNull();
    expect($log->action)->toBe('granted');
    expect($log->consent_type_slug)->toBe('privacy-policy');
    expect($log->consent_version)->toBe('1.0');
    expect($log->policy_url)->toBe('https://example.com/privacy/v1');
    expect($log->policy_text_hash)->toBe('abc123hash');
    expect($log->metadata)->toBe(['source' => 'signup']);
    expect($log->occurred_at)->not->toBeNull();
});

test('revoking consent appends a revoked audit entry', function () {
    makePolicyType();
    $user = TestUser::create(['name' => 'A', 'email' => 'b@example.com']);

    $user->giveConsent('privacy-policy');
    $user->revokeConsent('privacy-policy');

    expect(ConsentAuditLog::query()->where('action', 'granted')->count())->toBe(1);
    expect(ConsentAuditLog::query()->where('action', 'revoked')->count())->toBe(1);
});

test('a revoke audit entry does not inherit the grant metadata (Phase 2 review)', function () {
    makePolicyType();
    $user = TestUser::create(['name' => 'A', 'email' => 'meta-audit@example.com']);

    $user->giveConsent('privacy-policy', ['source' => 'signup']);
    $user->revokeConsent('privacy-policy');

    $grant = ConsentAuditLog::query()->where('action', 'granted')->first();
    $revoke = ConsentAuditLog::query()->where('action', 'revoked')->first();

    expect($grant->metadata)->toBe(['source' => 'signup']);
    expect($revoke->metadata)->toBeNull();
});

test('superseding a consent records a revoke for the old and a grant for the new', function () {
    $type = makePolicyType();
    $user = TestUser::create(['name' => 'A', 'email' => 'c@example.com']);

    $user->giveConsent('privacy-policy');           // grant v1.0
    $type->createNewVersion(['policy_url' => 'https://example.com/privacy/v2']);
    $user->unsetRelation('consents');
    $user->giveConsent('privacy-policy');           // supersede: revoke v1.0 + grant v1.1

    expect(ConsentAuditLog::query()->where('action', 'granted')->count())->toBe(2);
    expect(ConsentAuditLog::query()->where('action', 'revoked')->count())->toBe(1);

    $latestGrant = ConsentAuditLog::query()->where('action', 'granted')->latest('id')->first();
    expect($latestGrant->consent_version)->toBe('1.1');
    expect($latestGrant->policy_url)->toBe('https://example.com/privacy/v2');
});

test('the audit trail is exposed via the consentAuditLogs relation', function () {
    makePolicyType();
    $user = TestUser::create(['name' => 'A', 'email' => 'd@example.com']);

    $user->giveConsent('privacy-policy');
    $user->revokeConsent('privacy-policy');

    expect($user->consentAuditLogs()->count())->toBe(2);
    // Ordered most-recent first.
    expect($user->consentAuditLogs()->first()->action)->toBe('revoked');
});

test('audit log entries cannot be updated', function () {
    makePolicyType();
    $user = TestUser::create(['name' => 'A', 'email' => 'e@example.com']);
    $user->giveConsent('privacy-policy');

    $log = ConsentAuditLog::query()->first();

    expect(function () use ($log) {
        $log->action = 'tampered';
        $log->save();
    })->toThrow(RuntimeException::class);
});

test('audit log entries cannot be deleted', function () {
    makePolicyType();
    $user = TestUser::create(['name' => 'A', 'email' => 'f@example.com']);
    $user->giveConsent('privacy-policy');

    $log = ConsentAuditLog::query()->first();

    expect(fn () => $log->delete())->toThrow(RuntimeException::class);
});

test('audit proof survives deletion of the consent type', function () {
    $type = makePolicyType();
    $user = TestUser::create(['name' => 'A', 'email' => 'g@example.com']);
    $user->giveConsent('privacy-policy');

    // Deleting the consent type must NOT destroy the audit proof (nullOnDelete).
    $type->delete();

    $log = ConsentAuditLog::query()->where('action', 'granted')->first();
    expect($log)->not->toBeNull();
    expect($log->consent_type_id)->toBeNull();
    // The snapshotted slug/version/policy remain as proof.
    expect($log->consent_type_slug)->toBe('privacy-policy');
    expect($log->consent_version)->toBe('1.0');
    expect($log->policy_url)->toBe('https://example.com/privacy/v1');
});
