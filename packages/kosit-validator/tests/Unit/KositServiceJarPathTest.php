<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Moox\KositValidator\Services\KositService;
use Moox\KositValidator\Support\KositInstallPaths;
use Moox\KositValidator\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    configureKositInstallTestDefaults();
});

afterEach(function (): void {
    cleanupKositConfiguredPaths('kosit-validator.base_path');
});

it('resolves the expected jar filename deterministically', function (): void {
    $paths = KositInstallPaths::fromConfig();
    File::ensureDirectoryExists($paths->validatorDir);

    file_put_contents($paths->validatorDir.'/validator-9.9.9-standalone.jar', 'decoy');
    file_put_contents($paths->validatorDir.'/validator-1.6.2-standalone.jar', 'expected');

    $service = app(KositService::class);

    expect($service->jarPath())->toBe($paths->validatorDir.'/validator-1.6.2-standalone.jar');
});

it('throws when the expected jar is missing', function (): void {
    $paths = KositInstallPaths::fromConfig();
    File::ensureDirectoryExists($paths->validatorDir);
    file_put_contents($paths->validatorDir.'/validator-9.9.9-standalone.jar', 'decoy');

    $service = app(KositService::class);

    expect(fn () => $service->jarPath())
        ->toThrow(RuntimeException::class, 'Expected validator JAR validator-1.6.2-standalone.jar');
});

it('rejects invalid validator_dir segment at runtime', function (): void {
    config()->set('kosit-validator.paths.validator_dir', '../evil');

    expect(fn () => app(KositService::class)->jarPath())
        ->toThrow(RuntimeException::class, 'single directory name');
});

it('rejects invalid xrechnung_dir segment at runtime', function (): void {
    config()->set('kosit-validator.paths.xrechnung_dir', 'foo/bar');

    expect(fn () => app(KositService::class)->scenariosPath())
        ->toThrow(RuntimeException::class, 'single directory name');
});
