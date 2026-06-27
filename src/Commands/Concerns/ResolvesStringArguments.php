<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase\Commands\Concerns;

use Illuminate\Console\Command;

/**
 * Reads a console argument as a string.
 *
 * Laravel types `argument()` with a conditional return type that resolves differently across PHP
 * versions (a plain `string` on some, the full `array|string|float|int|bool|null` union on others).
 * This helper normalises the value so the commands stay clean under static analysis everywhere.
 *
 * @phpstan-require-extends Command
 */
trait ResolvesStringArguments
{
    protected function stringArgument(string $key): string
    {
        $value = $this->argument($key);

        if (is_array($value)) {
            return '';
        }

        return (string) $value;
    }
}
