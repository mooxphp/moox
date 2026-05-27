<?php

declare(strict_types=1);

namespace Moox\KositValidator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Moox\KositValidator\Services\KositService;
use RuntimeException;
use ZipArchive;

class InstallKositCommand extends Command
{
    protected $signature = 'kosit:install
        {--force : Overwrite existing installation}';

    protected $description = 'Download and install KoSIT Validator + XRechnung configuration';

    public function handle(KositService $kosit): int
    {
        // 1. Check Java
        $this->components->info('Checking Java ...');

        if (! $kosit->javaAvailable()) {
            $this->components->error(
                'Java not found. Install a JRE/JDK on the server first (e.g. sudo apt install default-jre-headless).'
            );

            return self::FAILURE;
        }

        $this->components->info('Java found.');

        // 2. Prepare directories
        $basePath = config('kosit-validator.base_path');
        $validatorDir = $basePath.'/'.config('kosit-validator.paths.validator_dir');
        $xrechnungDir = $basePath.'/'.config('kosit-validator.paths.xrechnung_dir');

        if ($this->option('force') && File::exists($basePath)) {
            $this->components->warn("Deleting existing installation at {$basePath}");
            File::deleteDirectory($basePath);
        }

        if ($kosit->isInstalled() && ! $this->option('force')) {
            $this->components->info('KoSIT is already installed. Use --force to reinstall.');

            return self::SUCCESS;
        }

        File::ensureDirectoryExists($validatorDir);
        File::ensureDirectoryExists($xrechnungDir);

        $tmpDir = $basePath.'/tmp';
        File::ensureDirectoryExists($tmpDir);

        try {
            // 3. Download
            $xrechnungZip = $tmpDir.'/xrechnung.zip';

            $validatorUrl = config('kosit-validator.validator.download_url');

            if (str_ends_with($validatorUrl, '.jar')) {
                $jarTarget = $validatorDir.'/'.basename($validatorUrl);
                $this->downloadFile(
                    $validatorUrl,
                    $jarTarget,
                    'Validator v'.config('kosit-validator.validator.version')
                );
            } else {
                $validatorZip = $tmpDir.'/validator.zip';
                $this->downloadFile(
                    $validatorUrl,
                    $validatorZip,
                    'Validator v'.config('kosit-validator.validator.version')
                );
                $this->extractZip($validatorZip, $validatorDir, 'Validator');
            }

            $this->downloadFile(
                config('kosit-validator.xrechnung.download_url'),
                $xrechnungZip,
                'XRechnung Configuration v'.config('kosit-validator.xrechnung.version')
            );

            // 4. Extract
            $this->extractZip($xrechnungZip, $xrechnungDir, 'XRechnung Configuration');
        } catch (RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        // 5. Cleanup tmp
        File::deleteDirectory($tmpDir);

        // 6. Verify
        try {
            $jarPath = $kosit->jarPath();
            $scenariosPath = $kosit->scenariosPath();
        } catch (RuntimeException $e) {
            $this->components->error('Installation incomplete: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('KoSIT installation successful.');
        $this->line("  JAR:          <info>{$jarPath}</info>");
        $this->line("  scenarios.xml: <info>{$scenariosPath}</info>");
        $this->newLine();
        $this->line('Test with: <comment>php artisan kosit:validate /path/to/invoice.xml</comment>');

        return self::SUCCESS;
    }

    private function downloadFile(string $url, string $target, string $label): void
    {
        $this->components->info("Downloading {$label} ...");

        $response = Http::timeout(600)
            ->sink($target)
            ->withOptions([
                'allow_redirects' => true,
            ])
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException("Download failed for {$label}: HTTP {$response->status()} from {$url}");
        }

        if (! File::exists($target) || File::size($target) === 0) {
            throw new RuntimeException("Download incomplete for {$label}");
        }
    }

    private function extractZip(string $zipFile, string $targetDir, string $label): void
    {
        $this->components->info("Extracting {$label} ...");

        $zip = new ZipArchive;
        if ($zip->open($zipFile) !== true) {
            throw new RuntimeException("Cannot open ZIP: {$zipFile}");
        }

        if (! $zip->extractTo($targetDir)) {
            $zip->close();
            throw new RuntimeException("Cannot extract ZIP: {$zipFile}");
        }

        $zip->close();
    }
}
