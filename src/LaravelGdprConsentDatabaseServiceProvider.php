<?php

namespace Selli\LaravelGdprConsentDatabase;

use Illuminate\Support\Facades\Blade;
use Selli\LaravelGdprConsentDatabase\Commands\LaravelGdprConsentDatabaseCommand;
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
            ->hasCommand(LaravelGdprConsentDatabaseCommand::class);

        $migrationPath = __DIR__.'/../database/migrations/';
        if (file_exists($migrationPath)) {
            foreach (\Illuminate\Support\Facades\File::files($migrationPath) as $migration) {
                $package->hasMigration($migration->getBasename());
            }
        }
    }

    public function boot()
    {
        parent::boot();

        $this->registerBladeDirectives();
        $this->registerServices();
    }

    protected function registerBladeDirectives()
    {
        Blade::directive('gdprCookieBanner', function ($expression) {
            return "<?php echo view('gdpr-consent-database::cookie-banner', $expression ?: [])->render(); ?>";
        });
    }

    protected function registerServices()
    {
        $this->app->singleton(GuestConsentManager::class, function ($app) {
            return new GuestConsentManager;
        });
    }
}
