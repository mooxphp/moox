<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\EBilling\Data\Invoice as InvoiceDto;
use Moox\Invoice\Models\Invoice;

final class InvoiceDocumentNotes
{
    /**
     * @return list<string>
     */
    public static function fromInvoice(Invoice $invoice): array
    {
        $parsedNotes = $invoice->notes ?? [];
        $orderDate = $invoice->order_date !== null ? trim((string) $invoice->order_date) : '';

        return self::collect(
            $invoice->delivery_terms !== null ? (string) $invoice->delivery_terms : null,
            $invoice->shipping_method !== null ? (string) $invoice->shipping_method : null,
            is_array($parsedNotes) ? $parsedNotes : [],
            $orderDate !== '' ? $orderDate : null,
        );
    }

    /**
     * @return list<string>
     */
    public static function fromDto(InvoiceDto $invoice): array
    {
        $orderDate = $invoice->orderDate !== null ? trim($invoice->orderDate) : '';

        return self::collect(
            $invoice->deliveryTerms,
            $invoice->shippingMethod,
            $invoice->notes,
            $orderDate !== '' ? $orderDate : null,
        );
    }

    /**
     * @param  list<string>  $parsedNotes
     * @return list<array{field: string, text: string}>
     */
    public static function entries(
        ?string $deliveryTerms,
        ?string $shippingMethod,
        array $parsedNotes = [],
        ?string $orderDate = null,
    ): array {
        $notes = [];

        if ($deliveryTerms !== null && trim($deliveryTerms) !== '') {
            $notes[] = ['field' => 'delivery_terms', 'text' => trim($deliveryTerms)];
        }

        if ($shippingMethod !== null && trim($shippingMethod) !== '') {
            $notes[] = ['field' => 'shipping_method', 'text' => trim($shippingMethod)];
        }

        if ($orderDate !== null && trim($orderDate) !== '') {
            $notes[] = ['field' => 'order_date', 'text' => trim($orderDate)];
        }

        foreach ($parsedNotes as $note) {
            if (is_string($note) && trim($note) !== '') {
                $notes[] = ['field' => 'notes', 'text' => trim($note)];
            }
        }

        return $notes;
    }

    /**
     * Build BT-22 note texts from invoice fields (delivery terms, shipping method, order date, parser notes).
     *
     * @param  list<string>  $parsedNotes
     * @return list<string>
     */
    public static function collect(
        ?string $deliveryTerms,
        ?string $shippingMethod,
        array $parsedNotes = [],
        ?string $orderDate = null,
    ): array {
        return array_map(
            fn (array $entry): string => $entry['field'] === 'notes'
                ? $entry['text']
                : InvoiceFieldLabels::label($entry['field']).': '.$entry['text'],
            self::entries($deliveryTerms, $shippingMethod, $parsedNotes, $orderDate),
        );
    }
}
