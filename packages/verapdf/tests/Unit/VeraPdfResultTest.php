<?php

declare(strict_types=1);

use Moox\VeraPdf\DTOs\VeraPdfResult;

test('passed and failed reflect exit code when no report', function (): void {
    $ok = new VeraPdfResult(0, '', '', null, null);
    expect($ok->passed())->toBeTrue()->and($ok->failed())->toBeFalse();

    $bad = new VeraPdfResult(1, '', '', null, null);
    expect($bad->passed())->toBeFalse()->and($bad->failed())->toBeTrue();
});

test('errors falls back to stderr when no report and failed', function (): void {
    $result = new VeraPdfResult(1, '', 'Something broke', null, null);
    expect($result->errors())->toBe(['Something broke']);
});

test('errors is empty when passed without report', function (): void {
    $result = new VeraPdfResult(0, '', '', null, null);
    expect($result->errors())->toBe([]);
});

test('errors prefers stdout when stderr empty and failed', function (): void {
    $result = new VeraPdfResult(1, 'stdout msg', '', null, null);
    expect($result->errors())->toBe(['stdout msg']);
});

test('passed reads isCompliant from veraPDF report fixture', function (): void {
    $passPath = __DIR__.'/../fixtures/verapdf-report-pass.xml';
    $failPath = __DIR__.'/../fixtures/verapdf-report-fail.xml';

    $pass = new VeraPdfResult(1, '', '', $passPath, null);
    expect($pass->passed())->toBeTrue()->and($pass->errors())->toBe([]);

    $fail = new VeraPdfResult(0, '', '', $failPath, null);
    expect($fail->passed())->toBeFalse();
});

test('validationMessages parses failed rules from veraPDF report', function (): void {
    $path = __DIR__.'/../fixtures/verapdf-report-fail.xml';
    $result = new VeraPdfResult(1, '', '', $path, null);

    expect($result->validationMessages())->toBe([
        [
            'type' => 'error',
            'text' => 'If an image dictionary contains the Interpolate key, its value shall be false.',
            'location' => 'root/document[0]/pages[0]/contentStream[0]/operators[65]/xObject[0]',
            'rule' => '6.2.11.3#1',
        ],
    ])->and($result->errors())->toBe([
        'If an image dictionary contains the Interpolate key, its value shall be false.',
    ]);
});
