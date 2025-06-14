<?php

use Selli\LaravelGdprConsentDatabase\Models\ConsentType;

test('può creare un tipo di consenso', function () {
    $consentType = ConsentType::create([
        'name' => 'Marketing Email',
        'slug' => 'marketing-email',
        'description' => 'Consenso per l\'invio di email di marketing',
        'required' => false,
        'active' => true,
        'category' => 'other',
    ]);

    expect($consentType)->toBeInstanceOf(ConsentType::class)
        ->and($consentType->name)->toBe('Marketing Email')
        ->and($consentType->slug)->toBe('marketing-email')
        ->and($consentType->description)->toBe('Consenso per l\'invio di email di marketing')
        ->and($consentType->required)->toBeFalse()
        ->and($consentType->active)->toBeTrue();
});

test('può creare un tipo di consenso con factory', function () {
    $consentType = ConsentType::factory()->create();

    expect($consentType)->toBeInstanceOf(ConsentType::class)
        ->and($consentType->exists)->toBeTrue();
});

test('può creare un tipo di consenso obbligatorio con factory', function () {
    $consentType = ConsentType::factory()->required()->create();

    expect($consentType)->toBeInstanceOf(ConsentType::class)
        ->and($consentType->required)->toBeTrue();
});

test('può creare un tipo di consenso inattivo con factory', function () {
    $consentType = ConsentType::factory()->inactive()->create();

    expect($consentType)->toBeInstanceOf(ConsentType::class)
        ->and($consentType->active)->toBeFalse();
});

test('può gestire metadati JSON', function () {
    $metadata = [
        'legal_reference' => 'GDPR Art. 7',
        'version' => '1.0',
        'display_order' => 1,
    ];

    $consentType = ConsentType::create([
        'name' => 'Cookie Tecnici',
        'slug' => 'technical-cookies',
        'description' => 'Consenso per i cookie tecnici essenziali',
        'required' => true,
        'active' => true,
        'category' => 'cookie',
        'metadata' => $metadata,
    ]);

    expect($consentType->metadata)->toBe($metadata);

    // Verifica che i metadati siano correttamente serializzati e deserializzati
    $reloadedConsentType = ConsentType::find($consentType->id);
    expect($reloadedConsentType->metadata)->toBe($metadata);
});
