<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase\Commands;

use Illuminate\Console\Command;
use Selli\LaravelGdprConsentDatabase\Services\ConsentExporter;

class ExportConsentsCommand extends Command
{
    /** @var string */
    protected $signature = 'gdpr:consents:export
        {type : The stored consentable type (morph alias or fully-qualified class name)}
        {id : The subject identifier}
        {--path= : Write the JSON export to this file instead of stdout}';

    /** @var string */
    protected $description = 'Export a subject\'s consents and audit trail as JSON (GDPR Art. 15 / 20).';

    public function handle(ConsentExporter $exporter): int
    {
        $json = $exporter->toJson($this->argument('type'), $this->argument('id'));

        $path = $this->option('path');

        if (is_string($path) && $path !== '') {
            file_put_contents($path, $json);
            $this->info("Export written to {$path}.");

            return self::SUCCESS;
        }

        $this->line($json);

        return self::SUCCESS;
    }
}
