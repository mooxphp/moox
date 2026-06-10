<?php

declare(strict_types=1);

namespace Moox\DataLegacy\Support;

use Moox\DataLegacy\Models\StaticAllowanceReason;
use Moox\DataLegacy\Models\StaticChargeReason;
use Moox\DataLegacy\Models\StaticDocumentType;
use Moox\DataLegacy\Models\StaticEasScheme;
use Moox\DataLegacy\Models\StaticIcdScheme;
use Moox\DataLegacy\Models\StaticIncoterm;
use Moox\DataLegacy\Models\StaticPaymentMean;
use Moox\DataLegacy\Models\StaticUnit;
use Moox\DataLegacy\Models\StaticVatCategory;
use Moox\DataLegacy\Models\StaticVatExemptionReason;

class CodelistRegistry
{
    /**
     * Scheme key => codelist definition (committed JSON file + Eloquent model).
     *
     * @var array<string, array{file: string, model: class-string, upsert_keys?: list<string>}>
     */
    public const ENTRIES = [
        'uncl7161' => [
            'file' => 'uncl7161.json',
            'model' => StaticChargeReason::class,
        ],
        'uncl5189' => [
            'file' => 'allowance-reasons.json',
            'model' => StaticAllowanceReason::class,
        ],
        'untdid1001' => [
            'file' => 'document-types.json',
            'model' => StaticDocumentType::class,
        ],
        'untdid5305' => [
            'file' => 'vat-categories.json',
            'model' => StaticVatCategory::class,
        ],
        'untdid4461' => [
            'file' => 'payment-means.json',
            'model' => StaticPaymentMean::class,
        ],
        'rec20' => [
            'file' => 'units.json',
            'model' => StaticUnit::class,
        ],
        'incoterms2020' => [
            'file' => 'incoterms.json',
            'model' => StaticIncoterm::class,
            'upsert_keys' => ['code', 'version'],
        ],
        'vatex' => [
            'file' => 'vat-exemption-reasons.json',
            'model' => StaticVatExemptionReason::class,
        ],
        'icd' => [
            'file' => 'icd-schemes.json',
            'model' => StaticIcdScheme::class,
        ],
        'eas' => [
            'file' => 'eas-schemes.json',
            'model' => StaticEasScheme::class,
        ],
    ];

    /**
     * @return array<string, array{file: string, model: class-string, upsert_keys?: list<string>}>
     */
    public static function all(): array
    {
        return self::ENTRIES;
    }

    /**
     * @return array{file: string, model: class-string, upsert_keys?: list<string>}|null
     */
    public static function get(string $scheme): ?array
    {
        return self::ENTRIES[$scheme] ?? null;
    }
}
