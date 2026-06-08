<?php

declare(strict_types=1);

namespace Moox\KositValidator\Commands;

use Illuminate\Console\Command;
use Moox\KositValidator\Actions\RecordKositValidation;
use Moox\KositValidator\Services\KositService;

class ValidateCommand extends Command
{
    protected $signature = 'kosit:validate
        {path : Absolute path to the XML file to validate}';

    protected $description = 'Validate a ZUGFeRD/XRechnung XML file using KoSIT Validator';

    public function handle(KositService $kosit, RecordKositValidation $recordKositValidation): int
    {
        if (! $kosit->isInstalled()) {
            $this->components->error('KoSIT is not installed. Run php artisan kosit:install first.');

            return self::FAILURE;
        }

        $path = $this->argument('path');

        if (! file_exists($path)) {
            $this->components->error("File not found: {$path}");

            return self::FAILURE;
        }

        $this->components->info("Validating {$path} ...");

        $result = $kosit->validate($path);

        if ($result->passed()) {
            $this->components->info('Validation passed.');
        } else {
            $this->components->error('Validation failed.');
            $errors = $result->errors();

            if ($errors !== []) {
                $this->newLine();
                $this->components->warn('Errors:');
                foreach ($errors as $i => $error) {
                    $this->line('  '.($i + 1).'. '.$error);
                }
            }
        }

        if ($result->reportXmlPath) {
            $this->line("  Report XML:  <info>{$result->reportXmlPath}</info>");
        }
        if ($result->reportHtmlPath) {
            $this->line("  Report HTML: <info>{$result->reportHtmlPath}</info>");
        }

        $validation = $recordKositValidation($result);
        $this->line("  Validation ID: <info>{$validation->id}</info>");

        return $result->passed() ? self::SUCCESS : self::FAILURE;
    }
}
