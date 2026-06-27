<?php

namespace Selli\LaravelGdprConsentDatabase\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as Orchestra;
use Selli\LaravelGdprConsentDatabase\LaravelGdprConsentDatabaseServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Selli\\LaravelGdprConsentDatabase\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelGdprConsentDatabaseServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        // Include package migrations
        $migrationPath = __DIR__.'/../database/migrations/';
        if (file_exists($migrationPath)) {
            foreach (File::files($migrationPath) as $migration) {
                (include $migration->getRealPath())->up();
            }
        }

        // Include test migrations
        foreach (File::files(__DIR__.'/database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
        }
    }
}
