<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Enums\InvoiceOriginRule;
use Moox\EBilling\Models\EbillingDocument;
use Moox\Invoice\Support\InvoiceModels;
use Throwable;

/**
 * Human-readable detail for an ignored document, built from its `ignored_reason` payload.
 */
final class IgnoredReasonLabels
{
    public static function detail(EbillingDocument $document): ?string
    {
        $reason = is_array($document->ignored_reason) ? $document->ignored_reason : [];

        return match ($document->gateway_status) {
            EBillingAttachmentProcessingStatus::IgnoredForeign => self::foreignDetail($reason),
            EBillingAttachmentProcessingStatus::IgnoredBeforeCutoff => self::beforeCutoffDetail($reason),
            EBillingAttachmentProcessingStatus::IgnoredIdenticalDuplicate => self::identicalDuplicateDetail($document),
            default => null,
        };
    }

    /**
     * The invoice an identical-duplicate document matched, when it still exists. Memoized per document,
     * since the list table asks once for the detail text and once for its link.
     */
    public static function matchedInvoice(EbillingDocument $document): ?Model
    {
        if ($document->gateway_status !== EBillingAttachmentProcessingStatus::IgnoredIdenticalDuplicate) {
            return null;
        }

        $invoiceId = $document->ignored_reason['matched_invoice_id'] ?? null;

        if (! is_string($invoiceId) || $invoiceId === '') {
            return null;
        }

        return once(fn (): ?Model => InvoiceModels::invoice()::query()->find($invoiceId));
    }

    /**
     * @param  array<string, mixed>  $reason
     */
    private static function foreignDetail(array $reason): ?string
    {
        $country = $reason['country'] ?? null;

        if (is_string($country) && $country !== '') {
            return __('e-billing::fields.ignored_detail_country', ['country' => $country]);
        }

        if (($reason['matched_rule'] ?? null) === InvoiceOriginRule::NetOnly->value) {
            return __('e-billing::fields.ignored_detail_net_only');
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $reason
     */
    private static function beforeCutoffDetail(array $reason): ?string
    {
        $invoiceDate = self::formatDate($reason['invoice_date'] ?? null);
        $cutoff = self::formatDate($reason['invoice_date_from'] ?? null);

        if ($invoiceDate === null || $cutoff === null) {
            return null;
        }

        return __('e-billing::fields.ignored_detail_before_cutoff', [
            'invoice_date' => $invoiceDate,
            'cutoff' => $cutoff,
        ]);
    }

    private static function identicalDuplicateDetail(EbillingDocument $document): string
    {
        $number = self::matchedInvoice($document)?->getAttribute('invoice_number');

        if (! is_string($number) || $number === '') {
            return __('e-billing::fields.ignored_detail_identical_duplicate_unknown');
        }

        return __('e-billing::fields.ignored_detail_identical_duplicate', ['invoice' => $number]);
    }

    private static function formatDate(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('d.m.Y');
        } catch (Throwable) {
            return $value;
        }
    }
}
