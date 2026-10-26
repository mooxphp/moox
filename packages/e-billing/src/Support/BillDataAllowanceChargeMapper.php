<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\Zugferd\Contracts\ZugferdAllowanceCharge;
use Moox\Zugferd\Data\AllowanceCharge;

/**
 * Maps legacy bill_data scalar fields to allowance/charge objects.
 */
final class BillDataAllowanceChargeMapper
{
    /**
     * Document charge fields that may be itemized: default reason text and UNCL 7161 reason code,
     * as {@see fromHeaderScalars()} emits them.
     *
     * @var array<string, array{0: string, 1: ?string}>
     */
    private const DOCUMENT_CHARGE_REASONS = [
        'shipping_cost' => ['Versand', null],
        'packaging_cost' => ['Verpackung', null],
        'minimum_quantity_surcharge' => ['Mindermengenzuschlag', null],
        'freight_flat_rate' => ['Frachtkostenpauschale', null],
        'certificate_cost' => ['Attestkosten', 'CAE'],
        'customs_cost' => ['Zollkosten', null],
    ];

    /**
     * Keeps rows of itemizable charge fields with a non-zero amount, signed as printed.
     *
     * @return list<array{field: string, amount: float, reason: ?string}>
     */
    public static function documentChargeRows(array $rows): array
    {
        $valid = [];

        foreach ($rows as $row) {
            if (! is_array($row)
                || ! is_string($row['field'] ?? null)
                || ! isset(self::DOCUMENT_CHARGE_REASONS[$row['field']])
                || ! is_numeric($row['amount'] ?? null)
                || (float) $row['amount'] === 0.0) {
                continue;
            }

            $reason = is_string($row['reason'] ?? null) && trim($row['reason']) !== '' ? trim($row['reason']) : null;
            $valid[] = ['field' => $row['field'], 'amount' => (float) $row['amount'], 'reason' => $reason];
        }

        return $valid;
    }

    /**
     * @param  list<array{field: string, amount: float, reason: ?string}>  $rows
     * @return list<ZugferdAllowanceCharge>
     */
    public static function fromDocumentChargeRows(array $rows): array
    {
        return array_map(static function (array $row): AllowanceCharge {
            [$defaultText, $reasonCode] = self::DOCUMENT_CHARGE_REASONS[$row['field']];

            return new AllowanceCharge(
                isCharge: true,
                amount: $row['amount'],
                reasonCode: $reasonCode,
                reasonText: $row['reason'] ?? $defaultText,
            );
        }, self::documentChargeRows($rows));
    }

    /**
     * @return list<ZugferdAllowanceCharge>
     */
    public static function fromHeaderScalars(
        ?float $shippingCost,
        ?float $packagingCost,
        ?float $minimumQuantitySurcharge,
        ?float $freightFlatRate,
        ?float $discountAmount,
        ?float $discountPercent,
        ?float $certificateCost = null,
        ?float $customsCost = null,
        ?string $discountReason = null,
    ): array {
        $items = [];

        if (self::isNonZero($shippingCost)) {
            $items[] = new AllowanceCharge(isCharge: true, amount: $shippingCost, reasonText: 'Versand');
        }

        if (self::isNonZero($packagingCost)) {
            $items[] = new AllowanceCharge(isCharge: true, amount: $packagingCost, reasonText: 'Verpackung');
        }

        if (self::isNonZero($minimumQuantitySurcharge)) {
            $items[] = new AllowanceCharge(isCharge: true, amount: $minimumQuantitySurcharge, reasonText: 'Mindermengenzuschlag');
        }

        if (self::isNonZero($freightFlatRate)) {
            $items[] = new AllowanceCharge(isCharge: true, amount: $freightFlatRate, reasonText: 'Frachtkostenpauschale');
        }

        // CAE "Certificate of conformance" as for line certificates (ADR 0012), here at document level.
        if (self::isNonZero($certificateCost)) {
            $items[] = new AllowanceCharge(isCharge: true, amount: $certificateCost, reasonCode: 'CAE', reasonText: 'Attestkosten');
        }

        // No reason code: UNCL 7161 "ABW Customs duty charge" is outside the EN 16931 subset (BR-CL-20).
        if (self::isNonZero($customsCost)) {
            $items[] = new AllowanceCharge(isCharge: true, amount: $customsCost, reasonText: 'Zollkosten');
        }

        if (self::isNonZero($discountAmount)) {
            $printedReason = trim((string) $discountReason);
            $reasonText = $printedReason !== '' ? $printedReason : ($discountPercent
                ? self::percentAsPrinted($discountPercent).' % vom Warenwert'
                : 'Rabatt');

            $items[] = new AllowanceCharge(
                isCharge: false,
                amount: $discountAmount,
                reasonCode: '95',
                reasonText: $reasonText,
                percentage: $discountPercent,
            );
        }

        return $items;
    }

    /**
     * @return list<ZugferdAllowanceCharge>
     */
    public static function fromLineScalars(
        ?float $surchargeAmount,
        ?string $surchargeDescription,
        ?float $materialTestCertificatePrice,
        ?string $materialTestCertificate,
    ): array {
        $items = [];

        if (self::isNonZero($surchargeAmount)) {
            $items[] = new AllowanceCharge(
                isCharge: true,
                amount: $surchargeAmount,
                reasonText: $surchargeDescription ?? 'Legierungszuschlag',
            );
        }

        if (self::isNonZero($materialTestCertificatePrice)) {
            $items[] = new AllowanceCharge(
                isCharge: true,
                amount: $materialTestCertificatePrice,
                reasonCode: 'CAE',
                reasonText: $materialTestCertificate ?? DocumentEmissionLabels::materialTestCertificate(),
            );
        }

        return $items;
    }

    /**
     * Every non-zero amount is emitted with its sign: a corrected invoice (384) as a delta carries negative
     * charges and allowances, a credit note (381) positive ones (FeRD ZUGFeRD 2.5.2 ch. 7.1.6 / 7.1.11).
     */
    private static function isNonZero(?float $amount): bool
    {
        return $amount !== null && $amount !== 0.0;
    }

    /** A percentage as printed: decimal comma, only the decimals needed (25 → 25, 3.5 → 3,5). */
    private static function percentAsPrinted(float $percent): string
    {
        return rtrim(rtrim(number_format($percent, 2, ',', ''), '0'), ',');
    }
}
