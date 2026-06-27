<?php

declare(strict_types=1);

use Selli\LaravelGdprConsentDatabase\Support\Css;

test('Css::color accepts valid colour formats', function (string $value) {
    expect(Css::color($value, '#000'))->toBe($value);
})->with([
    '#fff',
    '#ffffff',
    '#ffffffaa',
    'rgba(0,0,0,0.1)',
    'rgb(255, 0, 0)',
    'hsl(120, 50%, 50%)',
    'red',
]);

test('Css::color rejects values that could break out of the CSS rule', function (string $value) {
    expect(Css::color($value, '#000'))->toBe('#000');
})->with([
    'red; } body { display:none',
    '#fff} .x{color:red}',
    'url(javascript:alert(1))',
    'expression(alert(1))',
    '<script>',
]);
