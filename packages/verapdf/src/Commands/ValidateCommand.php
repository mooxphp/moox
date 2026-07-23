<?php

declare(strict_types=1);

namespace Moox\VeraPdf\Commands;

use Illuminate\Console\Command;
use Moox\VeraPdf\Actions\RecordVeraPdfValidation;
use Moox\VeraPdf\Commands\Concerns\InteractsWithVeraPdfEnvironment;
use Moox\VeraPdf\DTOs\VeraPdfResult;
use Moox\VeraPdf\Services\VeraPdfService;
use RuntimeException;

class ValidateCommand extends Command
{
    use InteractsWithVeraPdfEnvironment;

    protected $signature = 'verapdf:validate
        {path : Absolute path to the PDF file to validate}';

    protected $description = 'Validate a PDF for PDF/A-3 conformance using veraPDF';

    public function handle(VeraPdfService $veraPdf, RecordVeraPdfValidation $recordVeraPdfValidation): int
    {
        if ($this->requireJavaAvailable($veraPdf) !== null) {
            return self::FAILURE;
        }

        if ($this->requireVeraPdfInstalled($veraPdf) !== null) {
            return self::FAILURE;
        }

        $path = $this->argument('path');

        if (! file_exists($path)) {
            $this->components->error("File not found: {$path}");

            return self::FAILURE;
        }

        $this->components->info("Validating {$path} ...");

        try {
            $result = $veraPdf->validate($path);
        } catch (RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->reportValidationResult($result);

        $validation = $recordVeraPdfValidation($result);
        $this->line("  Validation ID: <info>{$validation->id}</info>");

        return $result->passed() ? self::SUCCESS : self::FAILURE;
    }

    private function reportValidationResult(VeraPdfResult $result): void
    {
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
    }
}
