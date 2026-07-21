<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Moox\VeraPdf\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    configureInstallTestDefaults();
});

afterEach(function (): void {
    cleanupConfiguredPaths('verapdf.base_path');
});

test('install succeeds early when CLI layout is already present', function (): void {
    seedCliInstallLayout();

    fakeJavaProcess();

    $this->artisan('verapdf:install')
        ->expectsOutputToContain('already installed')
        ->assertSuccessful();
});

test('install fails when launcher exists without CLI pack', function (): void {
    $base = (string) config('verapdf.base_path');
    File::ensureDirectoryExists($base);
    file_put_contents($base.'/verapdf', "#!/bin/sh\nexit 0\n");
    chmod($base.'/verapdf', 0755);

    fakeJavaProcess();

    $this->artisan('verapdf:install')
        ->expectsOutputToContain('CLI pack is missing')
        ->assertFailed();
});

test('install aborts on checksum mismatch without wiping an existing install', function (): void {
    $base = seedCliInstallLayout();
    file_put_contents($base.'/bin/cli-1.30.1.jar', 'existing-cli');

    $payload = 'tampered-installer-bytes';
    fakeInstallerDownload($payload, hash('sha256', 'different-expected-bytes'));

    $jarTracker = fakeJavaProcessTrackingJar();

    $this->artisan('verapdf:install', ['--force' => true])
        ->expectsOutputToContain('checksum mismatch')
        ->assertFailed();

    expect(is_file($base.'/verapdf'))->toBeTrue()
        ->and(is_file($base.'/bin/cli-1.30.1.jar'))->toBeTrue()
        ->and((string) file_get_contents($base.'/bin/cli-1.30.1.jar'))->toBe('existing-cli')
        ->and($jarTracker['installerJarRan'])->toBeFalse();
});

test('install aborts on zip-slip archive', function (bool $force, bool $seedExisting): void {
    $base = $seedExisting ? seedCliInstallLayout() : (string) config('verapdf.base_path');

    if ($seedExisting) {
        file_put_contents($base.'/bin/cli-1.30.1.jar', 'existing-cli');
    }

    $installer = buildInstallerZipWithChecksum('cmd-slip', [
        ['name' => '../evil.txt', 'content' => 'pwned'],
    ]);

    fakeInstallerDownload($installer['bytes'], $installer['sha256']);

    $jarTracker = fakeJavaProcessTrackingJar();

    $command = $this->artisan('verapdf:install', $force ? ['--force' => true] : []);
    $command->expectsOutputToContain('unsafe ZIP entry')->assertFailed();

    if ($seedExisting) {
        expect(is_file($base.'/verapdf'))->toBeTrue()
            ->and(is_file($base.'/bin/cli-1.30.1.jar'))->toBeTrue()
            ->and((string) file_get_contents($base.'/bin/cli-1.30.1.jar'))->toBe('existing-cli');
    } else {
        expect(is_file($base.'/verapdf'))->toBeFalse();
    }

    expect($jarTracker['installerJarRan'])->toBeFalse();

    File::delete($installer['path']);
})->with([
    'before running the installer' => [false, false],
    'with --force without wiping an existing install' => [true, true],
]);
