<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase\Support;

/**
 * Small helpers to keep config-provided values safe when interpolated into a `<style>` block.
 */
class Css
{
    /**
     * Validate a CSS colour value, returning the fallback when it does not match a safe pattern.
     *
     * Accepts hex (#rgb / #rrggbb / #rrggbbaa), rgb()/rgba()/hsl()/hsla() and plain colour keywords.
     * This is defence-in-depth: config is developer-controlled, but it prevents a stray `;`/`}` from
     * breaking out of the CSS rule.
     */
    public static function color(string $value, string $fallback): string
    {
        $value = trim($value);

        $patterns = [
            '/^#(?:[0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/',
            '/^rgba?\(\s*[0-9.,\s%]+\)$/i',
            '/^hsla?\(\s*[0-9.,\s%]+\)$/i',
            '/^[a-zA-Z]+$/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value) === 1) {
                return $value;
            }
        }

        return $fallback;
    }
}
