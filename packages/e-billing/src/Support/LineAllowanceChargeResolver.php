<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\Invoice\Models\InvoiceAllowanceCharge;
use Moox\Invoice\Models\InvoiceLine;

final class LineAllowanceChargeResolver
{
    public static function resolveSurchargeAmount(iterable $allowanceCharges): ?float
    {
        $charge = self::findSurchargeCharge($allowanceCharges);

        if ($charge === null || $charge->amount === null || $charge->amount === '') {
            return null;
        }

        return (float) $charge->amount;
    }

    public static function resolveSurchargeDescription(iterable $allowanceCharges): ?string
    {
        $charge = self::findSurchargeCharge($allowanceCharges);

        if ($charge === null) {
            return null;
        }

        $text = trim((string) ($charge->reason_text ?? ''));

        return $text === '' ? null : $text;
    }

    public static function resolveMaterialTestCertificatePrice(iterable $allowanceCharges, ?InvoiceLine $line = null): ?float
    {
        $charge = self::findMaterialTestCertificateCharge($allowanceCharges, $line);

        if ($charge === null || $charge->amount === null || $charge->amount === '') {
            return null;
        }

        return (float) $charge->amount;
    }

    public static function hasSurchargeCharge(iterable $allowanceCharges): bool
    {
        return self::findSurchargeCharge($allowanceCharges) !== null;
    }

    public static function hasMaterialTestCertificateCharge(iterable $allowanceCharges, ?InvoiceLine $line = null): bool
    {
        return self::findMaterialTestCertificateCharge($allowanceCharges, $line) !== null;
    }

    public static function findSurchargeCharge(iterable $allowanceCharges): ?InvoiceAllowanceCharge
    {
        foreach ($allowanceCharges as $charge) {
            if (! $charge instanceof InvoiceAllowanceCharge || ! $charge->is_charge) {
                continue;
            }

            if (self::isMaterialTestCertificateCharge($charge)) {
                continue;
            }

            return $charge;
        }

        return null;
    }

    public static function findMaterialTestCertificateCharge(
        iterable $allowanceCharges,
        ?InvoiceLine $line = null,
    ): ?InvoiceAllowanceCharge {
        foreach ($allowanceCharges as $charge) {
            if ($charge instanceof InvoiceAllowanceCharge && self::isMaterialTestCertificateCharge($charge, $line)) {
                return $charge;
            }
        }

        return null;
    }

    public static function isMaterialTestCertificateCharge(
        InvoiceAllowanceCharge $charge,
        ?InvoiceLine $line = null,
    ): bool {
        $label = self::normalizeString($charge->reason_text);
        $lineModel = $line ?? ($charge->chargeable instanceof InvoiceLine ? $charge->chargeable : null);
        $certificate = self::normalizeString($lineModel !== null
            ? (string) ($lineModel->material_test_certificate ?? '')
            : '');

        if ($certificate !== '' && strcasecmp($label, $certificate) === 0) {
            return true;
        }

        return strcasecmp($label, 'Materialprüfzeugnis') === 0;
    }

    private static function normalizeString(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }
}
