<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Moox\KositValidator\Services\KositService;
use Moox\KositValidator\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    configureKositInstallTestDefaults();
});

afterEach(function (): void {
    cleanupKositConfiguredPaths('kosit-validator.base_path');
});

it('resolves the expected jar filename deterministically', function (): void {
    $base = (string) config('kosit-validator.base_path');
    $validatorDir = $base.'/'.config('kosit-validator.paths.validator_dir');
    File::ensureDirectoryExists($validatorDir);

    file_put_contents($validatorDir.'/validator-9.9.9-standalone.jar', 'decoy');
    file_put_contents($validatorDir.'/validator-1.6.2-standalone.jar', 'expected');

    $service = app(KositService::class);

    expect($service->jarPath())->toBe($validatorDir.'/validator-1.6.2-standalone.jar');
});

it('throws when the expected jar is missing', function (): void {
    $base = (string) config('kosit-validator.base_path');
    $validatorDir = $base.'/'.config('kosit-validator.paths.validator_dir');
    File::ensureDirectoryExists($validatorDir);
    file_put_contents($validatorDir.'/validator-9.9.9-standalone.jar', 'decoy');

    $service = app(KositService::class);

    expect(fn () => $service->jarPath())
        ->toThrow(RuntimeException::class, 'Expected validator JAR validator-1.6.2-standalone.jar');
});
