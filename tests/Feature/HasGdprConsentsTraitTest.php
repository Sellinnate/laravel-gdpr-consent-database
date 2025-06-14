<?php

use Selli\LaravelGdprConsentDatabase\Models\ConsentType;
use Selli\LaravelGdprConsentDatabase\Tests\Models\TestUser;

test('può verificare se un utente ha dato un consenso specifico', function () {
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
        'category' => 'other',
    ]);

    // Inizialmente l'utente non ha dato il consenso
    expect($user->hasConsent($consentType->id))->toBeFalse();
    expect($user->hasConsent('marketing-email'))->toBeFalse();

    // L'utente dà il consenso
    $user->giveConsent($consentType->id);

    // Ora l'utente dovrebbe avere il consenso
    expect($user->hasConsent($consentType->id))->toBeTrue();
    expect($user->hasConsent('marketing-email'))->toBeTrue();
});

test('può dare consenso usando l\'ID o lo slug', function () {
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
        'category' => 'other',
    ]);

    // Dà il consenso usando l'ID
    $user->giveConsent($consentType->id);
    expect($user->hasConsent($consentType->id))->toBeTrue();

    // Revoca il consenso
    $user->revokeConsent($consentType->id);
    expect($user->hasConsent($consentType->id))->toBeFalse();

    // Dà il consenso usando lo slug
    $user->giveConsent('marketing-email');
    expect($user->hasConsent('marketing-email'))->toBeTrue();
});

test('può revocare un consenso', function () {
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
        'category' => 'other',
    ]);

    // L'utente dà il consenso
    $user->giveConsent($consentType->id);
    expect($user->hasConsent($consentType->id))->toBeTrue();

    // L'utente revoca il consenso
    $user->revokeConsent($consentType->id);
    expect($user->hasConsent($consentType->id))->toBeFalse();
});

test('può ottenere tutti i consensi attivi', function () {
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
        'category' => 'other',
    ]);

    $consentType2 = ConsentType::create([
        'name' => 'Profiling',
        'slug' => 'profiling',
        'description' => 'Consenso per la profilazione',
        'required' => false,
        'active' => true,
        'category' => 'cookie',
    ]);

    // L'utente dà il consenso solo al primo tipo
    $user->giveConsent($consentType1->id);

    // Verifica che activeConsents restituisca solo il consenso attivo
    $activeConsents = $user->activeConsents();
    expect($activeConsents)->toHaveCount(1)
        ->and($activeConsents->first()->consent_type_id)->toBe($consentType1->id);
});

test('può verificare i consensi obbligatori mancanti', function () {
    $user = new TestUser([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
    $user->save();

    // Crea un tipo di consenso obbligatorio
    $requiredConsentType = ConsentType::create([
        'name' => 'Terms and Conditions',
        'slug' => 'terms',
        'description' => 'Accettazione dei termini e condizioni',
        'required' => true,
        'active' => true,
        'category' => 'other',
    ]);

    // Crea un tipo di consenso non obbligatorio
    $optionalConsentType = ConsentType::create([
        'name' => 'Marketing Email',
        'slug' => 'marketing-email',
        'description' => 'Consenso per l\'invio di email di marketing',
        'required' => false,
        'active' => true,
        'category' => 'other',
    ]);

    // Verifica che l'utente manchi del consenso obbligatorio
    $missingConsents = $user->getMissingRequiredConsents();
    expect($missingConsents)->toHaveCount(1)
        ->and($missingConsents->first()->id)->toBe($requiredConsentType->id);

    // Verifica che l'utente non abbia tutti i consensi obbligatori
    expect($user->hasAllRequiredConsents())->toBeFalse();

    // L'utente dà il consenso obbligatorio
    $user->giveConsent($requiredConsentType->id);

    // Ora l'utente dovrebbe avere tutti i consensi obbligatori
    expect($user->getMissingRequiredConsents())->toHaveCount(0);
    expect($user->hasAllRequiredConsents())->toBeTrue();
});

test('può gestire metadati nei consensi tramite trait', function () {
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
        'category' => 'other',
    ]);

    $metadata = [
        'source' => 'registration_form',
        'campaign_id' => 'summer_2023',
    ];

    // L'utente dà il consenso con metadati
    $consent = $user->giveConsent($consentType->id, $metadata);

    expect($consent->metadata)->toBe($metadata);
});
