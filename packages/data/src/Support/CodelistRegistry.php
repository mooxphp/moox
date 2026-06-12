<?php

declare(strict_types=1);

namespace Moox\Data\Support;

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
