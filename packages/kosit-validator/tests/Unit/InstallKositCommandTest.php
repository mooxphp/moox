<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
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
    $validatorDir = $base.'/'.config('kosit-validator.paths.validator_dir');
    file_put_contents($validatorDir.'/validator-1.6.2-standalone.jar', 'existing-jar');

    $xrechnung = buildBenignXrechnungZip();
    fakeKositDownloads('tampered-jar', hash('sha256', 'different-jar-bytes'), $xrechnung['bytes'], $xrechnung['sha256']);

    $jarTracker = fakeKositJavaProcessTrackingJar();

    $this->artisan('kosit:install', ['--force' => true])
        ->expectsOutputToContain('checksum mismatch')
        ->assertFailed();

    expect(is_file($validatorDir.'/validator-1.6.2-standalone.jar'))->toBeTrue()
        ->and((string) file_get_contents($validatorDir.'/validator-1.6.2-standalone.jar'))->toBe('existing-jar')
        ->and($jarTracker['jarRan'])->toBeFalse();

    File::delete($xrechnung['path']);
});

test('install aborts on xrechnung checksum mismatch without wiping an existing install', function (): void {
    $base = seedKositInstallLayout();
    $validatorDir = $base.'/'.config('kosit-validator.paths.validator_dir');

    $jarBytes = 'valid-jar-bytes';
    fakeKositDownloads($jarBytes, hash('sha256', $jarBytes), 'tampered-zip', hash('sha256', 'different-zip-bytes'));

    fakeKositJavaProcess();

    $this->artisan('kosit:install', ['--force' => true])
        ->expectsOutputToContain('checksum mismatch')
        ->assertFailed();

    expect((string) file_get_contents($validatorDir.'/validator-1.6.2-standalone.jar'))->toBe('existing-jar');
});

test('install aborts on zip-slip archive', function (bool $force, bool $seedExisting): void {
    $base = $seedExisting ? seedKositInstallLayout() : (string) config('kosit-validator.base_path');
    $validatorDir = $base.'/'.config('kosit-validator.paths.validator_dir');

    if ($seedExisting) {
        file_put_contents($validatorDir.'/validator-1.6.2-standalone.jar', 'existing-jar');
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
        expect(is_file($validatorDir.'/validator-1.6.2-standalone.jar'))->toBeTrue()
            ->and((string) file_get_contents($validatorDir.'/validator-1.6.2-standalone.jar'))->toBe('existing-jar');
    } else {
        expect(is_dir($validatorDir))->toBeFalse();
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

test('install rejects http redirect downgrade during download', function (): void {
    $jarBytes = 'valid-jar-bytes';
    $xrechnung = buildBenignXrechnungZip();

    config()->set('kosit-validator.validator.sha256', hash('sha256', $jarBytes));
    config()->set('kosit-validator.xrechnung.sha256', $xrechnung['sha256']);

    Http::fake([
        config('kosit-validator.validator.download_url') => Http::response('', 302, [
            'Location' => 'http://evil.test/validator-1.6.2-standalone.jar',
        ]),
        config('kosit-validator.xrechnung.download_url') => Http::response($xrechnung['bytes'], 200),
    ]);

    fakeKositJavaProcess();

    $this->artisan('kosit:install')
        ->assertFailed();

    $base = (string) config('kosit-validator.base_path');
    expect(is_dir($base))->toBeFalse();

    File::delete($xrechnung['path']);
});

test('install succeeds with verified downloads', function (): void {
    $jarBytes = 'valid-jar-bytes';
    $xrechnung = buildBenignXrechnungZip();
    fakeKositDownloads($jarBytes, hash('sha256', $jarBytes), $xrechnung['bytes'], $xrechnung['sha256']);

    fakeKositJavaProcess();

    $this->artisan('kosit:install')
        ->expectsOutputToContain('installation successful')
        ->assertSuccessful();

    $base = (string) config('kosit-validator.base_path');
    $validatorDir = $base.'/'.config('kosit-validator.paths.validator_dir');
    $xrechnungDir = $base.'/'.config('kosit-validator.paths.xrechnung_dir');

    expect(is_file($validatorDir.'/validator-1.6.2-standalone.jar'))->toBeTrue()
        ->and((string) file_get_contents($validatorDir.'/validator-1.6.2-standalone.jar'))->toBe($jarBytes)
        ->and(is_file($xrechnungDir.'/scenarios.xml'))->toBeTrue();

    File::delete($xrechnung['path']);
});
