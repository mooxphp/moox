<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\Data\Models\StaticAllowanceReason;
use Moox\Data\Models\StaticChargeReason;

/**
 * The reason codes a reviewer may choose for an allowance or charge (ADR 0014): UNCL 7161 for a charge,
 * UNCL 5189 for an allowance, from the imported moox/data codelists.
 */
final class ReasonCodeOptions
{
    public const CHARGE_INPUT = 'charge_reason_code';

    public const ALLOWANCE_INPUT = 'allowance_reason_code';

    public static function inputType(bool $isCharge): string
    {
        return $isCharge ? self::CHARGE_INPUT : self::ALLOWANCE_INPUT;
    }

    /**
     * @return array<string, string> code => "code – name"
     */
    public static function forInputType(string $inputType): array
    {
        $model = $inputType === self::ALLOWANCE_INPUT ? StaticAllowanceReason::class : StaticChargeReason::class;
        $locale = app()->getLocale();
        $options = [];

        foreach ($model::query()->with('translations')->orderBy('code')->get() as $record) {
            $name = $record->translatedLabel($locale) ?? $record->translatedLabel('en');
            $options[(string) $record->code] = $name !== null ? "{$record->code} – {$name}" : (string) $record->code;
        }

        return $options;
    }
}
