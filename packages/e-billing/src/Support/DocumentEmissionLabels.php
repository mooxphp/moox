<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

/**
 * EN 16931 emission free-text labels (BT-160 names, CAE reason_text fallback).
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
