<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Moox\VeraPdf\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config()->set('verapdf.base_path', sys_get_temp_dir().'/verapdf-install-'.uniqid());
    config()->set('verapdf.paths.launcher', 'verapdf');
    config()->set('verapdf.java_binary', 'java');
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
