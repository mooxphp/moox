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
     * @return list<ZugferdAllowanceCharge>
     */
    public static function fromHeaderScalars(
        ?float $shippingCost,
        ?float $packagingCost,
        ?float $minimumQuantitySurcharge,
        ?float $freightFlatRate,
        ?float $discountAmount,
        ?float $discountPercent,
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
                reasonText: $materialTestCertificate ?? 'Materialprüfzeugnis',
            );
        }

        return $items;
    }
}
