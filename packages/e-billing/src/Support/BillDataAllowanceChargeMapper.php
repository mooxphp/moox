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
     * Document charge fields with their default reason text, as {@see fromHeaderScalars()} emits them;
     * the reason code comes from {@see AllowanceChargeReasonCodes}.
     *
     * @var array<string, string>
     */
    private const DOCUMENT_CHARGE_REASONS = [
        'shipping_cost' => 'Versand',
        'packaging_cost' => 'Verpackung',
        'minimum_quantity_surcharge' => 'Mindermengenzuschlag',
        'freight_flat_rate' => 'Frachtkostenpauschale',
        'certificate_cost' => 'Attestkosten',
        'customs_cost' => 'Zollkosten',
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
        return array_map(
            static fn (array $row): AllowanceCharge => self::documentCharge(
                $row['field'],
                $row['amount'],
                $row['reason'],
            ),
            self::documentChargeRows($rows),
        );
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
        $amounts = [
            'shipping_cost' => $shippingCost,
            'packaging_cost' => $packagingCost,
            'minimum_quantity_surcharge' => $minimumQuantitySurcharge,
            'freight_flat_rate' => $freightFlatRate,
            'certificate_cost' => $certificateCost,
            'customs_cost' => $customsCost,
        ];

        $items = [];

        foreach ($amounts as $field => $amount) {
            if (self::isNonZero($amount)) {
                $items[] = self::documentCharge($field, $amount, null);
            }
        }

        if (self::isNonZero($discountAmount)) {
            $printedReason = trim((string) $discountReason);
            $reasonText = $printedReason !== '' ? $printedReason : ($discountPercent
                ? self::percentAsPrinted($discountPercent).' % vom Warenwert'
                : 'Rabatt');

            $items[] = new AllowanceCharge(
                isCharge: false,
                amount: $discountAmount,
                reasonCode: AllowanceChargeReasonCodes::header(AllowanceChargeReasonCodes::DISCOUNT_FIELD),
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
                reasonCode: AllowanceChargeReasonCodes::line('surcharge_amount'),
                reasonText: $surchargeDescription ?? 'Legierungszuschlag',
            );
        }

        if (self::isNonZero($materialTestCertificatePrice)) {
            $items[] = new AllowanceCharge(
                isCharge: true,
                amount: $materialTestCertificatePrice,
                reasonCode: AllowanceChargeReasonCodes::line('material_test_certificate_price'),
                reasonText: $materialTestCertificate ?? DocumentEmissionLabels::materialTestCertificate(),
            );
        }

        return $items;
    }

    private static function documentCharge(string $field, float $amount, ?string $reason): AllowanceCharge
    {
        return new AllowanceCharge(
            isCharge: true,
            amount: $amount,
            reasonCode: AllowanceChargeReasonCodes::header($field),
            reasonText: $reason ?? self::DOCUMENT_CHARGE_REASONS[$field],
        );
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
