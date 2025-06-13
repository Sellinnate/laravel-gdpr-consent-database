<?php

use Selli\LaravelGdprConsentDatabase\Models\ConsentType;
use Selli\LaravelGdprConsentDatabase\Models\UserConsent;
use Selli\LaravelGdprConsentDatabase\Tests\Models\TestUser;

test('può creare un consenso utente', function () {
    $user = new TestUser([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
    $user->save();

    $consentType = ConsentType::create([
        'name' => 'Marketing Email',
        'slug' => 'marketing-email',
        'description' => 'Consenso per l\'invio di email di marketing',
        'required' => false,
        'active' => true,
    ]);

    $userConsent = new UserConsent([
        'consent_type_id' => $consentType->id,
        'granted' => true,
        'granted_at' => now(),
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit Test',
    ]);

    $user->consents()->save($userConsent);

    expect($userConsent)->toBeInstanceOf(UserConsent::class)
        ->and($userConsent->consentable_id)->toBe($user->id)
        ->and($userConsent->consentable_type)->toBe(get_class($user))
        ->and($userConsent->granted)->toBeTrue()
        ->and($userConsent->granted_at)->not->toBeNull()
        ->and($userConsent->revoked_at)->toBeNull();
});

test('può revocare un consenso utente', function () {
    $user = new TestUser([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
    $user->save();

    $consentType = ConsentType::create([
        'name' => 'Marketing Email',
        'slug' => 'marketing-email',
        'description' => 'Consenso per l\'invio di email di marketing',
        'required' => false,
        'active' => true,
    ]);

    $userConsent = new UserConsent([
        'consent_type_id' => $consentType->id,
        'granted' => true,
        'granted_at' => now(),
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit Test',
    ]);

    $user->consents()->save($userConsent);

    // Revoca il consenso
    $userConsent->update([
        'granted' => false,
        'revoked_at' => now(),
    ]);

    $userConsent->refresh();

    expect($userConsent->granted)->toBeFalse()
        ->and($userConsent->revoked_at)->not->toBeNull();
});

test('può filtrare i consensi attivi', function () {
    $user = new TestUser([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
    $user->save();

    $consentType1 = ConsentType::create([
        'name' => 'Marketing Email',
        'slug' => 'marketing-email',
        'description' => 'Consenso per l\'invio di email di marketing',
        'required' => false,
        'active' => true,
    ]);

    $consentType2 = ConsentType::create([
        'name' => 'Profiling',
        'slug' => 'profiling',
        'description' => 'Consenso per la profilazione',
        'required' => false,
        'active' => true,
    ]);

    // Crea un consenso attivo
    $activeConsent = new UserConsent([
        'consent_type_id' => $consentType1->id,
        'granted' => true,
        'granted_at' => now(),
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit Test',
    ]);
    $user->consents()->save($activeConsent);

    // Crea un consenso revocato
    $revokedConsent = new UserConsent([
        'consent_type_id' => $consentType2->id,
        'granted' => false,
        'granted_at' => now()->subDays(10),
        'revoked_at' => now(),
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit Test',
    ]);
    $user->consents()->save($revokedConsent);

    // Verifica che lo scope active funzioni correttamente
    $activeConsents = $user->consents()->active()->get();
    expect($activeConsents)->toHaveCount(1)
        ->and($activeConsents->first()->id)->toBe($activeConsent->id);

    // Verifica che lo scope revoked funzioni correttamente
    $revokedConsents = $user->consents()->revoked()->get();
    expect($revokedConsents)->toHaveCount(1)
        ->and($revokedConsents->first()->id)->toBe($revokedConsent->id);
});

test('può gestire metadati JSON nei consensi', function () {
    $user = new TestUser([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
    $user->save();

    $consentType = ConsentType::create([
        'name' => 'Marketing Email',
        'slug' => 'marketing-email',
        'description' => 'Consenso per l\'invio di email di marketing',
        'required' => false,
        'active' => true,
    ]);

    $metadata = [
        'source' => 'registration_form',
        'campaign_id' => 'summer_2023',
        'additional_notes' => 'Consenso dato durante la registrazione',
    ];

    $userConsent = new UserConsent([
        'consent_type_id' => $consentType->id,
        'granted' => true,
        'granted_at' => now(),
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit Test',
        'metadata' => $metadata,
    ]);

    $user->consents()->save($userConsent);

    expect($userConsent->metadata)->toBe($metadata);

    // Verifica che i metadati siano correttamente serializzati e deserializzati
    $reloadedConsent = UserConsent::find($userConsent->id);
    expect($reloadedConsent->metadata)->toBe($metadata);
});
