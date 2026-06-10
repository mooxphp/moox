<?php

declare(strict_types=1);

use Moox\KositValidator\DTOs\KositResult;

test('passed and failed reflect exit code', function (): void {
    $ok = new KositResult(0, '', '', null, null);
    expect($ok->passed())->toBeTrue()->and($ok->failed())->toBeFalse();

    $bad = new KositResult(1, '', '', null, null);
    expect($bad->passed())->toBeFalse()->and($bad->failed())->toBeTrue();
});

test('errors falls back to stderr when no report and failed', function (): void {
    $result = new KositResult(1, '', 'Something broke', null, null);
    expect($result->errors())->toBe(['Something broke']);
});

test('errors is empty when passed without report', function (): void {
    $result = new KositResult(0, '', '', null, null);
    expect($result->errors())->toBe([]);
});

test('errors prefers stdout when stderr empty and failed', function (): void {
    $result = new KositResult(1, 'stdout msg', '', null, null);
    expect($result->errors())->toBe(['stdout msg']);
});

test('validationMessages falls back to SVRL when report has no rep:message', function (): void {
    $path = __DIR__.'/../fixtures/kosit-report-svrl.xml';
    $failed = new KositResult(1, '', '', $path, null);
    $messages = $failed->validationMessages();

    expect($messages)->toHaveCount(3)
        ->and($messages[0])->toMatchArray([
            'type' => 'error',
            'text' => 'Invoice total is wrong',
            'location' => '/invoice',
            'rule' => 'BR-DE-01',
        ])
        ->and($messages[1]['type'])->toBe('info')
        ->and($messages[1]['text'])->toBe('Optional notice')
        ->and($messages[2]['type'])->toBe('warning')
        ->and($messages[2]['text'])->toBe('Deprecated field used');

    expect($failed->errors())->toBe(['Invoice total is wrong']);
});

test('validationMessages parses rep:message with level as primary format', function (): void {
    $path = __DIR__.'/../fixtures/kosit-report-rep-message.xml';
    $result = new KositResult(1, '', '', $path, null);

    expect($result->validationMessages())->toBe([
        [
            'type' => 'error',
            'text' => 'Scenario not applicable',
            'location' => '/invoice',
            'rule' => 'BR-SCENARIO',
        ],
    ])->and($result->errors())->toBe(['Scenario not applicable']);
});

test('validationMessages keeps warnings when passed and errors() stays empty', function (): void {
    $path = __DIR__.'/../fixtures/kosit-report-warnings-only.xml';
    $ok = new KositResult(0, '', '', $path, null);

    expect($ok->errors())->toBe([])
        ->and($ok->validationMessages())->toBe([
            [
                'type' => 'warning',
                'text' => 'Heads up only',
                'location' => null,
                'rule' => 'W-1',
            ],
        ]);
});
