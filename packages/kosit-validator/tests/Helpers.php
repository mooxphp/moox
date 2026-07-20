<?php

declare(strict_types=1);

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

function kositTempPath(string $prefix): string
{
    return sys_get_temp_dir().'/kosit-'.$prefix.'-'.uniqid('', true);
}

function kositTempDir(string $prefix): string
{
    $dir = kositTempPath($prefix);
    File::ensureDirectoryExists($dir);

    return $dir;
}

/**
 * @param  list<array{name: string, content: string, symlink?: bool}>  $entries
 */
function buildKositZipArchive(string $zipPath, array $entries): void
{
    $zip = new ZipArchive;

    if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
        throw new RuntimeException('Cannot create ZIP archive: '.$zipPath);
    }

    foreach ($entries as $entry) {
        $zip->addFromString($entry['name'], $entry['content']);

        if ($entry['symlink'] ?? false) {
            $zip->setExternalAttributesName(
                $entry['name'],
                ZipArchive::OPSYS_UNIX,
                (0o120000 | 0o777) << 16,
            );
        }
    }

    $zip->close();
}

/**
 * @param  list<array{name: string, content: string, symlink?: bool}>  $entries
 */
function buildKositZipAt(string $prefix, array $entries): string
{
    $zipPath = kositTempPath($prefix).'.zip';
    buildKositZipArchive($zipPath, $entries);

    return $zipPath;
}

/**
 * @return array{path: string, bytes: string, sha256: string}
 */
function buildKositZipWithChecksum(string $prefix, array $entries): array
{
    $zipPath = buildKositZipAt($prefix, $entries);
    $bytes = (string) file_get_contents($zipPath);

    return [
        'path' => $zipPath,
        'bytes' => $bytes,
        'sha256' => hash('sha256', $bytes),
    ];
}

function configureKositInstallTestDefaults(): void
{
    config()->set('kosit-validator.installer.storage_root', storage_path('app/private'));
    config()->set(
        'kosit-validator.base_path',
        storage_path('app/private/kosit-install-'.uniqid()),
    );
    config()->set('kosit-validator.java_binary', 'java');
    config()->set('kosit-validator.validator.version', '1.6.2');
    config()->set(
        'kosit-validator.validator.download_url',
        'https://github.com/itplr-kosit/validator/releases/download/v1.6.2/validator-1.6.2-standalone.jar',
    );
    config()->set(
        'kosit-validator.xrechnung.download_url',
        'https://github.com/itplr-kosit/validator-configuration-xrechnung/releases/download/v2026-01-31/xrechnung-3.0.2-validator-configuration-2026-01-31.zip',
    );
    config()->set('kosit-validator.installer.allow_untrusted_base_path', false);
    config()->set('kosit-validator.installer.allow_untrusted_download_hosts', false);
}

function cleanupKositConfiguredPaths(string ...$configKeys): void
{
    foreach ($configKeys as $key) {
        $path = config($key);
        if (is_string($path) && is_dir($path)) {
            File::deleteDirectory($path);
        }
    }
}

function seedKositInstallLayout(?string $base = null): string
{
    $base ??= (string) config('kosit-validator.base_path');
    $validatorDir = $base.'/'.config('kosit-validator.paths.validator_dir');
    $xrechnungDir = $base.'/'.config('kosit-validator.paths.xrechnung_dir');

    File::ensureDirectoryExists($validatorDir);
    File::ensureDirectoryExists($xrechnungDir);
    file_put_contents($validatorDir.'/validator-1.6.2-standalone.jar', 'existing-jar');
    file_put_contents($xrechnungDir.'/scenarios.xml', '<scenarios/>');

    return $base;
}

function fakeKositJavaProcess(): void
{
    Process::fake(fn () => Process::result(
        output: '',
        errorOutput: 'openjdk version "17"',
        exitCode: 0,
    ));
}

/**
 * @return array{jarRan: bool}
 */
function fakeKositJavaProcessTrackingJar(): array
{
    $state = ['jarRan' => false];

    Process::fake(function (PendingProcess $process) use (&$state) {
        $command = $process->command;
        if (is_array($command) && in_array('-jar', $command, true)) {
            $state['jarRan'] = true;
        }

        return Process::result(
            output: '',
            errorOutput: 'openjdk version "17"',
            exitCode: 0,
        );
    });

    return $state;
}

function fakeKositDownloads(string $jarBytes, string $jarSha256, string $zipBytes, string $zipSha256): void
{
    config()->set('kosit-validator.validator.sha256', $jarSha256);
    config()->set('kosit-validator.xrechnung.sha256', $zipSha256);

    Http::fake([
        config('kosit-validator.validator.download_url') => Http::response($jarBytes, 200),
        config('kosit-validator.xrechnung.download_url') => Http::response($zipBytes, 200),
    ]);
}

function buildBenignXrechnungZip(): array
{
    return buildKositZipWithChecksum('xrechnung', [
        ['name' => 'scenarios.xml', 'content' => '<scenarios/>'],
    ]);
}
