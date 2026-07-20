<?php

declare(strict_types=1);

namespace Moox\KositValidator\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Moox\KositValidator\Support\InstallerChecksum;
use Moox\KositValidator\Support\InstallerDownloadUrlGuard;
use Moox\KositValidator\Support\KositInstallPaths;
use Moox\KositValidator\Support\KositValidatorArtifact;
use Moox\KositValidator\Support\RecursiveFileFinder;
use Moox\KositValidator\Support\SafeZipExtractor;
use RuntimeException;

/**
 * Downloads, verifies, stages, and promotes KoSIT validator install artifacts.
 */
final class KositInstaller
{
    /**
     * @param  callable(string): void|null  $onDownloadStart  Receives the human-readable artifact label.
     * @param  callable(string, string): void|null  $onDirectoryBackup  Receives directory basename and backup basename.
     *
     * @throws RuntimeException
     */
    public function install(
        KositInstallPaths $paths,
        bool $force,
        ?callable $onDownloadStart = null,
        ?callable $onDirectoryBackup = null,
    ): void {
        $stagingDir = sys_get_temp_dir().'/kosit-install-'.uniqid('', true);
        File::ensureDirectoryExists($stagingDir);

        try {
            $expectedJarName = KositValidatorArtifact::expectedJarFilename();
            $stagingJar = $stagingDir.'/'.$expectedJarName;
            $stagingXrechnungDir = $stagingDir.'/xrechnung';

            $this->stageValidator($stagingDir, $stagingJar, $expectedJarName, $onDownloadStart);
            $this->stageXrechnung($stagingDir, $stagingXrechnungDir, $onDownloadStart);

            /** @var array<string, string> $backups */
            $backups = [];

            if ($force) {
                foreach ($paths->directories() as $dir) {
                    if (File::isDirectory($dir)) {
                        $backupPath = $dir.'.bak-'.uniqid('', true);
                        if ($onDirectoryBackup !== null) {
                            $onDirectoryBackup(basename($dir), basename($backupPath));
                        }
                        File::moveDirectory($dir, $backupPath);
                        $backups[$dir] = $backupPath;
                    }
                }
            }

            try {
                File::ensureDirectoryExists($paths->validatorDir);
                File::ensureDirectoryExists($paths->xrechnungDir);

                if (! File::move($stagingJar, $paths->validatorDir.'/'.$expectedJarName)) {
                    throw new RuntimeException('Failed to install verified validator JAR.');
                }

                File::copyDirectory($stagingXrechnungDir, $paths->xrechnungDir);
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
        } finally {
            File::deleteDirectory($stagingDir);
        }
    }

    /**
     * @param  callable(string): void|null  $onDownloadStart
     */
    private function stageValidator(
        string $stagingDir,
        string $stagingJar,
        string $expectedJarName,
        ?callable $onDownloadStart,
    ): void {
        $validatorUrl = (string) config('kosit-validator.validator.download_url');
        $validatorLabel = 'Validator v'.config('kosit-validator.validator.version');

        if (str_ends_with($validatorUrl, '.jar')) {
            $this->downloadFile($validatorUrl, $stagingJar, $validatorLabel, $onDownloadStart);
            InstallerChecksum::assertValid($stagingJar, (string) config('kosit-validator.validator.sha256'));

            return;
        }

        $validatorZip = $stagingDir.'/validator.zip';
        $this->downloadFile($validatorUrl, $validatorZip, $validatorLabel, $onDownloadStart);
        InstallerChecksum::assertValid($validatorZip, (string) config('kosit-validator.validator.sha256'));

        $unpackDir = $stagingDir.'/validator-unpack';
        SafeZipExtractor::extract($validatorZip, $unpackDir);

        $resolvedJar = $this->resolveStagedJar($unpackDir, $expectedJarName);
        File::move($resolvedJar, $stagingJar);
    }

    /**
     * @param  callable(string): void|null  $onDownloadStart
     */
    private function stageXrechnung(
        string $stagingDir,
        string $stagingXrechnungDir,
        ?callable $onDownloadStart,
    ): void {
        $xrechnungZip = $stagingDir.'/xrechnung.zip';
        $xrechnungLabel = 'XRechnung Configuration v'.config('kosit-validator.xrechnung.version');

        $this->downloadFile(
            (string) config('kosit-validator.xrechnung.download_url'),
            $xrechnungZip,
            $xrechnungLabel,
            $onDownloadStart,
        );

        InstallerChecksum::assertValid($xrechnungZip, (string) config('kosit-validator.xrechnung.sha256'));
        SafeZipExtractor::extract($xrechnungZip, $stagingXrechnungDir);
    }

    private function resolveStagedJar(string $unpackDir, string $expectedJarName): string
    {
        $direct = $unpackDir.'/'.$expectedJarName;
        if (is_file($direct)) {
            return $direct;
        }

        $resolved = RecursiveFileFinder::find($unpackDir, $expectedJarName);

        if ($resolved !== null) {
            return $resolved;
        }

        throw new RuntimeException("Expected validator JAR {$expectedJarName} not found in downloaded archive.");
    }

    /**
     * @param  callable(string): void|null  $onDownloadStart
     */
    private function downloadFile(
        string $url,
        string $target,
        string $label,
        ?callable $onDownloadStart,
    ): void {
        InstallerDownloadUrlGuard::assertValid($url, $label);

        if ($onDownloadStart !== null) {
            $onDownloadStart("Downloading {$label} ...");
        }

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
