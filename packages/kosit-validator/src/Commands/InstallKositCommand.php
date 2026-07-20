<?php

declare(strict_types=1);

namespace Moox\KositValidator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Moox\KositValidator\Services\KositService;
use Moox\KositValidator\Support\InstallerBasePathGuard;
use Moox\KositValidator\Support\InstallerChecksum;
use Moox\KositValidator\Support\InstallerDownloadUrlGuard;
use Moox\KositValidator\Support\KositValidatorArtifact;
use Moox\KositValidator\Support\SafeZipExtractor;
use RuntimeException;

class InstallKositCommand extends Command
{
    protected $signature = 'kosit:install
        {--force : Overwrite existing installation}';

    protected $description = 'Download and install KoSIT Validator + XRechnung configuration';

    public function handle(KositService $kosit): int
    {
        $this->components->info('Checking Java ...');

        if (! $kosit->javaAvailable()) {
            $this->components->error(
                'Java not found. Install a JRE/JDK on the server first (e.g. sudo apt install default-jre-headless).'
            );

            return self::FAILURE;
        }

        $this->components->info('Java found.');

        $basePath = (string) config('kosit-validator.base_path');
        $validatorDir = $basePath.'/'.config('kosit-validator.paths.validator_dir');
        $xrechnungDir = $basePath.'/'.config('kosit-validator.paths.xrechnung_dir');

        try {
            InstallerBasePathGuard::assertSafe($basePath);
        } catch (RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        if ($kosit->isInstalled() && ! $this->option('force')) {
            $this->components->info('KoSIT is already installed. Use --force to reinstall.');

            return self::SUCCESS;
        }

        $stagingDir = sys_get_temp_dir().'/kosit-install-'.uniqid('', true);
        File::ensureDirectoryExists($stagingDir);

        try {
            $expectedJarName = KositValidatorArtifact::expectedJarFilename();
            $stagingJar = $stagingDir.'/'.$expectedJarName;
            $stagingXrechnungDir = $stagingDir.'/xrechnung';

            $this->stageValidator($stagingDir, $stagingJar, $expectedJarName);
            $this->stageXrechnung($stagingDir, $stagingXrechnungDir);

            /** @var array<string, string> $backups */
            $backups = [];

            if ($this->option('force')) {
                foreach ([$validatorDir, $xrechnungDir] as $dir) {
                    if (File::isDirectory($dir)) {
                        $backupPath = $dir.'.bak-'.uniqid('', true);
                        $this->components->warn('Backing up '.basename($dir).' to '.basename($backupPath));
                        File::moveDirectory($dir, $backupPath);
                        $backups[$dir] = $backupPath;
                    }
                }
            }

            try {
                File::ensureDirectoryExists($validatorDir);
                File::ensureDirectoryExists($xrechnungDir);

                if (! File::move($stagingJar, $validatorDir.'/'.$expectedJarName)) {
                    throw new RuntimeException('Failed to install verified validator JAR.');
                }

                File::copyDirectory($stagingXrechnungDir, $xrechnungDir);
            } catch (\Throwable $e) {
                foreach ($backups as $dir => $backupPath) {
                    if (File::isDirectory($dir)) {
                        File::deleteDirectory($dir);
                    }

                    if (File::isDirectory($backupPath)) {
                        File::moveDirectory($backupPath, $dir);
                    }
                }

                throw $e instanceof RuntimeException
                    ? $e
                    : new RuntimeException($e->getMessage(), 0, $e);
            }

            foreach ($backups as $backupPath) {
                if (File::isDirectory($backupPath)) {
                    File::deleteDirectory($backupPath);
                }
            }
        } catch (RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        } finally {
            File::deleteDirectory($stagingDir);
        }

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

    private function stageValidator(string $stagingDir, string $stagingJar, string $expectedJarName): void
    {
        $validatorUrl = (string) config('kosit-validator.validator.download_url');
        $validatorLabel = 'Validator v'.config('kosit-validator.validator.version');

        if (str_ends_with($validatorUrl, '.jar')) {
            $this->downloadFile($validatorUrl, $stagingJar, $validatorLabel);
            InstallerChecksum::assertMatches($stagingJar, (string) config('kosit-validator.validator.sha256'));

            return;
        }

        $validatorZip = $stagingDir.'/validator.zip';
        $this->downloadFile($validatorUrl, $validatorZip, $validatorLabel);
        InstallerChecksum::assertMatches($validatorZip, (string) config('kosit-validator.validator.sha256'));

        $unpackDir = $stagingDir.'/validator-unpack';
        SafeZipExtractor::extract($validatorZip, $unpackDir);

        $resolvedJar = $this->resolveStagedJar($unpackDir, $expectedJarName);
        File::move($resolvedJar, $stagingJar);
    }

    private function stageXrechnung(string $stagingDir, string $stagingXrechnungDir): void
    {
        $xrechnungZip = $stagingDir.'/xrechnung.zip';
        $xrechnungLabel = 'XRechnung Configuration v'.config('kosit-validator.xrechnung.version');

        $this->downloadFile(
            (string) config('kosit-validator.xrechnung.download_url'),
            $xrechnungZip,
            $xrechnungLabel,
        );

        InstallerChecksum::assertMatches($xrechnungZip, (string) config('kosit-validator.xrechnung.sha256'));
        SafeZipExtractor::extract($xrechnungZip, $stagingXrechnungDir);
    }

    private function resolveStagedJar(string $unpackDir, string $expectedJarName): string
    {
        $direct = $unpackDir.'/'.$expectedJarName;
        if (is_file($direct)) {
            return $direct;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($unpackDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file instanceof \SplFileInfo || ! $file->isFile()) {
                continue;
            }

            if ($file->getFilename() === $expectedJarName) {
                return $file->getPathname();
            }
        }

        throw new RuntimeException("Expected validator JAR {$expectedJarName} not found in downloaded archive.");
    }

    private function downloadFile(string $url, string $target, string $label): void
    {
        InstallerDownloadUrlGuard::assertAllowed($url, $label);

        $this->components->info("Downloading {$label} ...");

        try {
            $response = Http::timeout(600)
                ->sink($target)
                ->withOptions([
                    'allow_redirects' => [
                        'protocols' => ['https'],
                    ],
                ])
                ->get($url);

            if (! $response->successful()) {
                throw new RuntimeException("Download failed for {$label}: HTTP {$response->status()} from {$url}");
            }
        } catch (\Throwable $e) {
            if ($e instanceof RuntimeException) {
                throw $e;
            }

            throw new RuntimeException("Download failed for {$label}: {$e->getMessage()}", 0, $e);
        }

        if (! File::exists($target) || File::size($target) === 0) {
            throw new RuntimeException("Download incomplete for {$label}");
        }
    }
}
