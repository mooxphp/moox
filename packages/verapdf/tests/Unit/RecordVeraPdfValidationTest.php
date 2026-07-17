<?php

declare(strict_types=1);

use Moox\VeraPdf\Actions\RecordVeraPdfValidation;
use Moox\VeraPdf\DTOs\VeraPdfResult;
use Moox\VeraPdf\Models\VeraPdfValidation;
use Moox\VeraPdf\Tests\TestCase;

uses(TestCase::class);

it('persists a VeraPdfValidation audit row with path columns', function (): void {
    $validation = app(RecordVeraPdfValidation::class)(new VeraPdfResult(
        exitCode: 0,
        stdout: '',
        stderr: '',
        reportXmlPath: '/tmp/report.xml',
        reportHtmlPath: '/tmp/report.html',
        pdfPath: '/tmp/file.pdf',
    ));

    expect($validation)->toBeInstanceOf(VeraPdfValidation::class)
        ->and($validation->passed)->toBeTrue()
        ->and($validation->input_path)->toBe('/tmp/file.pdf')
        ->and($validation->report_xml_path)->toBe('/tmp/report.xml')
        ->and($validation->report_html_path)->toBe('/tmp/report.html');
});

it('stores errors as JSON from report fixture', function (): void {
    $reportPath = __DIR__.'/../fixtures/verapdf-report-fail.xml';

    $validation = app(RecordVeraPdfValidation::class)(new VeraPdfResult(
        exitCode: 1,
        stdout: '',
        stderr: '',
        reportXmlPath: $reportPath,
        reportHtmlPath: null,
        pdfPath: '/tmp/file.pdf',
    ));

    $validation->refresh();

    expect($validation->passed)->toBeFalse()
        ->and($validation->errors)->toBe([
            [
                'type' => 'error',
                'text' => 'If an image dictionary contains the Interpolate key, its value shall be false.',
                'location' => 'root/document[0]/pages[0]/contentStream[0]/operators[65]/xObject[0]',
                'rule' => '6.2.11.3#1',
            ],
        ]);
});

it('stores the validated_at timestamp', function (): void {
    $before = now()->subSecond();

    $validation = app(RecordVeraPdfValidation::class)(new VeraPdfResult(
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
    $validation = app(RecordVeraPdfValidation::class)(new VeraPdfResult(
        exitCode: 0,
        stdout: '',
        stderr: '',
        reportXmlPath: null,
        reportHtmlPath: null,
        pdfPath: null,
    ));

    expect($validation->input_path)->toBeNull();
});
