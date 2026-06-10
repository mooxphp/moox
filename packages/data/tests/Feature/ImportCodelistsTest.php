<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Moox\Data\Models\StaticAllowanceReason;
use Moox\Data\Models\StaticChargeReason;
use Moox\Data\Models\StaticDocumentType;
use Moox\Data\Models\StaticEasScheme;
use Moox\Data\Models\StaticIcdScheme;
use Moox\Data\Models\StaticIncoterm;
use Moox\Data\Models\StaticPaymentMean;
use Moox\Data\Models\StaticUnit;
use Moox\Data\Models\StaticVatCategory;
use Moox\Data\Models\StaticVatExemptionReason;
use Moox\Data\Services\ImportCodelistsService;
use Moox\Data\Tests\TestCase;

uses(TestCase::class);

test('moox:data:import-codelists populates all codelist tables from committed JSON', function (): void {
    $this->artisan('moox:data:import-codelists')
        ->assertSuccessful();

    expect(StaticChargeReason::query()->count())->toBe(9)
        ->and(StaticAllowanceReason::query()->count())->toBe(19)
        ->and(StaticDocumentType::query()->count())->toBe(54)
        ->and(StaticVatCategory::query()->count())->toBe(9)
        ->and(StaticPaymentMean::query()->count())->toBe(10)
        ->and(StaticUnit::query()->count())->toBe(15)
        ->and(StaticIncoterm::query()->count())->toBe(11)
        ->and(StaticVatExemptionReason::query()->count())->toBe(10)
        ->and(StaticIcdScheme::query()->count())->toBe(8)
        ->and(StaticEasScheme::query()->count())->toBe(10)
        ->and(StaticChargeReason::query()->where('code', 'FC')->value('common_name'))->toBe('Freight charge')
        ->and(StaticDocumentType::query()->where('code', '380')->value('en16931_interpretation'))->toBe('invoice')
        ->and(StaticDocumentType::query()->where('code', '381')->value('en16931_interpretation'))->toBe('credit_note')
        ->and(StaticUnit::query()->where('code', 'KGM')->value('symbol'))->toBe('kg')
        ->and(StaticIncoterm::query()->where('code', 'FOB')->where('version', '2020')->value('common_name'))->toBe('Free On Board');

    $chargeColumns = Schema::getColumnListing('static_charge_reasons');
    expect($chargeColumns)->toContain('id', 'code', 'common_name', 'description', 'created_at', 'updated_at')
        ->and($chargeColumns)->not->toContain('exonyms');

    expect(Schema::getColumnListing('static_allowance_reasons'))->toContain('description')
        ->and(Schema::getColumnListing('static_document_types'))->toContain('en16931_interpretation', 'description')
        ->and(Schema::getColumnListing('static_vat_categories'))->toContain('description')
        ->and(Schema::getColumnListing('static_payment_means'))->toContain('description')
        ->and(Schema::getColumnListing('static_units'))->toContain('symbol', 'description')
        ->and(Schema::getColumnListing('static_incoterms'))->toContain('version', 'description')
        ->and(Schema::getColumnListing('static_vat_exemption_reasons'))->toContain('vat_category_code', 'description')
        ->and(Schema::getColumnListing('static_icd_schemes'))->toContain('description')
        ->and(Schema::getColumnListing('static_eas_schemes'))->toContain('description');
});

test('codelist import is idempotent for each scheme', function (): void {
    $importer = app(ImportCodelistsService::class);

    foreach (['uncl7161', 'uncl5189', 'untdid1001', 'untdid5305', 'untdid4461', 'rec20', 'incoterms2020', 'vatex', 'icd', 'eas'] as $scheme) {
        $first = $importer->import($scheme);
        $second = $importer->import($scheme);

        expect($second)->toBe($first);
    }

    expect(StaticIncoterm::query()->count())->toBe(11);
});

test('static_charge_reasons code column is unique', function (): void {
    StaticChargeReason::query()->create([
        'code' => 'FC',
        'common_name' => 'Freight charge',
    ]);

    expect(fn () => StaticChargeReason::query()->create([
        'code' => 'FC',
        'common_name' => 'Duplicate',
    ]))->toThrow(QueryException::class);
});

test('static_incoterms uses composite unique on code and version', function (): void {
    StaticIncoterm::query()->create([
        'code' => 'FOB',
        'version' => '2020',
        'common_name' => 'Free On Board',
    ]);

    StaticIncoterm::query()->create([
        'code' => 'FOB',
        'version' => '2010',
        'common_name' => 'Free On Board (2010)',
    ]);

    expect(StaticIncoterm::query()->where('code', 'FOB')->count())->toBe(2);

    expect(fn () => StaticIncoterm::query()->create([
        'code' => 'FOB',
        'version' => '2020',
        'common_name' => 'Duplicate',
    ]))->toThrow(QueryException::class);
});

test('incoterms import upserts on code and version composite key', function (): void {
    $importer = app(ImportCodelistsService::class);

    $importer->import('incoterms2020');

    StaticIncoterm::query()->where('code', 'EXW')->where('version', '2020')->update(['common_name' => 'Changed']);

    $importer->import('incoterms2020');

    expect(StaticIncoterm::query()->where('code', 'EXW')->where('version', '2020')->value('common_name'))
        ->toBe('Ex Works')
        ->and(StaticIncoterm::query()->count())->toBe(11);
});

test('per-code lang files resolve under DE locale', function (): void {
    app()->setLocale('de');

    expect(__('data::enums/charge-reasons.FC'))->toBe('Frachtkosten')
        ->and(__('data::enums/allowance-reasons.95'))->toBe('Rabatt')
        ->and(__('data::enums/vat-categories.S'))->toBe('Normalsatz')
        ->and(__('data::enums/payment-means.58'))->toBe('SEPA-Überweisung')
        ->and(__('data::enums/units.KGM'))->toBe('Kilogramm')
        ->and(__('data::enums/incoterms.FOB'))->toBe('Frei an Bord')
        ->and(__('data::enums/document-types.380'))->toBe('Handelsrechnung')
        ->and(__('data::enums/vat-exemption-reasons.VATEX-EU-AE'))->toBe('Reverse Charge')
        ->and(__('data::enums/icd-schemes.9930'))->toBe('Deutsche Umsatzsteuer-Identifikationsnummer')
        ->and(__('data::enums/eas-schemes.0204'))->toBe('Leitweg-ID')
        ->and(__('data::fields.code'))->toBe('Code');
});

test('static_vat_exemption_reasons code column is unique', function (): void {
    StaticVatExemptionReason::query()->create([
        'code' => 'VATEX-EU-AE',
        'common_name' => 'Reverse charge',
    ]);

    expect(fn () => StaticVatExemptionReason::query()->create([
        'code' => 'VATEX-EU-AE',
        'common_name' => 'Duplicate',
    ]))->toThrow(QueryException::class);
});

test('vatex import maps description and vat_category_code and vatCategory resolves by code', function (): void {
    $this->artisan('moox:data:import-codelists', ['scheme' => 'untdid5305'])
        ->assertSuccessful();
    $this->artisan('moox:data:import-codelists', ['scheme' => 'vatex'])
        ->assertSuccessful();

    $reason = StaticVatExemptionReason::query()->where('code', 'VATEX-EU-AE')->first();

    expect($reason)->not->toBeNull()
        ->and($reason->vat_category_code)->toBe('AE')
        ->and($reason->description)->toBe('Only use with VAT category code AE (reverse charge).')
        ->and($reason->vatCategory)->not->toBeNull()
        ->and($reason->vatCategory->code)->toBe('AE');

    $franchise = StaticVatExemptionReason::query()->where('code', 'VATEX-FR-FRANCHISE')->first();

    expect($franchise)->not->toBeNull()
        ->and($franchise->vat_category_code)->toBeNull()
        ->and($franchise->vatCategory)->toBeNull();
});

test('icd import maps description when present in JSON', function (): void {
    $this->artisan('moox:data:import-codelists', ['scheme' => 'icd'])
        ->assertSuccessful();

    expect(StaticIcdScheme::query()->where('code', '0002')->value('description'))
        ->toContain('INSEE')
        ->and(StaticIcdScheme::query()->where('code', '0060')->value('description'))->toBeNull();
});

test('static_icd_schemes code column is unique', function (): void {
    StaticIcdScheme::query()->create([
        'code' => '9930',
        'common_name' => 'Germany VAT number',
    ]);

    expect(fn () => StaticIcdScheme::query()->create([
        'code' => '9930',
        'common_name' => 'Duplicate',
    ]))->toThrow(QueryException::class);
});

test('static_eas_schemes code column is unique', function (): void {
    StaticEasScheme::query()->create([
        'code' => '9930',
        'common_name' => 'Germany VAT number',
    ]);

    expect(fn () => StaticEasScheme::query()->create([
        'code' => '9930',
        'common_name' => 'Duplicate',
    ]))->toThrow(QueryException::class);
});
