<?php

declare(strict_types=1);

namespace Moox\EBilling\ViewModels;

use Carbon\Carbon;
use Moox\EBilling\Support\InvoiceDisplayNumberFormatter;
use Moox\EBilling\Support\InvoiceFieldLabels;
use Moox\EBilling\Support\LineAllowanceChargeResolver;
use Moox\EBilling\Support\PartyAddressFormatter;
use Moox\Invoice\Models\InvoiceLine;

final class InvoiceLineViewModel
{
    /**
     * @param  array<string, array{status: string, source?: string, matched_id?: string}>  $lineValidations
     */
    public function __construct(
        private InvoiceLine $line, // Extend InvoiceLine in your host app if needed
        private array $lineValidations = [],
    ) {
        $this->line->loadMissing('allowanceCharges');
    }

    public function position(): ?string
    {
        $p = $this->line->position;

        return is_numeric($p) ? (string) $p : (is_string($p) ? $p : null);
    }

    /**
     * @return list<FieldViewData>
     */
    public function fields(): array
    {
        $names = [
            'position', 'description', 'description_detail',
            'quantity', 'unit', 'unit_price', 'line_total',
            'article_number', 'material', 'customs_tariff_number',
            'delivery_date', 'delivery_note_number',
            'order_number', 'order_date', 'delivery_address',
            'weight_kg_total', 'weight_kg_net',
            'surcharge_amount', 'surcharge_description',
            'material_test_certificate', 'material_test_certificate_price',
        ];

        return array_map(fn (string $name): FieldViewData => $this->buildField($name), $names);
    }

    /**
     * @return list<FieldViewData>
     */
    public function relevantFields(): array
    {
        return array_values(array_filter(
            $this->fields(),
            fn (FieldViewData $f): bool => $f->value !== null && $f->value !== ''
                || in_array($f->status(), ['missing', 'needs_review'], true)
        ));
    }

    private function buildField(string $name): FieldViewData
    {
        $entry = $this->lineValidations[$name] ?? null;
        $validation = is_array($entry) ? $entry : null;
        $status = is_array($validation) && isset($validation['status']) && is_string($validation['status'])
            ? $validation['status']
            : '';

        return new FieldViewData(
            field: $name,
            label: InvoiceFieldLabels::label($name),
            btNumber: InvoiceFieldLabels::btNumber($name, 'invoice_line'),
            value: $this->formatValue($name),
            validation: $validation,
            hint: InvoiceFieldLabels::hint($name, $status, $validation),
        );
    }

    private function formatValue(string $field): mixed
    {
        $value = $this->resolveFieldValue($field);

        if ($value === null || $value === '') {
            return null;
        }

        if ($field === 'delivery_address') {
            return is_string($value) ? $value : null;
        }

        if (in_array($field, ['unit_price', 'line_total', 'surcharge_amount', 'material_test_certificate_price'], true) && is_numeric($value)) {
            return number_format((float) $value, 2, ',', '.');
        }

        if ($field === 'quantity' && is_numeric($value)) {
            return InvoiceDisplayNumberFormatter::formatQuantity($value);
        }

        if (in_array($field, ['weight_kg_total', 'weight_kg_net'], true) && is_numeric($value)) {
            return InvoiceDisplayNumberFormatter::formatWeight($value);
        }

        if (in_array($field, ['delivery_date', 'order_date'], true) && is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value)->format('d.m.Y');
            } catch (\Throwable) {
                return $value;
            }
        }

        return is_scalar($value) ? $value : null;
    }

    private function resolveFieldValue(string $field): mixed
    {
        return match ($field) {
            'surcharge_amount' => LineAllowanceChargeResolver::resolveSurchargeAmount($this->line->allowanceCharges),
            'surcharge_description' => LineAllowanceChargeResolver::resolveSurchargeDescription($this->line->allowanceCharges),
            'material_test_certificate_price' => LineAllowanceChargeResolver::resolveMaterialTestCertificatePrice(
                $this->line->allowanceCharges,
                $this->line,
            ),
            'delivery_address' => PartyAddressFormatter::format($this->line->delivery),
            default => $this->line->getAttribute($field),
        };
    }
}
