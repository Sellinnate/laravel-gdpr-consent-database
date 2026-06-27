<?php

declare(strict_types=1);

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Selli\LaravelGdprConsentDatabase\Events\ConsentExpired;
use Selli\LaravelGdprConsentDatabase\Models\ConsentAuditLog;
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;
use Selli\LaravelGdprConsentDatabase\Tests\Models\TestUser;

test('gdpr:consents:expire closes expired consents, audits and dispatches an event', function () {
    Carbon::setTestNow('2026-01-01 00:00:00');

    ConsentType::create([
        'name' => 'Marketing',
        'slug' => 'marketing',
        'required' => false,
        'active' => true,
        'category' => 'other',
        'version' => '1.0',
        'validity_months' => 1,
    ]);

    $user = TestUser::create(['name' => 'A', 'email' => 'expire@example.com']);
    $consent = $user->giveConsent('marketing'); // expires in 1 month

    // Move past expiry.
    Carbon::setTestNow('2026-03-01 00:00:00');

    Event::fake();
    $this->artisan('gdpr:consents:expire')->assertSuccessful();

    Event::assertDispatched(ConsentExpired::class);

    expect($consent->fresh()->granted)->toBeFalse();
    expect(ConsentAuditLog::where('action', 'expired')->count())->toBe(1);

    Carbon::setTestNow();
});

test('gdpr:consents:expire ignores still-valid consents', function () {
    Carbon::setTestNow('2026-01-01 00:00:00');

    ConsentType::create([
        'name' => 'Marketing',
        'slug' => 'marketing',
        'required' => false,
        'active' => true,
        'category' => 'other',
        'version' => '1.0',
        'validity_months' => 12,
    ]);

    $user = TestUser::create(['name' => 'A', 'email' => 'valid@example.com']);
    $consent = $user->giveConsent('marketing');

    $this->artisan('gdpr:consents:expire')->assertSuccessful();

    expect($consent->fresh()->granted)->toBeTrue();
    expect(ConsentAuditLog::where('action', 'expired')->count())->toBe(0);

    Carbon::setTestNow();
});
