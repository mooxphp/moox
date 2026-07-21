<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Moox\KositValidator\Support\KositInstallPaths;
use Moox\KositValidator\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    configureKositInstallTestDefaults();
});

afterEach(function (): void {
    cleanupKositConfiguredPaths('kosit-validator.base_path');
});

test('install succeeds early when already installed', function (): void {
    seedKositInstallLayout();
    fakeKositJavaProcess();

    $this->artisan('kosit:install')
        ->expectsOutputToContain('already installed')
        ->assertSuccessful();
});

test('install aborts on validator checksum mismatch without wiping an existing install', function (): void {
    $base = seedKositInstallLayout();
    $paths = KositInstallPaths::fromBasePath($base);
    file_put_contents($paths->validatorDir.'/validator-1.6.2-standalone.jar', 'existing-jar');

    $xrechnung = buildBenignXrechnungZip();
    fakeKositDownloads('tampered-jar', hash('sha256', 'different-jar-bytes'), $xrechnung['bytes'], $xrechnung['sha256']);

    fakeKositJavaProcess();

    $this->artisan('kosit:install', ['--force' => true])
        ->expectsOutputToContain('checksum mismatch')
        ->assertFailed();

    expect(is_file($paths->validatorDir.'/validator-1.6.2-standalone.jar'))->toBeTrue()
        ->and((string) file_get_contents($paths->validatorDir.'/validator-1.6.2-standalone.jar'))->toBe('existing-jar');

    File::delete($xrechnung['path']);
});

test('install aborts on xrechnung checksum mismatch without wiping an existing install', function (): void {
    $base = seedKositInstallLayout();
    $paths = KositInstallPaths::fromBasePath($base);

    $jarBytes = 'valid-jar-bytes';
    fakeKositDownloads($jarBytes, hash('sha256', $jarBytes), 'tampered-zip', hash('sha256', 'different-zip-bytes'));

    fakeKositJavaProcess();

    $this->artisan('kosit:install', ['--force' => true])
        ->expectsOutputToContain('checksum mismatch')
        ->assertFailed();

    expect((string) file_get_contents($paths->validatorDir.'/validator-1.6.2-standalone.jar'))->toBe('existing-jar');
});

test('install aborts on zip-slip archive', function (bool $force, bool $seedExisting): void {
    $base = $seedExisting ? seedKositInstallLayout() : (string) config('kosit-validator.base_path');
    $paths = KositInstallPaths::fromBasePath($base);

    if ($seedExisting) {
        file_put_contents($paths->validatorDir.'/validator-1.6.2-standalone.jar', 'existing-jar');
    }

    $jarBytes = 'valid-jar-bytes';
    $xrechnung = buildKositZipWithChecksum('cmd-slip', [
        ['name' => '../evil.txt', 'content' => 'pwned'],
    ]);

    fakeKositDownloads($jarBytes, hash('sha256', $jarBytes), $xrechnung['bytes'], $xrechnung['sha256']);

    $jarTracker = fakeKositJavaProcessTrackingJar();

    $command = $this->artisan('kosit:install', $force ? ['--force' => true] : []);
    $command->expectsOutputToContain('unsafe ZIP entry')->assertFailed();

    if ($seedExisting) {
        expect(is_file($paths->validatorDir.'/validator-1.6.2-standalone.jar'))->toBeTrue()
            ->and((string) file_get_contents($paths->validatorDir.'/validator-1.6.2-standalone.jar'))->toBe('existing-jar');
    } else {
        expect(is_dir($paths->validatorDir))->toBeFalse();
    }

    expect($jarTracker['jarRan'])->toBeFalse();

    File::delete($xrechnung['path']);
})->with([
    'before running the installer' => [false, false],
    'with --force without wiping an existing install' => [true, true],
]);

test('install rejects non-https download urls', function (): void {
    config()->set(
        'kosit-validator.validator.download_url',
        'http://example.test/validator-1.6.2-standalone.jar',
    );

    fakeKositJavaProcess();

    $this->artisan('kosit:install')
        ->expectsOutputToContain('must use HTTPS')
        ->assertFailed();
});

test('install succeeds with verified downloads', function (): void {
    $jarBytes = 'valid-jar-bytes';
    $xrechnung = buildBenignXrechnungZip();
    fakeKositDownloads($jarBytes, hash('sha256', $jarBytes), $xrechnung['bytes'], $xrechnung['sha256']);

    fakeKositJavaProcess();

    $this->artisan('kosit:install')
        ->expectsOutputToContain('installation successful')
        ->assertSuccessful();

    $paths = KositInstallPaths::fromConfig();

    expect(is_file($paths->validatorDir.'/validator-1.6.2-standalone.jar'))->toBeTrue()
        ->and((string) file_get_contents($paths->validatorDir.'/validator-1.6.2-standalone.jar'))->toBe($jarBytes)
        ->and(is_file($paths->xrechnungDir.'/scenarios.xml'))->toBeTrue()
        ->and(is_file($paths->xrechnungDir.'/.xrechnung-bundle.zip'))->toBeTrue()
        ->and(hash('sha256', (string) file_get_contents($paths->xrechnungDir.'/.xrechnung-bundle.zip')))->toBe($xrechnung['sha256']);

    File::delete($xrechnung['path']);
});

test('install with --force removes only validator and xrechnung subdirectories', function (): void {
    $base = seedKositInstallLayout();
    file_put_contents($base.'/unrelated.txt', 'keep-me');

    $jarBytes = 'fresh-jar-bytes';
    $xrechnung = buildBenignXrechnungZip();
    fakeKositDownloads($jarBytes, hash('sha256', $jarBytes), $xrechnung['bytes'], $xrechnung['sha256']);

    fakeKositJavaProcess();

    $this->artisan('kosit:install', ['--force' => true])
        ->expectsOutputToContain('installation successful')
        ->assertSuccessful();

    $paths = KositInstallPaths::fromBasePath($base);

    expect(is_file($base.'/unrelated.txt'))->toBeTrue()
        ->and((string) file_get_contents($base.'/unrelated.txt'))->toBe('keep-me')
        ->and((string) file_get_contents($paths->validatorDir.'/validator-1.6.2-standalone.jar'))->toBe($jarBytes)
        ->and(is_file($paths->xrechnungDir.'/scenarios.xml'))->toBeTrue();

    File::delete($xrechnung['path']);
});

test('install rejects misconfigured base path before downloading', function (): void {
    config()->set('kosit-validator.installer.allow_untrusted_base_path', false);
    config()->set('kosit-validator.installer.storage_root', storage_path('app/private'));
    config()->set('kosit-validator.base_path', '/tmp/kosit-evil-'.uniqid());

    fakeKositJavaProcess();

    $this->artisan('kosit:install')
        ->expectsOutputToContain('must be under')
        ->assertFailed();

    Http::assertNothingSent();
});

test('install rejects escaping path segment before downloading', function (): void {
    config()->set('kosit-validator.paths.validator_dir', '../evil');

    fakeKositJavaProcess();

    $this->artisan('kosit:install')
        ->expectsOutputToContain('single directory name')
        ->assertFailed();

    Http::assertNothingSent();
});

test('install rejects untrusted download host before downloading', function (): void {
    config()->set('kosit-validator.installer.allow_untrusted_download_hosts', false);
    config()->set(
        'kosit-validator.validator.download_url',
        'https://evil.test/validator-1.6.2-standalone.jar',
    );

    fakeKositJavaProcess();

    $this->artisan('kosit:install')
        ->expectsOutputToContain('is not allowed')
        ->assertFailed();

    Http::assertNothingSent();
});
