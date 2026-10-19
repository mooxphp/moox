<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\EBilling\Data\Invoice as InvoiceDto;
use Moox\EBilling\Data\InvoiceLine as InvoiceLineDto;
use Moox\Invoice\Models\Invoice;
use Moox\Invoice\Models\InvoiceLine;
use Moox\Invoice\Support\InvoiceNotes;
use Moox\Zugferd\Contracts\ZugferdInvoice;

/**
 * BT-22 notes. Labels use e-billing.document_locale: the notes are read by the buyer, not the reviewer.
 * A parsed note with a BT-21 subject code is handed on as {text, subject_code}; every other note is a string.
 *
 * @phpstan-import-type InvoiceNote from InvoiceNotes
 * @phpstan-import-type DocumentNote from ZugferdInvoice
 */
final class InvoiceDocumentNotes
{
    /**
     * @return list<DocumentNote>
     */
    public static function fromInvoice(Invoice $invoice): array
    {
        $orderDate = $invoice->order_date !== null ? trim((string) $invoice->order_date) : '';

        return self::collect(
            $invoice->delivery_terms !== null ? (string) $invoice->delivery_terms : null,
            $invoice->shipping_method !== null ? (string) $invoice->shipping_method : null,
            $invoice->notes ?? [],
            $orderDate !== '' ? $orderDate : null,
            self::lineOrdersWithoutHeaderOrder(
                $invoice->order_number !== null ? (string) $invoice->order_number : null,
                $invoice->lines->map(fn (InvoiceLine $line): array => [
                    $line->order_number !== null ? (string) $line->order_number : null,
                    $line->order_date !== null ? (string) $line->order_date : null,
                ])->all(),
            ),
        );
    }

    /**
     * @return list<DocumentNote>
     */
    public static function fromDto(InvoiceDto $invoice): array
    {
        $orderDate = $invoice->orderDate !== null ? trim($invoice->orderDate) : '';

        return self::collect(
            $invoice->deliveryTerms,
            $invoice->shippingMethod,
            $invoice->notes,
            $orderDate !== '' ? $orderDate : null,
            self::lineOrdersWithoutHeaderOrder(
                $invoice->orderNumber,
                array_map(
                    static fn (InvoiceLineDto $line): array => [$line->orderNumber, $line->orderDate],
                    $invoice->lines,
                ),
            ),
        );
    }

    /**
     * @param  list<string|InvoiceNote>  $parsedNotes
     * @param  list<string>  $lineOrders
     * @return list<array{field: string, text: string, subject_code?: ?string}>
     */
    public static function entries(
        ?string $deliveryTerms,
        ?string $shippingMethod,
        array $parsedNotes = [],
        ?string $orderDate = null,
        array $lineOrders = [],
    ): array {
        $notes = [];

        if ($deliveryTerms !== null && trim($deliveryTerms) !== '') {
            $notes[] = ['field' => 'delivery_terms', 'text' => trim($deliveryTerms)];
        }

        if ($shippingMethod !== null && trim($shippingMethod) !== '') {
            $notes[] = ['field' => 'shipping_method', 'text' => trim($shippingMethod)];
        }

        if ($orderDate !== null && trim($orderDate) !== '') {
            $notes[] = ['field' => 'order_date', 'text' => DocumentEmissionLabels::date(trim($orderDate))];
        }

        if ($lineOrders !== []) {
            $notes[] = ['field' => 'purchase_orders', 'text' => implode(', ', $lineOrders)];
        }

        foreach (InvoiceNotes::normalize($parsedNotes) as $note) {
            $notes[] = ['field' => 'notes', 'text' => $note['text'], 'subject_code' => $note['subject_code']];
        }

        return $notes;
    }

    /**
     * Build BT-22 note texts from invoice fields (delivery terms, shipping method, order date,
     * the orders of a multi-order invoice, parser notes).
     *
     * @param  list<string|InvoiceNote>  $parsedNotes
     * @param  list<string>  $lineOrders
     * @return list<DocumentNote>
     */
    public static function collect(
        ?string $deliveryTerms,
        ?string $shippingMethod,
        array $parsedNotes = [],
        ?string $orderDate = null,
        array $lineOrders = [],
    ): array {
        return array_map(
            fn (array $entry): string|array => match ($entry['field']) {
                'notes' => isset($entry['subject_code'])
                    ? ['text' => $entry['text'], 'subject_code' => $entry['subject_code']]
                    : $entry['text'],
                'purchase_orders' => DocumentEmissionLabels::note('purchase_orders').': '.$entry['text'],
                default => DocumentEmissionLabels::field($entry['field']).': '.$entry['text'],
            },
            self::entries($deliveryTerms, $shippingMethod, $parsedNotes, $orderDate, $lineOrders),
        );
    }

    /**
     * Distinct "number (date)" entries of the lines, in line order — only when the header names no order:
     * BT-13 holds one order, so an invoice billing several lists them here (line orders ride in BT-127).
     *
     * @param  list<array{0: ?string, 1: ?string}>  $lineOrders
     * @return list<string>
     */
    private static function lineOrdersWithoutHeaderOrder(?string $headerOrderNumber, array $lineOrders): array
    {
        if ($headerOrderNumber !== null && trim($headerOrderNumber) !== '') {
            return [];
        }

        $entries = [];

        foreach ($lineOrders as [$number, $date]) {
            $number = $number !== null ? trim($number) : '';
            $date = $date !== null ? trim($date) : '';

            if ($number === '' && $date === '') {
                continue;
            }

            $entries[] = trim($number.($date !== '' ? ' ('.DocumentEmissionLabels::date($date).')' : ''));
        }

        return array_values(array_unique($entries));
    }
}
