<?php

use Selli\LaravelGdprConsentDatabase\Models\ConsentType;
use Selli\LaravelGdprConsentDatabase\Tests\Models\TestUser;

test('flusso completo di gestione del consenso', function () {
    // Creiamo un utente di test
    $user = new TestUser([
        'name' => 'Mario Rossi',
        'email' => 'mario.rossi@example.com',
    ]);
    $user->save();

    // Creiamo diversi tipi di consenso
    $marketingConsent = ConsentType::create([
        'name' => 'Marketing Email',
        'slug' => 'marketing-email',
        'description' => 'Consenso per l\'invio di email di marketing',
        'required' => false,
        'active' => true,
    ]);

    $termsConsent = ConsentType::create([
        'name' => 'Termini e Condizioni',
        'slug' => 'terms',
        'description' => 'Accettazione dei termini e condizioni',
        'required' => true,
        'active' => true,
    ]);

    $profilingConsent = ConsentType::create([
        'name' => 'Profilazione',
        'slug' => 'profiling',
        'description' => 'Consenso per la profilazione delle preferenze',
        'required' => false,
        'active' => true,
    ]);

    // Verifichiamo che l'utente non abbia ancora dato alcun consenso
    expect($user->consents()->count())->toBe(0);
    expect($user->hasConsent('marketing-email'))->toBeFalse();
    expect($user->hasConsent('terms'))->toBeFalse();
    expect($user->hasConsent('profiling'))->toBeFalse();

    // Verifichiamo che l'utente manchi del consenso obbligatorio
    expect($user->getMissingRequiredConsents()->count())->toBe(1);
    expect($user->getMissingRequiredConsents()->first()->slug)->toBe('terms');
    expect($user->hasAllRequiredConsents())->toBeFalse();

    // L'utente dà il consenso ai termini e condizioni
    $termsConsentRecord = $user->giveConsent('terms', [
        'source' => 'registration_form',
        'version' => '1.0',
    ]);

    // Verifichiamo che l'utente abbia ora il consenso obbligatorio
    expect($user->hasConsent('terms'))->toBeTrue();
    expect($user->hasAllRequiredConsents())->toBeTrue();
    expect($user->getMissingRequiredConsents()->count())->toBe(0);
    expect($termsConsentRecord->metadata)->toBe([
        'source' => 'registration_form',
        'version' => '1.0',
    ]);

    // L'utente dà il consenso al marketing
    $user->giveConsent($marketingConsent->id);
    expect($user->hasConsent('marketing-email'))->toBeTrue();

    // L'utente non dà il consenso alla profilazione
    expect($user->hasConsent('profiling'))->toBeFalse();

    // Verifichiamo che l'utente abbia 2 consensi attivi
    expect($user->activeConsents()->count())->toBe(2);

    // L'utente revoca il consenso al marketing
    $user->revokeConsent('marketing-email');
    expect($user->hasConsent('marketing-email'))->toBeFalse();

    // Verifichiamo che l'utente abbia ora 1 solo consenso attivo
    expect($user->activeConsents()->count())->toBe(1);

    // Verifichiamo che il consenso revocato sia correttamente registrato
    $revokedConsent = $user->consents()
        ->where('consent_type_id', $marketingConsent->id)
        ->first();

    expect($revokedConsent->granted)->toBeFalse();
    expect($revokedConsent->revoked_at)->not->toBeNull();

    // L'utente dà nuovamente il consenso al marketing
    $user->giveConsent('marketing-email');
    expect($user->hasConsent('marketing-email'))->toBeTrue();

    // Verifichiamo che ci siano 2 record per il consenso marketing (uno revocato, uno attivo)
    expect($user->consents()->where('consent_type_id', $marketingConsent->id)->count())->toBe(2);

    // Verifichiamo che l'utente abbia nuovamente 2 consensi attivi
    expect($user->activeConsents()->count())->toBe(2);
});
