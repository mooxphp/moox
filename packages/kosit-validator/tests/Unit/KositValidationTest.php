<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Moox\KositValidator\Models\KositValidation;
use Moox\KositValidator\Tests\TestCase;

uses(TestCase::class);

it('casts errors to array', function (): void {
    $validation = KositValidation::query()->create([
        'passed' => false,
        'errors' => [['type' => 'error', 'text' => 'Invalid total', 'location' => null, 'rule' => null]],
        'validated_at' => now(),
    ]);

    $validation->refresh();

    expect($validation->errors)->toBeArray()
        ->and($validation->errors[0]['text'])->toBe('Invalid total');
});

it('casts passed to boolean', function (): void {
    $validation = KositValidation::query()->create([
        'passed' => 1,
        'validated_at' => now(),
    ]);

    $validation->refresh();

    expect($validation->passed)->toBeBool()->toBeTrue();
});

it('casts validated_at to a Carbon instance', function (): void {
    $validatedAt = now()->startOfSecond();

    $validation = KositValidation::query()->create([
        'passed' => true,
        'validated_at' => $validatedAt,
    ]);

    $validation->refresh();

    expect($validation->validated_at)->toBeInstanceOf(Carbon::class)
        ->and($validation->validated_at->equalTo($validatedAt))->toBeTrue();
});

it('scopes to passed validations', function (): void {
    KositValidation::query()->create([
        'passed' => true,
        'validated_at' => now(),
    ]);

    KositValidation::query()->create([
        'passed' => false,
        'validated_at' => now(),
    ]);

    expect(KositValidation::query()->passed()->count())->toBe(1);
});

it('scopes to failed validations', function (): void {
    KositValidation::query()->create([
        'passed' => true,
        'validated_at' => now(),
    ]);

    KositValidation::query()->create([
        'passed' => false,
        'validated_at' => now(),
    ]);

    expect(KositValidation::query()->failed()->count())->toBe(1);
});

it('builds a filename label from the input xml path', function (): void {
    $validation = KositValidation::query()->create([
        'input_path' => '/storage/invoices/Rechnung_123.xml',
        'passed' => true,
        'validated_at' => now(),
    ]);

    expect($validation->filenameLabel())->toBe('Rechnung_123.xml');
});

it('returns an em dash filename label when input xml path is null', function (): void {
    $validation = KositValidation::query()->create([
        'passed' => true,
        'validated_at' => now(),
    ]);

    expect($validation->filenameLabel())->toBe('—');
});

it('reads the HTML report path from the stored column', function (): void {
    $validation = KositValidation::query()->create([
        'passed' => true,
        'report_xml_path' => '/tmp/sample-report.xml',
        'report_html_path' => '/tmp/sample-report.html',
        'validated_at' => now(),
    ]);

    expect($validation->reportHtmlPath())->toBe('/tmp/sample-report.html');
});
