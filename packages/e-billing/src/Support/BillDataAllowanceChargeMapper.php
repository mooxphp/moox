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
     * Keeps rows of itemizable charge fields with a positive amount.
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
                || (float) $row['amount'] <= 0) {
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
    ): array {
        $items = [];

        if ($shippingCost !== null && $shippingCost > 0) {
            $items[] = new AllowanceCharge(isCharge: true, amount: $shippingCost, reasonText: 'Versand');
        }

        if ($packagingCost !== null && $packagingCost > 0) {
            $items[] = new AllowanceCharge(isCharge: true, amount: $packagingCost, reasonText: 'Verpackung');
        }

        if ($minimumQuantitySurcharge !== null && $minimumQuantitySurcharge > 0) {
            $items[] = new AllowanceCharge(isCharge: true, amount: $minimumQuantitySurcharge, reasonText: 'Mindermengenzuschlag');
        }

        if ($freightFlatRate !== null && $freightFlatRate > 0) {
            $items[] = new AllowanceCharge(isCharge: true, amount: $freightFlatRate, reasonText: 'Frachtkostenpauschale');
        }

        // CAE "Certificate of conformance" as for line certificates (ADR 0012), here at document level.
        if ($certificateCost !== null && $certificateCost > 0) {
            $items[] = new AllowanceCharge(isCharge: true, amount: $certificateCost, reasonCode: 'CAE', reasonText: 'Attestkosten');
        }

        // No reason code: UNCL 7161 "ABW Customs duty charge" is outside the EN 16931 subset (BR-CL-20).
        if ($customsCost !== null && $customsCost > 0) {
            $items[] = new AllowanceCharge(isCharge: true, amount: $customsCost, reasonText: 'Zollkosten');
        }

        if ($discountAmount !== null && $discountAmount > 0) {
            $reasonText = $discountPercent
                ? sprintf('%.0f %% vom Warenwert', $discountPercent)
                : 'Rabatt';

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

        if ($surchargeAmount !== null && $surchargeAmount > 0) {
            $items[] = new AllowanceCharge(
                isCharge: true,
                amount: $surchargeAmount,
                reasonText: $surchargeDescription ?? 'Legierungszuschlag',
            );
        }

        if ($materialTestCertificatePrice !== null && $materialTestCertificatePrice > 0) {
            $items[] = new AllowanceCharge(
                isCharge: true,
                amount: $materialTestCertificatePrice,
                reasonCode: 'CAE',
                reasonText: $materialTestCertificate ?? DocumentEmissionLabels::materialTestCertificate(),
            );
        }

        return $items;
    }
}
