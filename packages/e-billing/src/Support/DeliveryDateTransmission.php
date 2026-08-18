<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use DateTimeInterface;
use Moox\Invoice\Models\Invoice;
use Moox\Zugferd\Support\FlexibleDateParser;

final class DeliveryDateTransmission
{
    /**
     * @param  iterable<mixed>  $lines
     * @return list<string>
     */
    public static function distinctDates(?string $documentDate, iterable $lines): array
    {
        $dates = [];

        $normalizedDocument = self::normalize($documentDate);
        if ($normalizedDocument !== null) {
            $dates[$normalizedDocument] = $normalizedDocument;
        }

        foreach ($lines as $line) {
            $normalized = self::normalize(self::lineDate($line));
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
            self::scalarDate($invoice->delivery_date),
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
        $parsed = FlexibleDateParser::parse($value);

        return $parsed?->format('Y-m-d');
    }

    private static function lineDate(mixed $line): ?string
    {
        if (! is_object($line) || ! isset($line->delivery_date)) {
            return null;
        }

        return self::scalarDate($line->delivery_date);
    }

    private static function scalarDate(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        return null;
    }
}
