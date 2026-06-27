<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Selli\LaravelGdprConsentDatabase\Events\ConsentGranted;
use Selli\LaravelGdprConsentDatabase\Events\ConsentRenewed;
use Selli\LaravelGdprConsentDatabase\Events\ConsentRevoked;
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;
use Selli\LaravelGdprConsentDatabase\Tests\Models\TestUser;

beforeEach(function () {
    ConsentType::create([
        'name' => 'Marketing',
        'slug' => 'marketing',
        'required' => false,
        'active' => true,
        'category' => 'other',
        'version' => '1.0',
    ]);
});

test('giveConsent dispatches ConsentGranted', function () {
    Event::fake();
    $user = TestUser::create(['name' => 'A', 'email' => 'g@example.com']);

    $consent = $user->giveConsent('marketing');

    Event::assertDispatched(
        ConsentGranted::class,
        fn (ConsentGranted $e) => $e->consentable->is($user) && $e->consent->is($consent)
    );
    Event::assertNotDispatched(ConsentRevoked::class);
});

test('revokeConsent dispatches ConsentRevoked', function () {
    $user = TestUser::create(['name' => 'A', 'email' => 'r@example.com']);
    $user->giveConsent('marketing');

    Event::fake();
    $user->revokeConsent('marketing');

    Event::assertDispatched(ConsentRevoked::class);
});

test('superseding via giveConsent does not dispatch a revoke event', function () {
    $user = TestUser::create(['name' => 'A', 'email' => 's@example.com']);
    $user->giveConsent('marketing');

    Event::fake();
    $user->giveConsent('marketing'); // supersede

    Event::assertDispatched(ConsentGranted::class);
    Event::assertNotDispatched(ConsentRevoked::class);
});

test('renewConsent dispatches ConsentRenewed and not ConsentGranted', function () {
    $type = ConsentType::where('slug', 'marketing')->first();
    $user = TestUser::create(['name' => 'A', 'email' => 'rn@example.com']);
    $user->giveConsent('marketing');
    $type->createNewVersion();
    $user->unsetRelation('consents');

    Event::fake();
    $user->renewConsent('marketing');

    Event::assertDispatched(ConsentRenewed::class);
    Event::assertNotDispatched(ConsentGranted::class);
});
