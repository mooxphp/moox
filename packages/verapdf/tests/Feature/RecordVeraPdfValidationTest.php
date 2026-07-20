<?php

declare(strict_types=1);

use Moox\VeraPdf\Actions\RecordVeraPdfValidation;
use Moox\VeraPdf\Tests\TestCase;

uses(TestCase::class);

test('record persists with column shape and no subject morph on validation row', function (): void {
    $validation = app(RecordVeraPdfValidation::class)(makeVeraPdfResult(
        reportXmlPath: '/abs/path/file-report.xml',
        reportHtmlPath: '/abs/path/file-report.html',
        pdfPath: '/abs/path/file.pdf',
    ));

    expect($validation->exists)->toBeTrue()
        ->and($validation->input_path)->toBe('/abs/path/file.pdf')
        ->and($validation->report_xml_path)->toBe('/abs/path/file-report.xml')
        ->and($validation->report_html_path)->toBe('/abs/path/file-report.html')
        ->and($validation->passed)->toBeTrue()
        ->and($validation->errors)->toBe([]);

    $fresh = $validation->fresh();
    expect($fresh->getAttributes())->not->toHaveKey('subject_type')
        ->and($fresh->getAttributes())->not->toHaveKey('subject_id')
        ->and($fresh->getAttributes())->not->toHaveKey('pdf_path')
        ->and($fresh->getAttributes())->not->toHaveKey('report_path');
});
