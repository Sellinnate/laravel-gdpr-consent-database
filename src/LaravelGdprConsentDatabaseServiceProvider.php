<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Selli\LaravelGdprConsentDatabase\Commands\AnonymizeSubjectCommand;
use Selli\LaravelGdprConsentDatabase\Commands\ExpireConsentsCommand;
use Selli\LaravelGdprConsentDatabase\Commands\ExportConsentsCommand;
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
            ->hasMigration('1_create_consent_types_table')
            ->hasMigration('2_create_user_consents_table')
            ->hasMigration('3_add_versioning_to_consent_types_table')
            ->hasMigration('4_add_expiration_to_user_consents_table')
            ->hasMigration('5_create_guest_consents_table')
            ->hasMigration('6_add_category_to_consent_types_table')
            ->hasMigration('7_add_compliance_fields_to_consent_types_table')
            ->hasMigration('8_create_consent_audit_logs_table')
            ->hasCommands([
                AnonymizeSubjectCommand::class,
                ExportConsentsCommand::class,
                ExpireConsentsCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(GuestConsentManager::class, fn ($app) => new GuestConsentManager);
    }

    public function packageBooted(): void
    {
        $this->registerBladeDirectives();
        $this->registerRoutes();
    }

    /**
     * Register the package routes inside a configurable group (prefix / name / middleware).
     */
    protected function registerRoutes(): void
    {
        $config = config('gdpr-consent-database.routes');
        $config = is_array($config) ? $config : [];

        if (! ($config['enabled'] ?? true)) {
            return;
        }

        Route::group([
            'prefix' => $config['prefix'] ?? 'gdpr/consent',
            'as' => $config['name'] ?? 'gdpr.consent.',
            'middleware' => $config['middleware'] ?? ['web'],
        ], function (): void {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    protected function registerBladeDirectives(): void
    {
        Blade::directive('gdprCookieBanner', function (?string $expression = null) {
            $expression = ($expression === null || trim($expression) === '') ? '[]' : $expression;

            return "<?php echo view('gdpr-consent-database::cookie-banner', $expression)->render(); ?>";
        });
    }
}
