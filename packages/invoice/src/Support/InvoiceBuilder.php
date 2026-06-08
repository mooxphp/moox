<?php

declare(strict_types=1);

namespace Moox\Invoice\Support;

use Illuminate\Support\Facades\DB;
use Moox\Invoice\Models\Invoice;

class InvoiceBuilder
{
    /**
     * Persist invoice, lines, and allowance/charge rows in a single transaction.
     * Skips opening a nested transaction when the caller already has one active.
     */
    public function build(InvoiceDraft $draft): Invoice
    {
        $persist = fn (): Invoice => $this->persist($draft);

        if (DB::transactionLevel() > 0) {
            return $persist();
        }

        return DB::transaction($persist);
    }

    private function persist(InvoiceDraft $draft): Invoice
    {
        $invoiceClass = InvoiceModels::invoice();

        /** @var Invoice $invoice */
        $invoice = new $invoiceClass;
        $invoice->invoice_number = $draft->invoice_number;
        $invoice->invoice_date = $draft->invoice_date;
        $invoice->document_type = $draft->document_type;
        $invoice->due_date = $draft->due_date;
        $invoice->currency = $draft->currency;
        $invoice->customer_reference = $draft->customer_reference;
        $invoice->order_number = $draft->order_number;
        $invoice->order_date = $draft->order_date;
        $invoice->pricing_basis = $draft->pricing_basis;
        $invoice->seller = $draft->seller;
        $invoice->buyer = $draft->buyer;
        $invoice->delivery = $draft->delivery;
        $invoice->payment_means = $draft->payment_means;
        $invoice->net_total = $draft->net_total;
        $invoice->vat_rate = $draft->vat_rate;
        $invoice->vat_amount = $draft->vat_amount;
        $invoice->gross_total = $draft->gross_total;
        $invoice->save();

        foreach ($draft->headerCharges as $chargeDraft) {
            $invoice->allowanceCharges()->create($chargeDraft->toCreateAttributes());
        }

        foreach ($draft->lines as $lineDraft) {
            $this->persistLine($invoice, $lineDraft);
        }

        $invoice->load(['lines.allowanceCharges', 'allowanceCharges']);

        return $invoice;
    }

    private function persistLine(Invoice $invoice, InvoiceLineDraft $lineDraft): void
    {
        $lineClass = InvoiceModels::invoiceLine();

        $line = new $lineClass;
        $line->invoice_id = $invoice->id;
        $line->position = $lineDraft->position;
        $line->unit = $lineDraft->unit;
        $line->quantity = (string) $lineDraft->quantity;
        $line->description = $lineDraft->description !== '' && $lineDraft->description !== null
            ? $lineDraft->description
            : null;
        $line->description_detail = $lineDraft->description_detail;
        $line->article_number = $lineDraft->article_number;
        $line->customs_tariff_number = $lineDraft->customs_tariff_number;
        $line->unit_price = (string) $lineDraft->unit_price;
        $line->line_total = (string) $lineDraft->line_total;
        $line->delivery_date = $lineDraft->delivery_date;
        $line->delivery_note_number = $lineDraft->delivery_note_number;
        $line->order_number = $lineDraft->order_number;
        $line->order_date = $lineDraft->order_date;
        $line->delivery = $lineDraft->delivery;

        $fillable = $line->getFillable();

        foreach ($lineDraft->extra as $key => $value) {
            if (in_array($key, $fillable, true)) {
                $line->{$key} = $value;
            }
        }

        $line->save();

        foreach ($lineDraft->charges as $chargeDraft) {
            $line->allowanceCharges()->create($chargeDraft->toCreateAttributes());
        }
    }
}
