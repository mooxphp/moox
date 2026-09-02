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

        return self::collect(
            $invoice->delivery_terms !== null ? (string) $invoice->delivery_terms : null,
            $invoice->shipping_method !== null ? (string) $invoice->shipping_method : null,
            is_array($parsedNotes) ? $parsedNotes : [],
        );
    }

    /**
     * @return list<string>
     */
    public static function fromDto(InvoiceDto $invoice): array
    {
        return self::collect(
            $invoice->deliveryTerms,
            $invoice->shippingMethod,
            $invoice->notes,
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
    ): array {
        $notes = [];

        if ($deliveryTerms !== null && trim($deliveryTerms) !== '') {
            $notes[] = ['field' => 'delivery_terms', 'text' => trim($deliveryTerms)];
        }

        if ($shippingMethod !== null && trim($shippingMethod) !== '') {
            $notes[] = ['field' => 'shipping_method', 'text' => trim($shippingMethod)];
        }

        foreach ($parsedNotes as $note) {
            if (is_string($note) && trim($note) !== '') {
                $notes[] = ['field' => 'notes', 'text' => trim($note)];
            }
        }

        return $notes;
    }

    /**
     * Build BT-22 note texts from invoice fields (delivery terms, shipping method, parser notes).
     *
     * @param  list<string>  $parsedNotes
     * @return list<string>
     */
    public static function collect(
        ?string $deliveryTerms,
        ?string $shippingMethod,
        array $parsedNotes = [],
    ): array {
        return array_map(
            fn (array $entry): string => $entry['field'] === 'notes'
                ? $entry['text']
                : InvoiceFieldLabels::label($entry['field']).': '.$entry['text'],
            self::entries($deliveryTerms, $shippingMethod, $parsedNotes),
        );
    }
}
