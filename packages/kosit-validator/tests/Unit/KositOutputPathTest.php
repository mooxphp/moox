<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Moox\KositValidator\Support\KositOutputPath;
use Moox\KositValidator\Tests\TestCase;

uses(TestCase::class);

it('resolves the configured output path', function (): void {
    config(['kosit-validator.output.path' => '/tmp/kosit-reports']);

    expect(KositOutputPath::resolve())->toBe('/tmp/kosit-reports');
});

it('appends an optional subdirectory segment', function (): void {
    config(['kosit-validator.output.path' => '/tmp/kosit-reports']);

    expect(KositOutputPath::resolve('default/2026/01/15'))
        ->toBe('/tmp/kosit-reports/default/2026/01/15');
});

it('falls back to legacy report_path config when output.path is empty', function (): void {
    $tempBase = sys_get_temp_dir().'/kosit-legacy-'.uniqid('', true);
    config([
        'kosit-validator.output.path' => '',
        'kosit-validator.report_path' => $tempBase,
    ]);

    expect(KositOutputPath::resolve())->toBe($tempBase)
        ->and(is_dir($tempBase))->toBeTrue();

    File::deleteDirectory($tempBase);
});

it('resolve creates the base directory if it does not exist', function (): void {
    $tempBase = sys_get_temp_dir().'/kosit-output-'.uniqid('', true);
    config(['kosit-validator.output.path' => $tempBase]);

    expect(is_dir($tempBase))->toBeFalse();

    $resolved = KositOutputPath::resolve();

    expect($resolved)->toBe($tempBase)
        ->and(is_dir($resolved))->toBeTrue();

    File::deleteDirectory($tempBase);
});

it('resolve creates nested subdirectories', function (): void {
    $tempBase = sys_get_temp_dir().'/kosit-nested-'.uniqid('', true);
    config(['kosit-validator.output.path' => $tempBase]);

    $resolved = KositOutputPath::resolve('default/2026/05/20');
    $expected = $tempBase.'/default/2026/05/20';

    expect($resolved)->toBe($expected)
        ->and(is_dir($resolved))->toBeTrue();

    File::deleteDirectory($tempBase);
});
