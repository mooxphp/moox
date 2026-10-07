<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use DateTimeImmutable;
use InvalidArgumentException;
use Moox\EBilling\Enums\ForeignDisposition;

/**
 * Intake cutoff (`e-billing.intake.invoice_date_from`): mail-sourced documents whose parsed
 * invoice date (BT-2) lies before this day are not converted. The cutoff day itself passes;
 * a missing or unparseable invoice date never counts as before the cutoff.
 */
final class IntakeCutoff
{
    private const CONFIG_KEY = 'e-billing.intake.invoice_date_from';

    private function __construct(
        public readonly string $invoiceDateFrom,
    ) {
    }

    /**
     * @throws InvalidArgumentException when the configured value is not a valid Y-m-d date
     */
    public static function fromConfig(): ?self
    {
        $raw = config(self::CONFIG_KEY);

        if ($raw === null || (is_string($raw) && trim($raw) === '')) {
            return null;
        }

        if (! is_string($raw) || ! self::isIsoDate(trim($raw))) {
            throw new InvalidArgumentException(
                self::CONFIG_KEY.' must be a valid Y-m-d date or empty, got ['.(is_scalar($raw) ? $raw : get_debug_type($raw)).'].'
            );
        }

        return new self(trim($raw));
    }

    /**
     * What happens to a document before the cutoff (`e-billing.intake.before_cutoff_disposition`,
     * default ignore): settle only, or relay the source PDF first.
     */
    public function disposition(): ForeignDisposition
    {
        $raw = config('e-billing.intake.before_cutoff_disposition', ForeignDisposition::Ignore->value);

        return ForeignDisposition::fromConfig(is_string($raw) ? $raw : ForeignDisposition::Ignore->value);
    }

    /**
     * The ignored reason when the document is dated before the cutoff, otherwise null.
     *
     * @param  array<string, mixed>  $billData
     * @return array{invoice_date: string, invoice_date_from: string}|null
     */
    public function ignoredReason(array $billData): ?array
    {
        $invoiceDate = $billData['invoice_date'] ?? null;
        $invoiceDate = is_string($invoiceDate) ? trim($invoiceDate) : '';

        if (! self::isIsoDate($invoiceDate) || $invoiceDate >= $this->invoiceDateFrom) {
            return null;
        }

        return [
            'invoice_date' => $invoiceDate,
            'invoice_date_from' => $this->invoiceDateFrom,
        ];
    }

    private static function isIsoDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
