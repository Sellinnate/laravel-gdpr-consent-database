<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;

beforeEach(function () {
    ConsentType::create([
        'name' => 'Technical',
        'slug' => 'technical',
        'required' => true,
        'active' => true,
        'category' => 'cookie',
    ]);
});

test('@gdprCookieBanner directive compiles and renders without arguments', function () {
    $html = Blade::render('@gdprCookieBanner');

    expect($html)
        ->toContain('gdpr-cookie-banner')
        ->toContain(config('gdpr-consent-database.text.title'));
});

test('@gdprCookieBanner directive compiles with empty parentheses', function () {
    // Blade passes an empty string for @directive() — must not produce malformed view(..., ) PHP.
    $html = Blade::render('@gdprCookieBanner()');

    expect($html)->toContain('gdpr-cookie-banner');
});

test('@gdprCookieBanner directive forwards custom options', function () {
    $html = Blade::render("@gdprCookieBanner(['title' => 'My Custom Banner Title'])");

    expect($html)->toContain('My Custom Banner Title');
});
