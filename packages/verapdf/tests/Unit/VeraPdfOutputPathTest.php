<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Moox\VeraPdf\Support\VeraPdfOutputPath;
use Moox\VeraPdf\Tests\TestCase;

uses(TestCase::class);

it('resolves the configured output path', function (): void {
    $tempBase = sys_get_temp_dir().'/verapdf-out-'.uniqid('', true);
    config(['verapdf.output.path' => $tempBase]);

    expect(VeraPdfOutputPath::resolve())->toBe($tempBase)
        ->and(is_dir($tempBase))->toBeTrue();

    File::deleteDirectory($tempBase);
});

it('appends an optional subdirectory segment', function (): void {
    $tempBase = sys_get_temp_dir().'/verapdf-out-'.uniqid('', true);
    config(['verapdf.output.path' => $tempBase]);

    expect(VeraPdfOutputPath::resolve('2026/07/17'))
        ->toBe($tempBase.'/2026/07/17');

    File::deleteDirectory($tempBase);
});

it('rejects parent-path segments in subdirectory', function (): void {
    config(['verapdf.output.path' => '/tmp/verapdf-reports']);

    expect(fn () => VeraPdfOutputPath::resolve('../escape'))
        ->toThrow(InvalidArgumentException::class);
});
