<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Moox\VeraPdf\Services\VeraPdfService;
use Moox\VeraPdf\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    config()->set('verapdf.base_path', sys_get_temp_dir().'/verapdf-test-'.uniqid());
    config()->set('verapdf.paths.launcher', 'verapdf');
    config()->set('verapdf.java_binary', 'java');
});

afterEach(function (): void {
    $base = config('verapdf.base_path');
    if (is_string($base) && is_dir($base)) {
        File::deleteDirectory($base);
    }
});

test('isInstalled is false when launcher is missing', function (): void {
    expect(app(VeraPdfService::class)->isInstalled())->toBeFalse();
});

test('isInstalled is true when launcher exists and is executable', function (): void {
    $base = config('verapdf.base_path');
    File::ensureDirectoryExists($base);
    $launcher = $base.'/verapdf';
    file_put_contents($launcher, "#!/bin/sh\nexit 0\n");
    chmod($launcher, 0755);

    expect(app(VeraPdfService::class)->isInstalled())->toBeTrue();
});

test('hasCliBinaries requires launcher and bin cli jar', function (): void {
    $base = (string) config('verapdf.base_path');
    File::ensureDirectoryExists($base.'/bin');
    file_put_contents($base.'/verapdf', "#!/bin/sh\nexit 0\n");
    chmod($base.'/verapdf', 0755);

    $service = app(VeraPdfService::class);
    expect($service->hasCliBinaries())->toBeFalse();

    file_put_contents($base.'/bin/cli-1.30.1.jar', 'fake');
    expect($service->hasCliBinaries())->toBeTrue();
    expect($service->hasGuiArtefacts())->toBeFalse();
});

test('hasGuiArtefacts detects gui launcher or jar', function (): void {
    $base = (string) config('verapdf.base_path');
    File::ensureDirectoryExists($base.'/bin');
    file_put_contents($base.'/verapdf-gui', "#!/bin/sh\nexit 0\n");
    file_put_contents($base.'/bin/gui-1.30.1.jar', 'fake');

    expect(app(VeraPdfService::class)->hasGuiArtefacts())->toBeTrue();
});

test('javaAvailable reflects process result', function (): void {
    Process::fake(fn () => Process::result(
        output: '',
        errorOutput: 'openjdk version "17"',
        exitCode: 0,
    ));

    expect(app(VeraPdfService::class)->javaAvailable())->toBeTrue();

    Process::fake(fn () => Process::result(
        output: '',
        errorOutput: 'java: not found',
        exitCode: 127,
    ));

    expect(app(VeraPdfService::class)->javaAvailable())->toBeFalse();
});

test('validate throws a clear error when not installed', function (): void {
    Process::fake(fn () => Process::result(
        output: '',
        errorOutput: 'openjdk',
        exitCode: 0,
    ));

    expect(fn () => app(VeraPdfService::class)->validate('/tmp/missing.pdf'))
        ->toThrow(RuntimeException::class, 'veraPDF is not installed');
});

test('validate throws a clear error when java is missing', function (): void {
    Process::fake(fn () => Process::result(
        output: '',
        errorOutput: 'java: not found',
        exitCode: 127,
    ));

    expect(fn () => app(VeraPdfService::class)->validate('/tmp/missing.pdf'))
        ->toThrow(RuntimeException::class, 'Java not found');
});

test('inspectHealth reflects CLI-only install layout', function (): void {
    seedCliInstallLayout();

    Process::fake(fn () => Process::result(
        output: '',
        errorOutput: 'openjdk version "17"',
        exitCode: 0,
    ));

    $health = app(VeraPdfService::class)->inspectHealth();

    expect($health->javaAvailable)->toBeTrue()
        ->and($health->installed)->toBeTrue()
        ->and($health->cliBinariesPresent)->toBeTrue()
        ->and($health->launcherPath)->not->toBeNull()
        ->and($health->isHealthy())->toBeTrue();
});

test('canonical messages match command output', function (): void {
    $service = app(VeraPdfService::class);

    expect($service->javaMissingMessage())->toContain('Java not found')
        ->and($service->notInstalledMessage())->toContain('verapdf:install');
});
