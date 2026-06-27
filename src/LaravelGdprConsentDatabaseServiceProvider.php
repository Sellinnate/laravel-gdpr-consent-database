<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase;

use Illuminate\Support\Facades\Blade;
use Selli\LaravelGdprConsentDatabase\Services\GuestConsentManager;
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
            ->hasRoutes('web')
            ->hasMigration('1_create_consent_types_table')
            ->hasMigration('2_create_user_consents_table')
            ->hasMigration('3_add_versioning_to_consent_types_table')
            ->hasMigration('4_add_expiration_to_user_consents_table')
            ->hasMigration('5_create_guest_consents_table')
            ->hasMigration('6_add_category_to_consent_types_table');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(GuestConsentManager::class, fn ($app) => new GuestConsentManager);
    }

    public function packageBooted(): void
    {
        $this->registerBladeDirectives();
    }

    protected function registerBladeDirectives(): void
    {
        Blade::directive('gdprCookieBanner', function (?string $expression = null) {
            $expression = ($expression === null || trim($expression) === '') ? '[]' : $expression;

            return "<?php echo view('gdpr-consent-database::cookie-banner', $expression)->render(); ?>";
        });
    }
}
