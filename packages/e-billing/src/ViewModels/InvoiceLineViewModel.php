<?php

declare(strict_types=1);

namespace Moox\EBilling\ViewModels;

use Carbon\Carbon;
use Moox\EBilling\Support\InvoiceFieldLabels;
use Moox\EBilling\Support\LineAllowanceChargeResolver;
use Moox\Invoice\Models\InvoiceLine;
use Moox\Invoice\Support\En16931\Address;

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
            hint: InvoiceFieldLabels::hint($name, $status),
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

        if (in_array($field, ['quantity', 'weight_kg_total', 'weight_kg_net'], true) && is_numeric($value)) {
            return number_format((float) $value, 3, ',', '.');
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
            'delivery_address' => $this->formatEn16931Address($this->line->delivery),
            default => $this->line->getAttribute($field),
        };
    }

    private function formatEn16931Address(?Address $address): ?string
    {
        if ($address === null) {
            return null;
        }

        $lines = [];

        if ($address->line2 !== null && trim($address->line2) !== '') {
            foreach (preg_split("/\r\n|\r|\n/", $address->line2) ?: [] as $segment) {
                $segment = trim((string) $segment);
                if ($segment !== '') {
                    $lines[] = $segment;
                }
            }
        }

        if (trim($address->line1) !== '') {
            $lines[] = trim($address->line1);
        }

        $postalCity = trim($address->postal_code.' '.$address->city);
        if ($postalCity !== '') {
            $lines[] = $postalCity;
        }

        if (trim($address->country_code) !== '') {
            $lines[] = trim($address->country_code);
        }

        if ($lines === []) {
            return null;
        }

        return implode("\n", $lines);
    }
}
