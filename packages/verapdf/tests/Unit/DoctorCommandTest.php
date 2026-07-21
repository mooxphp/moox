<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Moox\VeraPdf\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    configureDoctorTestDefaults();
});

afterEach(function (): void {
    cleanupConfiguredPaths('verapdf.base_path', 'verapdf.output.path');
});

test('doctor succeeds for a CLI-only install layout', function (): void {
    seedCliInstallLayout();

    fakeJavaProcess();

    $this->artisan('verapdf:doctor')
        ->expectsOutputToContain('CLI binaries: OK')
        ->assertSuccessful();
});

test('doctor fails when only GUI artefacts are present', function (): void {
    $base = (string) config('verapdf.base_path');
    File::ensureDirectoryExists($base.'/bin');
    file_put_contents($base.'/verapdf-gui', "#!/bin/sh\nexit 0\n");
    chmod($base.'/verapdf-gui', 0755);
    file_put_contents($base.'/bin/gui-1.30.1.jar', 'fake');

    fakeJavaProcess();

    $this->artisan('verapdf:doctor')
        ->expectsOutputToContain('Launcher:')
        ->assertFailed();
});
