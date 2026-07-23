<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Moox\KositValidator\Models\KositValidation;
use Moox\KositValidator\Resources\KositValidationResource;
use Moox\KositValidator\Resources\KositValidationResource\Pages\ListKositValidations;
use Moox\KositValidator\Support\KositValidationMessages;
use Moox\KositValidator\Tests\FilamentTestCase;

uses(FilamentTestCase::class, RefreshDatabase::class);

it('exposes list and view pages on the resource', function (): void {
    expect(KositValidationResource::getPages())
        ->toHaveKeys(['index', 'view']);
});

it('defines five dynamic tabs from config', function (): void {
    $tabs = config('kosit-validator.resources.kosit-validation.tabs');

    expect($tabs)->toHaveKeys(['all', 'passed', 'failed', 'with-warnings', 'with-infos']);
});

it('filters passed and failed tabs via simple where conditions', function (): void {
    KositValidation::query()->create([
        'input_path' => '/tmp/passed.xml',
        'passed' => true,
        'validated_at' => now(),
    ]);

    KositValidation::query()->create([
        'input_path' => '/tmp/failed.xml',
        'passed' => false,
        'validated_at' => now(),
    ]);

    $applyConditions = new ReflectionMethod(ListKositValidations::class, 'applyConditions');
    $page = new ListKositValidations;

    $passedConditions = config('kosit-validator.resources.kosit-validation.tabs.passed.query');
    $failedConditions = config('kosit-validator.resources.kosit-validation.tabs.failed.query');

    /** @var Builder<KositValidation> $passedQuery */
    $passedQuery = $applyConditions->invoke($page, KositValidation::query(), $passedConditions);
    /** @var Builder<KositValidation> $failedQuery */
    $failedQuery = $applyConditions->invoke($page, KositValidation::query(), $failedConditions);

    $passedCount = $passedQuery->count();
    $failedCount = $failedQuery->count();

    expect($passedCount)->toBe(1)
        ->and($failedCount)->toBe(1);
});

it('with-warnings tab returns only validations whose errors contain a warning entry', function (): void {
    KositValidation::query()->create([
        'input_path' => '/abs/a.xml',
        'report_xml_path' => '/abs/a-report.xml',
        'report_html_path' => '/abs/a-report.html',
        'passed' => true,
        'errors' => [['type' => 'warning', 'text' => 'Minor issue', 'location' => '', 'rule' => 'X']],
        'validated_at' => now(),
    ]);

    KositValidation::query()->create([
        'input_path' => '/abs/b.xml',
        'report_xml_path' => '/abs/b-report.xml',
        'report_html_path' => '/abs/b-report.html',
        'passed' => false,
        'errors' => [['type' => 'error', 'text' => 'Fatal', 'location' => '', 'rule' => 'Y']],
        'validated_at' => now(),
    ]);

    $applyConditions = new ReflectionMethod(ListKositValidations::class, 'applyConditions');
    $conditions = config('kosit-validator.resources.kosit-validation.tabs.with-warnings.query');

    /** @var Builder<KositValidation> $query */
    $query = $applyConditions->invoke(new ListKositValidations, KositValidation::query(), $conditions);

    expect($query->count())->toBe(1);
});

it('with-infos tab returns only validations whose errors contain an info entry', function (): void {
    KositValidation::query()->create([
        'input_path' => '/abs/info.xml',
        'passed' => true,
        'errors' => [['type' => 'info', 'text' => 'Note', 'location' => '', 'rule' => 'N']],
        'validated_at' => now(),
    ]);

    KositValidation::query()->create([
        'input_path' => '/abs/err.xml',
        'passed' => false,
        'errors' => [['type' => 'error', 'text' => 'Bad', 'location' => '', 'rule' => 'E']],
        'validated_at' => now(),
    ]);

    $applyConditions = new ReflectionMethod(ListKositValidations::class, 'applyConditions');
    $conditions = config('kosit-validator.resources.kosit-validation.tabs.with-infos.query');

    /** @var Builder<KositValidation> $query */
    $query = $applyConditions->invoke(new ListKositValidations, KositValidation::query(), $conditions);

    expect($query->count())->toBe(1);
});

it('search matches filename via input_path', function (): void {
    KositValidation::query()->create([
        'input_path' => '/storage/invoices/Rechnung_42.xml',
        'passed' => true,
        'validated_at' => now(),
    ]);

    KositValidation::query()->create([
        'input_path' => '/storage/invoices/other.xml',
        'passed' => true,
        'validated_at' => now(),
    ]);

    $query = KositValidation::query();
    $query->where(function ($inner) {
        $inner->where('input_path', 'like', '%Rechnung_42%');
    });

    expect($query->count())->toBe(1);
});

it('search matches text inside errors JSON', function (): void {
    KositValidation::query()->create([
        'input_path' => '/tmp/a.xml',
        'passed' => false,
        'errors' => [['type' => 'error', 'text' => 'UniqueKoSiTMarkerXYZ', 'location' => '', 'rule' => 'R']],
        'validated_at' => now(),
    ]);

    KositValidation::query()->create([
        'input_path' => '/tmp/b.xml',
        'passed' => true,
        'errors' => [],
        'validated_at' => now(),
    ]);

    $query = KositValidation::query()->where(function (Builder $inner): void {
        KositValidationMessages::applyErrorsTextSearch($inner, 'UniqueKoSiTMarkerXYZ');
    });

    expect($query->count())->toBe(1);
});

it('passed ternary filter narrows validations by passed flag', function (): void {
    KositValidation::query()->create([
        'input_path' => '/tmp/ok.xml',
        'passed' => true,
        'validated_at' => now(),
    ]);

    KositValidation::query()->create([
        'input_path' => '/tmp/nok.xml',
        'passed' => false,
        'validated_at' => now(),
    ]);

    expect(KositValidation::query()->where('passed', true)->count())->toBe(1)
        ->and(KositValidation::query()->where('passed', false)->count())->toBe(1);
});

it('validated_at date range filter narrows by validated_at', function (): void {
    KositValidation::query()->create([
        'input_path' => '/tmp/old.xml',
        'passed' => true,
        'validated_at' => '2024-01-15 10:00:00',
    ]);

    KositValidation::query()->create([
        'input_path' => '/tmp/new.xml',
        'passed' => true,
        'validated_at' => '2025-06-20 10:00:00',
    ]);

    $query = KositValidation::query()
        ->whereDate('validated_at', '>=', '2025-01-01')
        ->whereDate('validated_at', '<=', '2025-12-31');

    expect($query->count())->toBe(1);
});

it('counts helper returns error warning and info tallies', function (): void {
    $errors = [
        ['type' => 'error', 'text' => 'e1', 'location' => null, 'rule' => null],
        ['type' => 'warning', 'text' => 'w1', 'location' => null, 'rule' => null],
        ['type' => 'info', 'text' => 'i1', 'location' => null, 'rule' => null],
        ['type' => 'warning', 'text' => 'w2', 'location' => null, 'rule' => null],
    ];

    expect(KositValidationMessages::counts($errors))->toBe([
        'error' => 1,
        'warning' => 2,
        'info' => 1,
    ]);
});

it('renders validation messages and report partials for a record', function (): void {
    $validation = KositValidation::query()->create([
        'input_path' => '/tmp/invoice.xml',
        'passed' => false,
        'errors' => [
            ['type' => 'error', 'text' => 'Invalid total', 'location' => '/invoice', 'rule' => 'BR-01'],
        ],
        'validated_at' => now(),
    ]);

    $messagesHtml = view('kosit-validator::filament.partials.kosit-validation-messages', [
        'record' => $validation,
    ])->render();

    $reportHtml = view('kosit-validator::filament.partials.kosit-report-iframe', [
        'record' => $validation,
    ])->render();

    expect($messagesHtml)
        ->toContain('Invalid total')
        ->toContain('BR-01')
        ->and($reportHtml)->toContain('No KoSIT report available for this validation.');
});

it('is read-only for create and edit', function (): void {
    $validation = KositValidation::query()->make([
        'input_path' => '/tmp/invoice.xml',
        'passed' => true,
        'validated_at' => now(),
    ]);

    expect(KositValidationResource::canCreate())->toBeFalse()
        ->and(KositValidationResource::canEdit($validation))->toBeFalse()
        ->and(KositValidationResource::canDelete($validation))->toBeFalse();
});

it('uses the input xml basename as the record title', function (): void {
    $validation = KositValidation::query()->create([
        'input_path' => '/storage/invoices/Rechnung_99.xml',
        'passed' => true,
        'validated_at' => now(),
    ]);

    expect(KositValidationResource::getRecordTitle($validation))->toBe('Rechnung_99.xml');
});
