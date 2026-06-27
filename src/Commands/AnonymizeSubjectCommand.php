<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase\Commands;

use Illuminate\Console\Command;
use Selli\LaravelGdprConsentDatabase\Commands\Concerns\ResolvesStringArguments;
use Selli\LaravelGdprConsentDatabase\Services\ConsentAnonymizer;

class AnonymizeSubjectCommand extends Command
{
    use ResolvesStringArguments;

    /** @var string */
    protected $signature = 'gdpr:anonymize-subject
        {type : The stored consentable type (morph alias or fully-qualified class name)}
        {id : The subject identifier}
        {--token= : Use a specific pseudonym instead of a random one}';

    /** @var string */
    protected $description = 'Anonymise (pseudonymise) all consent records of a subject for a GDPR Art. 17 erasure request, preserving the audit proof.';

    public function handle(ConsentAnonymizer $anonymizer): int
    {
        $token = $this->option('token');

        $result = $anonymizer->anonymize(
            $this->stringArgument('type'),
            $this->stringArgument('id'),
            is_string($token) ? $token : null,
        );

        $this->info('Subject anonymised.');
        $this->table(
            ['Pseudonym', 'Consents anonymised', 'Audit logs anonymised'],
            [[$result['token'], $result['consents'], $result['audit_logs']]],
        );

        return self::SUCCESS;
    }
}
