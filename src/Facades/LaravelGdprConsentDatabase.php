<?php

namespace Selli\LaravelGdprConsentDatabase\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Selli\LaravelGdprConsentDatabase\LaravelGdprConsentDatabase
 */
class LaravelGdprConsentDatabase extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Selli\LaravelGdprConsentDatabase\LaravelGdprConsentDatabase::class;
    }
}
