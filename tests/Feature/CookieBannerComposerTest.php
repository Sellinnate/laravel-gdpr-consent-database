<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;

test('the view composer provides cookie consent types without a query in the view', function () {
    ConsentType::create([
        'name' => 'Analytics Cookies',
        'slug' => 'analytics-cookies',
        'required' => false,
        'active' => true,
        'category' => 'cookie',
        'version' => '1.0',
    ]);

    // No consentTypes passed: the composer must supply them.
    $html = Blade::render('@gdprCookieBanner');

    expect($html)->toContain('Analytics Cookies');
});

test('the composer only lists active cookie-category types', function () {
    ConsentType::create([
        'name' => 'Active Cookie', 'slug' => 'active-cookie',
        'required' => false, 'active' => true, 'category' => 'cookie', 'version' => '1.0',
    ]);
    ConsentType::create([
        'name' => 'Inactive Cookie', 'slug' => 'inactive-cookie',
        'required' => false, 'active' => false, 'category' => 'cookie', 'version' => '1.0',
    ]);
    ConsentType::create([
        'name' => 'Other Purpose', 'slug' => 'other-purpose',
        'required' => false, 'active' => true, 'category' => 'other', 'version' => '1.0',
    ]);

    $html = Blade::render('@gdprCookieBanner');

    expect($html)->toContain('Active Cookie');
    expect($html)->not->toContain('Inactive Cookie');
    expect($html)->not->toContain('Other Purpose');
});

test('the banner exposes accessibility attributes', function () {
    $html = Blade::render('@gdprCookieBanner');

    expect($html)
        ->toContain('role="region"')
        ->toContain('aria-labelledby="gdpr-banner-title"')
        ->toContain('aria-label="Close cookie banner"');
});

test('optional cookie checkboxes are not pre-checked, required ones are checked+disabled', function () {
    ConsentType::create([
        'name' => 'Required Cookie', 'slug' => 'required-cookie',
        'required' => true, 'active' => true, 'category' => 'cookie', 'version' => '1.0',
    ]);
    ConsentType::create([
        'name' => 'Optional Cookie', 'slug' => 'optional-cookie',
        'required' => false, 'active' => true, 'category' => 'cookie', 'version' => '1.0',
    ]);

    $html = Blade::render('@gdprCookieBanner');

    // Required: checked + disabled. Optional: neither (no pre-ticked opt-in).
    expect($html)->toMatch('/name="consent\[required-cookie\]"[^>]*checked disabled/');
    expect($html)->not->toMatch('/name="consent\[optional-cookie\]"[^>]*checked/');
});
