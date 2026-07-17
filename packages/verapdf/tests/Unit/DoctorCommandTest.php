<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Moox\VeraPdf\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config()->set('verapdf.base_path', sys_get_temp_dir().'/verapdf-doctor-'.uniqid());
    config()->set('verapdf.paths.launcher', 'verapdf');
    config()->set('verapdf.java_binary', 'java');
    config()->set('verapdf.output.path', sys_get_temp_dir().'/verapdf-reports-'.uniqid());
});

afterEach(function (): void {
    foreach (['verapdf.base_path', 'verapdf.output.path'] as $key) {
        $path = config($key);
        if (is_string($path) && is_dir($path)) {
            File::deleteDirectory($path);
        }
    }
});

test('doctor succeeds for a CLI-only install layout', function (): void {
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

    Process::fake(fn () => Process::result(
        output: '',
        errorOutput: 'openjdk version "17"',
        exitCode: 0,
    ));

    $this->artisan('verapdf:doctor')
        ->expectsOutputToContain('Launcher:')
        ->assertFailed();
});
