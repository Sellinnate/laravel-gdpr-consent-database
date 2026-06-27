<?php

declare(strict_types=1);

use Selli\LaravelGdprConsentDatabase\Models\ConsentType;
use Selli\LaravelGdprConsentDatabase\Models\GuestConsent;
use Selli\LaravelGdprConsentDatabase\Services\GuestConsentManager;
use Selli\LaravelGdprConsentDatabase\Support\IpAddress;
use Selli\LaravelGdprConsentDatabase\Tests\Models\TestUser;

test('IpAddress masks the last octet of an IPv4 address', function () {
    expect(IpAddress::anonymize('203.0.113.42'))->toBe('203.0.113.0');
});

test('IpAddress masks the trailing bits of an IPv6 address', function () {
    // Keeps the /48 network prefix, zeroes the remaining 80 bits.
    expect(IpAddress::anonymize('2001:db8:1234:5678:9abc:def0:1234:5678'))->toBe('2001:db8:1234::');
});

test('forStorage returns null for a null or empty IP', function () {
    expect(IpAddress::forStorage(null))->toBeNull();
    expect(IpAddress::forStorage(''))->toBeNull();
});

test('anonymize returns a non-IP string unchanged', function () {
    expect(IpAddress::anonymize('not-an-ip'))->toBe('not-an-ip');
});

test('forStorage returns null when IP storage is disabled', function () {
    config()->set('gdpr-consent-database.privacy.store_ip_address', false);

    expect(IpAddress::forStorage('203.0.113.42'))->toBeNull();
});

test('forStorage returns the masked IP when anonymisation is enabled', function () {
    config()->set('gdpr-consent-database.privacy.anonymize_ip', true);

    expect(IpAddress::forStorage('203.0.113.42'))->toBe('203.0.113.0');
});

test('forStorage returns the raw IP by default', function () {
    expect(IpAddress::forStorage('203.0.113.42'))->toBe('203.0.113.42');
});

test('giveConsent stores an anonymised IP when configured', function () {
    config()->set('gdpr-consent-database.privacy.anonymize_ip', true);

    ConsentType::create([
        'name' => 'Marketing', 'slug' => 'marketing',
        'required' => false, 'active' => true, 'category' => 'other', 'version' => '1.0',
    ]);

    $user = TestUser::create(['name' => 'A', 'email' => 'ip@example.com']);
    $consent = $user->giveConsent('marketing');

    // The test request IP (127.0.0.1) must be masked.
    expect($consent->ip_address)->toBe('127.0.0.0');
});

test('guest consent records anonymise the IP too (Phase 4 review BLOCKER)', function () {
    config()->set('gdpr-consent-database.privacy.anonymize_ip', true);

    ConsentType::create([
        'name' => 'Marketing', 'slug' => 'marketing',
        'required' => false, 'active' => true, 'category' => 'other', 'version' => '1.0',
    ]);

    $manager = app(GuestConsentManager::class);
    $manager->giveConsent('marketing', [], null, 'guest-ip-session');

    $guest = GuestConsent::find('guest-ip-session');

    // The guest_consents row itself must not store a raw IP.
    expect($guest->ip_address)->toBe('127.0.0.0');
});

test('giveConsent stores no IP when storage is disabled', function () {
    config()->set('gdpr-consent-database.privacy.store_ip_address', false);

    ConsentType::create([
        'name' => 'Marketing', 'slug' => 'marketing',
        'required' => false, 'active' => true, 'category' => 'other', 'version' => '1.0',
    ]);

    $user = TestUser::create(['name' => 'A', 'email' => 'noip@example.com']);
    $consent = $user->giveConsent('marketing');

    expect($consent->ip_address)->toBeNull();
});
