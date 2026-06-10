<?php

declare(strict_types=1);

use Moox\KositValidator\Actions\RecordKositValidation;
use Moox\KositValidator\DTOs\KositResult;
use Moox\KositValidator\Models\KositValidation;
use Moox\KositValidator\Tests\TestCase;

uses(TestCase::class);

it('persists a KositValidation audit row with path columns', function (): void {
    $validation = app(RecordKositValidation::class)(new KositResult(
        exitCode: 0,
        stdout: '',
        stderr: '',
        reportXmlPath: '/tmp/report.xml',
        reportHtmlPath: '/tmp/report.html',
        xmlPath: '/tmp/invoice.xml',
    ));

    expect($validation)->toBeInstanceOf(KositValidation::class)
        ->and($validation->passed)->toBeTrue()
        ->and($validation->input_path)->toBe('/tmp/invoice.xml')
        ->and($validation->report_xml_path)->toBe('/tmp/report.xml')
        ->and($validation->report_html_path)->toBe('/tmp/report.html');
});

it('stores errors as JSON', function (): void {
    $reportPath = __DIR__.'/../fixtures/kosit-report-rep-message.xml';

    $errors = [
        [
            'type' => 'error',
            'text' => 'Scenario not applicable',
            'location' => '/invoice',
            'rule' => 'BR-SCENARIO',
        ],
    ];

    $validation = app(RecordKositValidation::class)(new KositResult(
        exitCode: 1,
        stdout: '',
        stderr: '',
        reportXmlPath: $reportPath,
        reportHtmlPath: null,
    ));

    $validation->refresh();

    expect($validation->errors)->toBe($errors)
        ->and(json_encode($validation->errors))->toContain('Scenario not applicable');
});

it('stores the validated_at timestamp', function (): void {
    $before = now()->subSecond();

    $validation = app(RecordKositValidation::class)(new KositResult(
        exitCode: 0,
        stdout: '',
        stderr: '',
        reportXmlPath: null,
        reportHtmlPath: null,
    ));

    $after = now()->addSecond();

    expect($validation->validated_at)->not->toBeNull()
        ->and($validation->validated_at->between($before, $after))->toBeTrue();
});

it('handles a null input path gracefully', function (): void {
    $validation = app(RecordKositValidation::class)(new KositResult(
        exitCode: 0,
        stdout: '',
        stderr: '',
        reportXmlPath: null,
        reportHtmlPath: null,
        xmlPath: null,
    ));

    expect($validation->input_path)->toBeNull();
});
