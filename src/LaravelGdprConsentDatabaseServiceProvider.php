<?php

namespace Selli\LaravelGdprConsentDatabase;

use Selli\LaravelGdprConsentDatabase\Commands\LaravelGdprConsentDatabaseCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelGdprConsentDatabaseServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-gdpr-consent-database')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_gdpr_consent_database_table')
            ->hasCommand(LaravelGdprConsentDatabaseCommand::class);
    }
}
