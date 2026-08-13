<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use DateTimeInterface;
use Moox\Invoice\Models\Invoice;
use Moox\Invoice\Models\InvoiceLine;

final class DeliveryDateTransmission
{
    /**
     * @var list<string>
     */
    private const EU_VAT_COUNTRY_PREFIXES = [
        'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'EL', 'ES', 'FI', 'FR',
        'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO',
        'SE', 'SI', 'SK', 'XI',
    ];

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
        $sellerPrefix = self::vatCountryPrefix($invoice->seller?->vat_id);
        $buyerPrefix = self::vatCountryPrefix($invoice->buyer?->vat_id);

        if ($sellerPrefix === null || $buyerPrefix === null) {
            return false;
        }

        return $sellerPrefix !== $buyerPrefix
            && self::isEuVatPrefix($sellerPrefix)
            && self::isEuVatPrefix($buyerPrefix);
    }

    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        foreach (['Y-m-d', 'd.m.Y', 'd.m.y'] as $format) {
            $parsed = \DateTimeImmutable::createFromFormat('!'.$format, $trimmed);
            if ($parsed instanceof \DateTimeImmutable) {
                return $parsed->format('Y-m-d');
            }
        }

        return null;
    }

    private static function lineDate(mixed $line): ?string
    {
        if (! is_object($line)) {
            return null;
        }

        if ($line instanceof InvoiceLine) {
            return self::scalarDate($line->delivery_date);
        }

        if (! isset($line->delivery_date)) {
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

    private static function vatCountryPrefix(?string $vatId): ?string
    {
        $normalized = VatIdNormalizer::normalize($vatId);
        if ($normalized === null || strlen($normalized) < 2) {
            return null;
        }

        $prefix = strtoupper(substr($normalized, 0, 2));

        return preg_match('/^[A-Z]{2}$/', $prefix) === 1 ? $prefix : null;
    }

    private static function isEuVatPrefix(string $prefix): bool
    {
        return in_array($prefix, self::EU_VAT_COUNTRY_PREFIXES, true);
    }
}
