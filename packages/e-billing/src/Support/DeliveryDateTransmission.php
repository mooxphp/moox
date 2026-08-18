<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\Invoice\Models\Invoice;

final class DeliveryDateTransmission
{
    /**
     * @param  iterable<mixed>  $lines
     * @return list<string>
     */
    public static function distinctDates(?string $documentDate, iterable $lines): array
    {
        $dates = [];

        $normalizedDocument = DeliveryDateNormalizer::normalize($documentDate);
        if ($normalizedDocument !== null) {
            $dates[$normalizedDocument] = $normalizedDocument;
        }

        foreach ($lines as $line) {
            $normalized = DeliveryDateNormalizer::normalize(DeliveryDateNormalizer::fromLine($line));
            if ($normalized !== null) {
                $dates[$normalized] = $normalized;
            }
        }

        return array_values($dates);
    }

    /**
     * @param  iterable<mixed>  $lines
     */
    public static function documentActualDeliveryDate(?string $documentDate, iterable $lines): ?string
    {
        $dates = self::distinctDates($documentDate, $lines);

        return count($dates) === 1 ? $dates[0] : null;
    }

    /**
     * @param  iterable<mixed>  $lines
     */
    public static function shouldEmitLineDeliveryDate(?string $documentDate, iterable $lines): bool
    {
        return count(self::distinctDates($documentDate, $lines)) > 1;
    }

    public static function hasSeveralDifferingDates(Invoice $invoice): bool
    {
        $invoice->loadMissing('lines');

        return self::shouldEmitLineDeliveryDate(
            DeliveryDateNormalizer::fromScalar($invoice->delivery_date),
            $invoice->lines,
        );
    }

    public static function isIntraCommunitySupply(Invoice $invoice): bool
    {
        $sellerPrefix = VatIdNormalizer::euCountryPrefix($invoice->seller?->vat_id);
        $buyerPrefix = VatIdNormalizer::euCountryPrefix($invoice->buyer?->vat_id);

        if ($sellerPrefix === null || $buyerPrefix === null) {
            return false;
        }

        return $sellerPrefix !== $buyerPrefix
            && VatIdNormalizer::isEuCountryPrefix($sellerPrefix)
            && VatIdNormalizer::isEuCountryPrefix($buyerPrefix);
    }

    public static function normalize(?string $value): ?string
    {
        return DeliveryDateNormalizer::normalize($value);
    }
}
