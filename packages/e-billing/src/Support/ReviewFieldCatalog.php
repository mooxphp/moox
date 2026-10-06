<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Illuminate\Database\Eloquent\Model;
use Moox\EBilling\Actions\CorrectFieldValueAction;
use Moox\EBilling\Actions\SetRecipientEmailAction;
use Moox\EBilling\Models\EbillingDocument;
use Moox\Invoice\Models\Invoice;
use Moox\Invoice\Models\InvoiceAllowanceCharge;
use Moox\Invoice\Models\InvoiceLine;

/**
 * Maps the rows of the invoice view to what the review workspace edits (ADR 0004). Row keys:
 * `invoice:{field}`, `notes:{index}` and `line:{line id}:{field}`, with the view's field names.
 * Every row resolves to an edit or to a visible reason; none silently lacks a control.
 */
final class ReviewFieldCatalog
{
    private const ADDRESS_PARTS = ['line1', 'line2', 'postal_code', 'city', 'subdivision', 'country_code'];

    private const BANK_ACCOUNT_PARTS = ['iban', 'bic', 'bank_name'];

    /**
     * @var array<string, array<string, string>>
     */
    private const INVOICE_FIELDS = [
        'invoice_number' => ['invoice_number' => 'text'],
        'invoice_date' => ['invoice_date' => 'date'],
        'preceding_invoice_number' => ['preceding_invoices.0.number' => 'text'],
        'preceding_invoice_date' => ['preceding_invoices.0.date' => 'date'],
        'due_date' => ['due_date' => 'date'],
        'currency' => ['currency' => 'text'],
        'order_number' => ['order_number' => 'text'],
        'order_date' => ['order_date' => 'date'],
        'customer_reference' => ['customer_reference' => 'text'],
        'payment_terms' => ['payment_terms' => 'textarea'],
        'material_test_certificate' => ['material_test_certificate' => 'text'],
        'supplier_name' => ['seller.name' => 'text'],
        'supplier_number' => ['supplier_number' => 'text'],
        'supplier_vat_id' => ['seller.vat_id' => 'text'],
        'supplier_tax_number' => ['seller.tax_number' => 'text'],
        'supplier_email' => ['seller.contact.email' => 'email'],
        'supplier_phone' => ['seller.contact.phone' => 'text'],
        'agent' => ['seller.contact.name' => 'text'],
        'payment_means' => ['payment_means.payment_means_code' => 'text'],
        'customer_number' => ['customer_number' => 'text'],
        'customer_name' => ['buyer.name' => 'text'],
        'customer_vat_id' => ['buyer.vat_id' => 'text'],
        'delivery_date' => ['delivery_date' => 'date'],
        'delivery_terms' => ['delivery_terms' => 'textarea'],
        'shipping_method' => ['shipping_method' => 'text'],
        'net_total' => ['net_total' => 'decimal'],
        'vat_category' => ['vat_category' => 'text'],
        'vat_rate' => ['vat_rate' => 'decimal'],
        'vat_amount' => ['vat_amount' => 'decimal'],
        'gross_total' => ['gross_total' => 'decimal'],
    ];

    /**
     * @var array<string, array<string, string>>
     */
    private const LINE_FIELDS = [
        'position' => ['position' => 'integer'],
        'description' => ['description' => 'text'],
        'description_detail' => ['description_detail' => 'textarea'],
        'quantity' => ['quantity' => 'decimal'],
        'unit' => ['unit' => 'text'],
        'unit_price' => ['unit_price' => 'decimal'],
        'line_total' => ['line_total' => 'decimal'],
        'article_number' => ['article_number' => 'text'],
        'material' => ['material' => 'text'],
        'customs_tariff_number' => ['customs_tariff_number' => 'text'],
        'delivery_date' => ['delivery_date' => 'date'],
        'delivery_note_number' => ['delivery_note_number' => 'text'],
        'order_number' => ['order_number' => 'text'],
        'order_date' => ['order_date' => 'date'],
        'weight_kg_total' => ['weight_kg_total' => 'decimal'],
        'weight_kg_net' => ['weight_kg_net' => 'decimal'],
        'material_test_certificate' => ['material_test_certificate' => 'text'],
    ];

    public function __construct(
        private readonly CorrectFieldValueAction $correctFieldValue,
    ) {
    }

    public static function invoiceKey(string $field): string
    {
        return "invoice:{$field}";
    }

    public static function noteKey(int $index): string
    {
        return "notes:{$index}";
    }

    public static function lineKey(InvoiceLine $line, string $field): string
    {
        return "line:{$line->getKey()}:{$field}";
    }

    /**
     * One charge of an itemized document charge field (e.g. Versand per delivery).
     */
    public static function chargeKey(InvoiceAllowanceCharge $charge): string
    {
        return "charge:{$charge->getKey()}";
    }

    public function resolve(Invoice $invoice, string $key): ReviewEditField
    {
        $parts = explode(':', $key, 3);

        return match ($parts[0]) {
            'invoice' => $this->invoiceField($invoice, $key, $parts[1] ?? ''),
            'notes' => $this->valueField($invoice, $key, InvoiceFieldLabels::label('notes'), $invoice, ['notes.'.(int) ($parts[1] ?? 0) => 'textarea']),
            'line' => $this->lineField($invoice, $key, (string) ($parts[1] ?? ''), $parts[2] ?? ''),
            'charge' => $this->chargeField($invoice, $key, (string) ($parts[1] ?? '')),
            default => $this->notEditable($key, $key, 'unknown_field'),
        };
    }

    private function chargeField(Invoice $invoice, string $key, string $chargeId): ReviewEditField
    {
        $charge = $invoice->allowanceCharges->first(
            fn (InvoiceAllowanceCharge $charge): bool => (string) $charge->getKey() === $chargeId,
        );

        if (! $charge instanceof InvoiceAllowanceCharge) {
            return $this->notEditable($key, $key, 'unknown_field');
        }

        return $this->valueField($invoice, $key, (string) $charge->reason_text, $charge, ['amount' => 'decimal']);
    }

    /**
     * The value parsed before the first correction, per corrected input of the row.
     *
     * @param  array<string, mixed>  $correctedFields  {@see ParsedValueHistory::correctedFields()}
     * @return array<string, mixed> attribute => parsed value
     */
    public function parsedValuesOfCorrections(Invoice $invoice, ReviewEditField $edit, array $correctedFields): array
    {
        if ($edit->target === null) {
            return [];
        }

        $parsed = [];
        foreach (array_keys($edit->inputs) as $attribute) {
            $key = $edit->target->getMorphClass().'|'.$edit->target->getKey().'|'.$this->correctFieldValue->field($invoice, $edit->target, $attribute);
            if (array_key_exists($key, $correctedFields)) {
                $parsed[$attribute] = $correctedFields[$key];
            }
        }

        return $parsed;
    }

    private function invoiceField(Invoice $invoice, string $key, string $field): ReviewEditField
    {
        $label = InvoiceFieldLabels::label($field);

        if ($field === 'document_type') {
            return DocumentClassification::isClassificationType((string) $invoice->document_type)
                ? new ReviewEditField($key, ReviewEditField::KIND_CLASSIFICATION, $label, $invoice, ['document_type' => 'classification'])
                : $this->notEditable($key, $label, 'not_classifiable');
        }

        if ($field === 'buyer_email') {
            return $this->recipientField($invoice, $key, $label);
        }

        if (array_key_exists($field, HeaderChargeResolver::FIELD_SPECS)) {
            if (HeaderChargeResolver::itemizedCharges($invoice->allowanceCharges, $field) !== []) {
                return $this->notEditable($key, $label, 'several_charges');
            }

            $charge = $this->headerCharge($invoice, $field);

            return $charge === null
                ? $this->notEditable($key, $label, 'no_charge_row')
                : $this->valueField($invoice, $key, $label, $charge, [$field === 'discount_percent' ? 'percentage' : 'amount' => 'decimal']);
        }

        $inputs = match ($field) {
            'customer_address' => $this->addressInputs('buyer'),
            'supplier_address' => $this->addressInputs('seller'),
            'delivery_address' => $this->consigneeInputs(),
            'supplier_bank_accounts' => $this->bankAccountInputs($invoice),
            default => self::INVOICE_FIELDS[$field] ?? null,
        };

        return $inputs === null
            ? $this->notEditable($key, $label, 'unknown_field')
            : $this->valueField($invoice, $key, $label, $invoice, $inputs, CrossCheckedFields::mapping($field) !== null ? $field : null);
    }

    private function lineField(Invoice $invoice, string $key, string $lineId, string $field): ReviewEditField
    {
        $label = InvoiceFieldLabels::label($field);
        $line = $invoice->lines->first(fn (InvoiceLine $line): bool => (string) $line->getKey() === $lineId);

        if (! $line instanceof InvoiceLine) {
            return $this->notEditable($key, $label, 'unknown_field');
        }

        if ($field === 'vat_category') {
            return $this->notEditable($key, $label, 'document_level');
        }

        $charge = match ($field) {
            'surcharge_amount', 'surcharge_description' => LineAllowanceChargeResolver::findSurchargeCharge($line->allowanceCharges),
            'material_test_certificate_price' => LineAllowanceChargeResolver::findMaterialTestCertificateCharge($line->allowanceCharges, $line),
            default => false,
        };

        if ($charge !== false) {
            return $charge === null
                ? $this->notEditable($key, $label, 'no_charge_row')
                : $this->valueField($invoice, $key, $label, $charge, [$field === 'surcharge_description' ? 'reason_text' : 'amount' => $field === 'surcharge_description' ? 'text' : 'decimal']);
        }

        $inputs = $field === 'delivery_address'
            ? $this->consigneeInputs()
            : (self::LINE_FIELDS[$field] ?? null);

        return $inputs === null
            ? $this->notEditable($key, $label, 'unknown_field')
            : $this->valueField($invoice, $key, $label, $line, $inputs);
    }

    /**
     * Inputs the value-correction rules refuse are dropped; a row left with none shows why.
     *
     * @param  array<string, string>  $inputs
     */
    private function valueField(Invoice $invoice, string $key, string $label, Model $target, array $inputs, ?string $crossChecked = null): ReviewEditField
    {
        $refusal = null;
        foreach (array_keys($inputs) as $attribute) {
            $refusal = $this->correctFieldValue->refusal($invoice, $target, $attribute);
            if ($refusal !== null) {
                unset($inputs[$attribute]);
            }
        }

        if ($inputs === []) {
            return $this->notEditable($key, $label, $refusal ?? 'unknown_field');
        }

        return new ReviewEditField($key, ReviewEditField::KIND_VALUE, $label, $target, $inputs, $crossChecked);
    }

    /**
     * On a mail-sourced document the recipient is a value correction and follows its rules;
     * elsewhere it is set as its own act ({@see SetRecipientEmailAction}).
     */
    private function recipientField(Invoice $invoice, string $key, string $label): ReviewEditField
    {
        $document = EbillingDocument::query()->where('invoice_id', $invoice->getKey())->first();
        $refusal = $document?->inboxAttachment() !== null
            ? $this->correctFieldValue->refusal($invoice, $invoice, SetRecipientEmailAction::ATTRIBUTE)
            : null;

        return $refusal === null
            ? new ReviewEditField($key, ReviewEditField::KIND_RECIPIENT, $label, $invoice, [SetRecipientEmailAction::ATTRIBUTE => 'email'])
            : $this->notEditable($key, $label, $refusal);
    }

    private function notEditable(string $key, string $label, string $reason): ReviewEditField
    {
        return new ReviewEditField($key, ReviewEditField::KIND_NONE, $label, reason: __("e-billing::fields.review_not_editable.{$reason}"));
    }

    /**
     * @return array<string, string>
     */
    private function addressInputs(string $party): array
    {
        $inputs = [];
        foreach (self::ADDRESS_PARTS as $part) {
            $inputs["{$party}.address.{$part}"] = 'text';
        }

        return $inputs;
    }

    /**
     * A consignee is its name and address, saved together so a missing one can be added.
     *
     * @return array<string, string>
     */
    private function consigneeInputs(): array
    {
        return ['delivery.name' => 'text', ...$this->addressInputs('delivery')];
    }

    /**
     * Every bank account on the document, or the first one to add when the parser found none.
     *
     * @return array<string, string>
     */
    private function bankAccountInputs(Invoice $invoice): array
    {
        $count = max(1, count($invoice->payment_means->bank_accounts ?? []));

        $inputs = [];
        for ($index = 0; $index < $count; $index++) {
            foreach (self::BANK_ACCOUNT_PARTS as $part) {
                $inputs["payment_means.bank_accounts.{$index}.{$part}"] = 'text';
            }
        }

        return $inputs;
    }

    private function headerCharge(Invoice $invoice, string $field): ?InvoiceAllowanceCharge
    {
        foreach ($invoice->allowanceCharges as $charge) {
            $matches = $field === 'discount_percent'
                ? ! $charge->is_charge && (float) $charge->percentage > 0
                : HeaderChargeResolver::matches($charge, HeaderChargeResolver::FIELD_SPECS[$field], $field === 'discount_amount');

            if ($matches) {
                return $charge;
            }
        }

        return null;
    }
}
