<?php

declare(strict_types=1);

use Moox\KositValidator\Actions\RecordKositValidation;
use Moox\KositValidator\DTOs\KositResult;
use Moox\KositValidator\Tests\TestCase;

uses(TestCase::class);

test('record persists with new column shape and no subject', function (): void {
    $result = new KositResult(
        exitCode: 0,
        stdout: '',
        stderr: '',
        reportXmlPath: '/abs/path/file-report.xml',
        reportHtmlPath: '/abs/path/file-report.html',
        xmlPath: '/abs/path/file.xml',
    );

    $validation = app(RecordKositValidation::class)($result);

    expect($validation->exists)->toBeTrue()
        ->and($validation->input_path)->toBe('/abs/path/file.xml')
        ->and($validation->report_xml_path)->toBe('/abs/path/file-report.xml')
        ->and($validation->report_html_path)->toBe('/abs/path/file-report.html')
        ->and($validation->passed)->toBeTrue()
        ->and($validation->errors)->toBe([]);

    $fresh = $validation->fresh();
    expect($fresh->getAttributes())->not->toHaveKey('subject_type')
        ->and($fresh->getAttributes())->not->toHaveKey('subject_id')
        ->and($fresh->getAttributes())->not->toHaveKey('xml_path')
        ->and($fresh->getAttributes())->not->toHaveKey('report_path');
});
