<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use DateTimeImmutable;

/**
 * EN 16931 emission free-text labels (BT-160 names, CAE reason_text fallback, BT-22 / BT-127 note labels).
 * Uses e-billing.document_locale — not Filament / App UI locale.
 */
final class DocumentEmissionLabels
{
    /**
     * @var list<string>
     */
    private const LEGACY_MATERIAL_TEST_CERTIFICATE_ALIASES = [
        'Werkszeugnis',
        'Werksprüfzeugnis',
    ];

    private static function locale(): string
    {
        $locale = config('e-billing.document_locale', 'en');

        return is_string($locale) && $locale !== '' ? $locale : 'en';
    }

    public static function materialDesignation(): string
    {
        return self::label('bt160.material_designation');
    }

    public static function netWeight(): string
    {
        return self::label('bt160.net_weight');
    }

    public static function grossWeight(): string
    {
        return self::label('bt160.gross_weight');
    }

    public static function materialTestCertificate(): string
    {
        return self::label('bt160.material_test_certificate');
    }

    /**
     * Free-text note label: purchase_order, purchase_orders, order_date, despatch_advice, consignee.
     */
    public static function note(string $key): string
    {
        return self::label('notes.'.$key);
    }

    /**
     * A stored Y-m-d date as written in a customer-readable note (e.g. 01.09.2026 for de);
     * anything that is not a Y-m-d date is returned as given.
     */
    public static function date(string $value): string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));

        if ($date === false || $date->format('Y-m-d') !== trim($value)) {
            return $value;
        }

        return $date->format(self::label('date_format'));
    }

    /**
     * Invoice field label (e-billing::fields) in the document locale, for BT-22 note prefixes.
     */
    public static function field(string $field): string
    {
        return (string) trans('e-billing::fields.'.$field, [], self::locale());
    }

    public static function matchesMaterialTestCertificateLabel(string $label): bool
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $label) ?? '');
        if ($normalized === '') {
            return false;
        }

        foreach (self::aliasesForMaterialTestCertificate() as $alias) {
            if (strcasecmp($normalized, $alias) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Emission + inbound aliases for CAE reason_text / BT-160 certificate name.
     *
     * @return list<string>
     */
    private static function aliasesForMaterialTestCertificate(): array
    {
        return [
            self::label('bt160.material_test_certificate', 'en'),
            self::label('bt160.material_test_certificate', 'de'),
            ...self::LEGACY_MATERIAL_TEST_CERTIFICATE_ALIASES,
        ];
    }

    private static function label(string $key, ?string $locale = null): string
    {
        return (string) trans('e-billing::emission.'.$key, [], $locale ?? self::locale());
    }
}
