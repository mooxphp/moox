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
    ['jarPath' => $jarPath] = seedVerifiedKositInstall();
    file_put_contents($jarPath, 'tampered');

    fakeKositJavaProcessRejectingJarExecution();

    expect(fn () => app(KositService::class)->validate(kositInvoiceXmlPath()))
        ->toThrow(RuntimeException::class, 'checksum mismatch');
});

it('rejects a tampered xrechnung bundle before running java', function (): void {
    ['bundlePath' => $bundlePath] = seedVerifiedKositInstall();
    file_put_contents($bundlePath, 'tampered-bundle');

    fakeKositJavaProcessRejectingJarExecution();

    expect(fn () => app(KositService::class)->validate(kositInvoiceXmlPath()))
        ->toThrow(RuntimeException::class, 'checksum mismatch');
});

it('rejects validate when the xrechnung bundle is missing', function (): void {
    ['bundlePath' => $bundlePath] = seedVerifiedKositInstall();
    unlink($bundlePath);

    fakeKositJavaProcessRejectingJarExecution();

    expect(fn () => app(KositService::class)->validate(kositInvoiceXmlPath()))
        ->toThrow(RuntimeException::class, 'kosit:install --force');
});

it('uses the verified xrechnung bundle instead of a tampered extracted tree', function (): void {
    ['paths' => $paths] = seedVerifiedKositInstall();
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

    $result = app(KositService::class)->validate(kositInvoiceXmlPath());

    expect($result->exitCode)->toBe(0)
        ->and($capturedScenariosPath)->toBeString()
        ->and(str_starts_with((string) $capturedScenariosPath, $paths->xrechnungDir))->toBeFalse()
        ->and(str_starts_with((string) $capturedScenariosPath, sys_get_temp_dir()))->toBeTrue();
});

it('allows validate when the validator jar matches the pinned checksum', function (): void {
    seedVerifiedKositInstall();
    fakeKositJavaProcess();

    $result = app(KositService::class)->validate(kositInvoiceXmlPath());

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
