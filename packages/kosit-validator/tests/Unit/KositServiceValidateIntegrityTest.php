<?php

declare(strict_types=1);

use Moox\KositValidator\Services\KositService;
use Moox\KositValidator\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    configureKositInstallTestDefaults();
});

afterEach(function (): void {
    cleanupKositConfiguredPaths('kosit-validator.base_path');

    foreach (glob(sys_get_temp_dir().'/kosit-invoice-*') ?: [] as $xml) {
        if (is_file($xml)) {
            @unlink($xml);
        }
    }
});

it('rejects a tampered validator jar before running java', function (): void {
    seedKositInstallLayout();
    $jarBytes = 'valid-jar-bytes';
    config(['kosit-validator.validator.sha256' => hash('sha256', $jarBytes)]);

    file_put_contents(app(KositService::class)->jarPath(), 'tampered');

    fakeKositJavaProcessRejectingJarExecution();
    $xml = kositTempPath('invoice').'.xml';
    file_put_contents($xml, '<invoice/>');

    expect(fn () => app(KositService::class)->validate($xml))
        ->toThrow(RuntimeException::class, 'checksum mismatch');
});

it('allows validate when the validator jar matches the pinned checksum', function (): void {
    $jarBytes = 'valid-jar-bytes';
    config(['kosit-validator.validator.sha256' => hash('sha256', $jarBytes)]);

    seedKositInstallLayout();
    file_put_contents(app(KositService::class)->jarPath(), $jarBytes);

    fakeKositJavaProcess();
    $xml = kositTempPath('invoice').'.xml';
    file_put_contents($xml, '<invoice/>');

    $result = app(KositService::class)->validate($xml);

    expect($result->exitCode)->toBe(0);
});
