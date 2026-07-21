<?php

declare(strict_types=1);

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Moox\KositValidator\Services\KositService;
use Moox\KositValidator\Support\KositInstallPaths;
use Moox\KositValidator\Support\XrechnungBundlePath;
use Moox\KositValidator\Support\XrechnungExecutionGuard;
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

it('rejects a tampered xrechnung bundle before running java', function (): void {
    seedKositInstallLayout();
    $jarBytes = 'valid-jar-bytes';
    config(['kosit-validator.validator.sha256' => hash('sha256', $jarBytes)]);
    file_put_contents(app(KositService::class)->jarPath(), $jarBytes);

    $paths = KositInstallPaths::fromConfig();
    file_put_contents($paths->xrechnungDir.'/'.XrechnungBundlePath::BUNDLE_FILENAME, 'tampered-bundle');

    fakeKositJavaProcessRejectingJarExecution();
    $xml = kositTempPath('invoice').'.xml';
    file_put_contents($xml, '<invoice/>');

    expect(fn () => app(KositService::class)->validate($xml))
        ->toThrow(RuntimeException::class, 'checksum mismatch');
});

it('rejects validate when the xrechnung bundle is missing', function (): void {
    seedKositInstallLayout();
    $jarBytes = 'valid-jar-bytes';
    config(['kosit-validator.validator.sha256' => hash('sha256', $jarBytes)]);
    file_put_contents(app(KositService::class)->jarPath(), $jarBytes);

    $paths = KositInstallPaths::fromConfig();
    unlink($paths->xrechnungDir.'/'.XrechnungBundlePath::BUNDLE_FILENAME);

    fakeKositJavaProcessRejectingJarExecution();
    $xml = kositTempPath('invoice').'.xml';
    file_put_contents($xml, '<invoice/>');

    expect(fn () => app(KositService::class)->validate($xml))
        ->toThrow(RuntimeException::class, 'kosit:install --force');
});

it('uses the verified xrechnung bundle instead of a tampered extracted tree', function (): void {
    seedKositInstallLayout();
    $jarBytes = 'valid-jar-bytes';
    config(['kosit-validator.validator.sha256' => hash('sha256', $jarBytes)]);
    file_put_contents(app(KositService::class)->jarPath(), $jarBytes);

    $paths = KositInstallPaths::fromConfig();
    file_put_contents($paths->xrechnungDir.'/scenarios.xml', '<scenarios><tampered/></scenarios>');

    $capturedScenariosPath = null;

    Process::fake(function (PendingProcess $process) use (&$capturedScenariosPath) {
        $command = $process->command;

        if (is_array($command)) {
            $scenariosFlagIndex = array_search('-s', $command, true);

            if ($scenariosFlagIndex !== false) {
                $capturedScenariosPath = $command[$scenariosFlagIndex + 1] ?? null;
            }
        }

        return Process::result(
            output: '',
            errorOutput: 'openjdk version "17"',
            exitCode: 0,
        );
    });

    $xml = kositTempPath('invoice').'.xml';
    file_put_contents($xml, '<invoice/>');

    $result = app(KositService::class)->validate($xml);

    expect($result->exitCode)->toBe(0)
        ->and($capturedScenariosPath)->toBeString()
        ->and(str_starts_with((string) $capturedScenariosPath, $paths->xrechnungDir))->toBeFalse()
        ->and(str_starts_with((string) $capturedScenariosPath, sys_get_temp_dir()))->toBeTrue();
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

it('reports not installed when the xrechnung bundle is missing', function (): void {
    seedKositInstallLayout();
    $paths = KositInstallPaths::fromConfig();
    unlink($paths->xrechnungDir.'/'.XrechnungBundlePath::BUNDLE_FILENAME);

    expect(app(KositService::class)->isInstalled())->toBeFalse();
});

it('verifies and extracts the xrechnung bundle within acceptable overhead', function (): void {
    seedKositInstallLayout();
    $bundle = buildBenignXrechnungZip();

    $start = hrtime(true);

    for ($i = 0; $i < 100; $i++) {
        XrechnungExecutionGuard::withVerifiedExtractedDir(
            KositInstallPaths::fromConfig(),
            $bundle['sha256'],
            static fn (): null => null,
        );
    }

    $averageMilliseconds = (hrtime(true) - $start) / 1_000_000 / 100;

    expect($averageMilliseconds)->toBeLessThan(5.0);
});
