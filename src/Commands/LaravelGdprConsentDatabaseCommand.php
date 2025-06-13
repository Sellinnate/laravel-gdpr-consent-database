<?php

namespace Selli\LaravelGdprConsentDatabase\Commands;

use Illuminate\Console\Command;

class LaravelGdprConsentDatabaseCommand extends Command
{
    public $signature = 'laravel-gdpr-consent-database';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
