<?php

declare(strict_types=1);

namespace Moox\Data\Support;

final class CodelistEnumTranslations
{
    /**
     * Scheme key => lang file path under data:: (without locale prefix).
     *
     * @var array<string, string>
     */
    public const SCHEME_LANG_FILES = [
        'uncl7161' => 'enums/charge-reasons',
        'uncl5189' => 'enums/allowance-reasons',
        'untdid1001' => 'enums/document-types',
        'untdid5305' => 'enums/vat-categories',
        'untdid4461' => 'enums/payment-means',
        'rec20' => 'enums/units',
        'incoterms2020' => 'enums/incoterms',
        'vatex' => 'enums/vat-exemption-reasons',
        'icd' => 'enums/icd-schemes',
        'eas' => 'enums/eas-schemes',
    ];

    /** @var list<string> */
    public const SHIPPED_LOCALES = ['en', 'de'];

    public static function labelFor(string $scheme, string $code, string $locale): ?string
    {
        $file = self::SCHEME_LANG_FILES[$scheme] ?? null;

        if ($file === null) {
            return null;
        }

        $key = "data::{$file}.{$code}";
        $label = __($key, [], $locale);

        return $label !== $key ? $label : null;
    }

    /**
     * @return array<string, string>
     */
    public static function labelsForScheme(string $scheme, string $locale): array
    {
        $file = self::SCHEME_LANG_FILES[$scheme] ?? null;

        if ($file === null) {
            return [];
        }

        /** @var array<string, string>|mixed $labels */
        $labels = trans("data::{$file}", [], $locale);

        return is_array($labels) ? $labels : [];
    }
}
