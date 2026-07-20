<?php

declare(strict_types=1);

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Moox\VeraPdf\DTOs\VeraPdfResult;

function verapdfTempPath(string $prefix): string
{
    return sys_get_temp_dir().'/verapdf-'.$prefix.'-'.uniqid('', true);
}

function verapdfTempDir(string $prefix): string
{
    $dir = verapdfTempPath($prefix);
    File::ensureDirectoryExists($dir);

    return $dir;
}

/**
 * @param  list<array{name: string, content: string, symlink?: bool}>  $entries
 */
function buildZipArchive(string $zipPath, array $entries): void
{
    $zip = new ZipArchive;
    expect($zip->open($zipPath, ZipArchive::CREATE))->toBeTrue();

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
function buildZipAt(string $prefix, array $entries): string
{
    $zipPath = verapdfTempPath($prefix).'.zip';
    buildZipArchive($zipPath, $entries);

    return $zipPath;
}

/**
 * @return array{path: string, bytes: string, sha256: string}
 */
function buildInstallerZipWithChecksum(string $prefix, array $entries): array
{
    $zipPath = buildZipAt($prefix, $entries);
    $bytes = (string) file_get_contents($zipPath);

    return [
        'path' => $zipPath,
        'bytes' => $bytes,
        'sha256' => hash('sha256', $bytes),
    ];
}

function tempFileWithContent(string $prefix, string $content, string $extension = '.bin'): string
{
    $path = verapdfTempPath($prefix).$extension;
    file_put_contents($path, $content);

    return $path;
}

function configureInstallTestDefaults(): void
{
    config()->set('verapdf.base_path', sys_get_temp_dir().'/verapdf-install-'.uniqid());
    config()->set('verapdf.paths.launcher', 'verapdf');
    config()->set('verapdf.java_binary', 'java');
    config()->set('verapdf.installer.version', '1.30.1');
    config()->set(
        'verapdf.installer.download_url',
        'https://software.verapdf.org/releases/1.30/verapdf-greenfield-1.30.1-installer.zip',
    );
}

function configureDoctorTestDefaults(): void
{
    config()->set('verapdf.base_path', sys_get_temp_dir().'/verapdf-doctor-'.uniqid());
    config()->set('verapdf.paths.launcher', 'verapdf');
    config()->set('verapdf.java_binary', 'java');
    config()->set('verapdf.output.path', sys_get_temp_dir().'/verapdf-reports-'.uniqid());
}

function cleanupConfiguredPaths(string ...$configKeys): void
{
    foreach ($configKeys as $key) {
        $path = config($key);
        if (is_string($path) && is_dir($path)) {
            File::deleteDirectory($path);
        }
    }
}

function seedCliInstallLayout(?string $base = null): string
{
    $base ??= (string) config('verapdf.base_path');
    File::ensureDirectoryExists($base.'/bin');
    file_put_contents($base.'/verapdf', "#!/bin/sh\nexit 0\n");
    chmod($base.'/verapdf', 0755);
    file_put_contents($base.'/bin/cli-1.30.1.jar', 'fake');

    return $base;
}

function fakeJavaProcess(): void
{
    Process::fake(fn () => Process::result(
        output: '',
        errorOutput: 'openjdk version "17"',
        exitCode: 0,
    ));
}

/**
 * @return array{installerJarRan: bool}
 */
function fakeJavaProcessTrackingJar(): array
{
    $state = ['installerJarRan' => false];

    Process::fake(function (PendingProcess $process) use ($state) {
        $command = $process->command;
        if (is_array($command) && in_array('-jar', $command, true)) {
            $state['installerJarRan'] = true;
        }

        return Process::result(
            output: '',
            errorOutput: 'openjdk version "17"',
            exitCode: 0,
        );
    });

    return $state;
}

function fakeInstallerDownload(string $bytes, string $sha256): void
{
    config()->set('verapdf.installer.sha256', $sha256);

    Http::fake([
        '*' => Http::response($bytes, 200),
    ]);
}

function makeVeraPdfResult(
    int $exitCode = 0,
    ?string $reportXmlPath = null,
    ?string $reportHtmlPath = null,
    ?string $pdfPath = '/tmp/file.pdf',
): VeraPdfResult {
    return new VeraPdfResult(
        exitCode: $exitCode,
        stdout: '',
        stderr: '',
        reportXmlPath: $reportXmlPath,
        reportHtmlPath: $reportHtmlPath,
        pdfPath: $pdfPath,
    );
}
