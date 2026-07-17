<?php

declare(strict_types=1);

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Moox\VeraPdf\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config()->set('verapdf.base_path', sys_get_temp_dir().'/verapdf-install-'.uniqid());
    config()->set('verapdf.paths.launcher', 'verapdf');
    config()->set('verapdf.java_binary', 'java');
    config()->set('verapdf.installer.version', '1.30.1');
    config()->set(
        'verapdf.installer.download_url',
        'https://software.verapdf.org/releases/1.30/verapdf-greenfield-1.30.1-installer.zip'
    );
});

afterEach(function (): void {
    $base = config('verapdf.base_path');
    if (is_string($base) && is_dir($base)) {
        File::deleteDirectory($base);
    }
});

test('install succeeds early when CLI layout is already present', function (): void {
    $base = (string) config('verapdf.base_path');
    File::ensureDirectoryExists($base.'/bin');
    file_put_contents($base.'/verapdf', "#!/bin/sh\nexit 0\n");
    chmod($base.'/verapdf', 0755);
    file_put_contents($base.'/bin/cli-1.30.1.jar', 'fake');

    Process::fake(fn () => Process::result(
        output: '',
        errorOutput: 'openjdk version "17"',
        exitCode: 0,
    ));

    $this->artisan('verapdf:install')
        ->expectsOutputToContain('already installed')
        ->assertSuccessful();
});

test('install fails when launcher exists without CLI pack', function (): void {
    $base = (string) config('verapdf.base_path');
    File::ensureDirectoryExists($base);
    file_put_contents($base.'/verapdf', "#!/bin/sh\nexit 0\n");
    chmod($base.'/verapdf', 0755);

    Process::fake(fn () => Process::result(
        output: '',
        errorOutput: 'openjdk version "17"',
        exitCode: 0,
    ));

    $this->artisan('verapdf:install')
        ->expectsOutputToContain('CLI pack is missing')
        ->assertFailed();
});

test('install aborts on checksum mismatch without wiping an existing install', function (): void {
    $base = (string) config('verapdf.base_path');
    File::ensureDirectoryExists($base.'/bin');
    file_put_contents($base.'/verapdf', "#!/bin/sh\nexit 0\n");
    chmod($base.'/verapdf', 0755);
    file_put_contents($base.'/bin/cli-1.30.1.jar', 'existing-cli');

    $payload = 'tampered-installer-bytes';
    config()->set('verapdf.installer.sha256', hash('sha256', 'different-expected-bytes'));

    Http::fake([
        '*' => Http::response($payload, 200),
    ]);

    $installerJarRan = false;
    Process::fake(function (PendingProcess $process) use (&$installerJarRan) {
        $command = $process->command;
        if (is_array($command) && in_array('-jar', $command, true)) {
            $installerJarRan = true;
        }

        return Process::result(
            output: '',
            errorOutput: 'openjdk version "17"',
            exitCode: 0,
        );
    });

    $this->artisan('verapdf:install', ['--force' => true])
        ->expectsOutputToContain('checksum mismatch')
        ->assertFailed();

    expect(is_file($base.'/verapdf'))->toBeTrue()
        ->and(is_file($base.'/bin/cli-1.30.1.jar'))->toBeTrue()
        ->and((string) file_get_contents($base.'/bin/cli-1.30.1.jar'))->toBe('existing-cli')
        ->and($installerJarRan)->toBeFalse();
});

test('install aborts on zip-slip archive before running the installer', function (): void {
    $zipPath = sys_get_temp_dir().'/verapdf-cmd-slip-'.uniqid('', true).'.zip';
    $zip = new ZipArchive;
    expect($zip->open($zipPath, ZipArchive::CREATE))->toBeTrue();
    $zip->addFromString('../evil.txt', 'pwned');
    $zip->close();

    $bytes = (string) file_get_contents($zipPath);
    config()->set('verapdf.installer.sha256', hash('sha256', $bytes));

    Http::fake([
        '*' => Http::response($bytes, 200),
    ]);

    $installerJarRan = false;
    Process::fake(function (PendingProcess $process) use (&$installerJarRan) {
        $command = $process->command;
        if (is_array($command) && in_array('-jar', $command, true)) {
            $installerJarRan = true;
        }

        return Process::result(
            output: '',
            errorOutput: 'openjdk version "17"',
            exitCode: 0,
        );
    });

    $this->artisan('verapdf:install')
        ->expectsOutputToContain('unsafe ZIP entry')
        ->assertFailed();

    $base = (string) config('verapdf.base_path');
    expect(is_file($base.'/verapdf'))->toBeFalse()
        ->and($installerJarRan)->toBeFalse();

    File::delete($zipPath);
});

test('install aborts on zip-slip with --force without wiping an existing install', function (): void {
    $base = (string) config('verapdf.base_path');
    File::ensureDirectoryExists($base.'/bin');
    file_put_contents($base.'/verapdf', "#!/bin/sh\nexit 0\n");
    chmod($base.'/verapdf', 0755);
    file_put_contents($base.'/bin/cli-1.30.1.jar', 'existing-cli');

    $zipPath = sys_get_temp_dir().'/verapdf-cmd-force-slip-'.uniqid('', true).'.zip';
    $zip = new ZipArchive;
    expect($zip->open($zipPath, ZipArchive::CREATE))->toBeTrue();
    $zip->addFromString('../evil.txt', 'pwned');
    $zip->close();

    $bytes = (string) file_get_contents($zipPath);
    config()->set('verapdf.installer.sha256', hash('sha256', $bytes));

    Http::fake([
        '*' => Http::response($bytes, 200),
    ]);

    $installerJarRan = false;
    Process::fake(function (PendingProcess $process) use (&$installerJarRan) {
        $command = $process->command;
        if (is_array($command) && in_array('-jar', $command, true)) {
            $installerJarRan = true;
        }

        return Process::result(
            output: '',
            errorOutput: 'openjdk version "17"',
            exitCode: 0,
        );
    });

    $this->artisan('verapdf:install', ['--force' => true])
        ->expectsOutputToContain('unsafe ZIP entry')
        ->assertFailed();

    expect(is_file($base.'/verapdf'))->toBeTrue()
        ->and(is_file($base.'/bin/cli-1.30.1.jar'))->toBeTrue()
        ->and((string) file_get_contents($base.'/bin/cli-1.30.1.jar'))->toBe('existing-cli')
        ->and($installerJarRan)->toBeFalse();

    File::delete($zipPath);
});
