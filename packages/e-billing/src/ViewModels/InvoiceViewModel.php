<?php

declare(strict_types=1);

namespace Moox\EBilling\ViewModels;

use Carbon\Carbon;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Support\HeaderChargeResolver;
use Moox\EBilling\Support\InvoiceFieldLabels;
use Moox\Invoice\Models\Invoice;
use Moox\Invoice\Support\En16931\Address;
use Moox\Invoice\Support\En16931\BankAccount;

final class InvoiceViewModel
{
    /**
     * @var list<string>
     */
    private const FIELDS_WITHOUT_PERSISTED_SOURCE = [
        'customer_number',
        'payment_terms',
        'shipping_method',
    ];

    public function __construct(
        private Invoice $invoice, // Extend Invoice in your host app if needed
        private ?EbillingDocument $document = null,
    ) {
        $this->invoice->loadMissing([
            'allowanceCharges',
            'lines.allowanceCharges',
        ]);
    }

    /**
     * @return array<string, array{title: string, subtitle: string, fields: list<FieldViewData>}>
     */
    public function groupedFields(): array
    {
        return [
            'document' => [
                'title' => __('e-billing::fields.section_document_data'),
                'subtitle' => 'BG-1',
                'fields' => $this->buildFields([
                    'invoice_number', 'invoice_date', 'document_type',
                    'due_date', 'currency', 'order_number', 'order_date',
                    'customer_reference', 'payment_terms',
                ]),
            ],
            'supplier' => [
                'title' => __('e-billing::fields.section_seller_supplier'),
                'subtitle' => 'BG-4',
                'fields' => $this->buildFields([
                    'supplier_name', 'supplier_vat_id', 'supplier_tax_number',
                    'supplier_address', 'supplier_bank_accounts',
                ]),
            ],
            'buyer' => [
                'title' => __('e-billing::fields.section_buyer_customer'),
                'subtitle' => 'BG-7',
                'fields' => $this->buildFields([
                    'customer_number', 'customer_name',
                    'customer_vat_id', 'customer_address',
                ]),
            ],
            'delivery' => [
                'title' => __('e-billing::fields.section_delivery'),
                'subtitle' => 'BG-13',
                'fields' => $this->buildFields([
                    'delivery_address', 'shipping_method', 'agent', 'pricing_basis',
                ]),
            ],
            'totals' => [
                'title' => __('e-billing::fields.section_amounts'),
                'subtitle' => 'BG-22',
                'fields' => $this->buildFields([
                    'net_total', 'vat_rate', 'vat_amount', 'gross_total',
                    'discount_percent', 'discount_amount',
                    'shipping_cost', 'freight_flat_rate', 'packaging_cost', 'minimum_quantity_surcharge',
                ]),
            ],
        ];
    }

    /**
     * @return list<InvoiceLineViewModel>
     */
    public function lines(): array
    {
        $lineValidationsRoot = is_array($this->document?->field_validations)
            ? ($this->document->field_validations['lines'] ?? null)
            : null;
        $lineValidationsRoot = is_array($lineValidationsRoot) ? $lineValidationsRoot : [];

        return $this->invoice->lines
            ->map(function ($line) use ($lineValidationsRoot): InvoiceLineViewModel {
                $lineKey = (string) $line->getKey();
                $validations = is_array($lineValidationsRoot[$lineKey] ?? null)
                    ? $lineValidationsRoot[$lineKey]
                    : [];

                return new InvoiceLineViewModel($line, $validations);
            })
            ->all();
    }

    /**
     * @return list<int|string>
     */
    public function notes(): array
    {
        return [];
    }

    /**
     * @return array{color: string, text: string}
     */
    public function statusBanner(): array
    {
        $status = $this->document?->review_status;
        if (! $status instanceof InvoiceProcessingStatus) {
            $raw = $this->document?->getAttributes()['review_status'] ?? null;
            $status = is_string($raw) && $raw !== ''
                ? InvoiceProcessingStatus::tryFrom($raw)
                : null;
        }

        if ($status === null) {
            return [
                'color' => 'default',
                'text' => '',
            ];
        }

        return match ($status) {
            InvoiceProcessingStatus::ParserCreated => [
                'color' => 'red',
                'text' => __('e-billing::fields.banner_incomplete'),
            ],
            InvoiceProcessingStatus::DbValidated => [
                'color' => 'yellow',
                'text' => __('e-billing::fields.banner_db_validated'),
            ],
            InvoiceProcessingStatus::HumanConfirmed => [
                'color' => 'blue',
                'text' => __('e-billing::fields.banner_human_confirmed'),
            ],
            InvoiceProcessingStatus::Validated => [
                'color' => 'green',
                'text' => __('e-billing::fields.banner_validated'),
            ],
        };
    }

    /**
     * @return array{color: string, text: string}|null
     */
    public function gatewayStatusBanner(): ?array
    {
        $status = $this->document?->gateway_status;
        if (! $status instanceof EBillingAttachmentProcessingStatus || ! $status->isFailure()) {
            return null;
        }

        return [
            'color' => 'red',
            'text' => $status->label(),
        ];
    }

    public function validationScore(): ?int
    {
        return $this->document?->validation_score;
    }

    /**
     * Count of fields (invoice + line items) with status "needs review" or "missing".
     */
    public function attentionFieldCount(): int
    {
        $n = 0;
        $invoiceFv = is_array($this->document?->field_validations) ? $this->document->field_validations : [];

        foreach ($invoiceFv as $field => $entry) {
            if ($field === 'lines' || ! is_array($entry)) {
                continue;
            }
            $st = $entry['status'] ?? '';
            if (is_string($st) && in_array($st, ['needs_review', 'missing'], true)) {
                $n++;
            }
        }

        $linesFv = $invoiceFv['lines'] ?? null;
        if (is_array($linesFv)) {
            foreach ($linesFv as $lineFieldValidations) {
                if (! is_array($lineFieldValidations)) {
                    continue;
                }
                foreach ($lineFieldValidations as $entry) {
                    if (! is_array($entry)) {
                        continue;
                    }
                    $st = $entry['status'] ?? '';
                    if (is_string($st) && in_array($st, ['needs_review', 'missing'], true)) {
                        $n++;
                    }
                }
            }
        }

        return $n;
    }

    public function formatValue(string $field): mixed
    {
        $value = $this->resolveFieldValue($field);

        if ($value === null || $value === '') {
            return null;
        }

        if (in_array($field, ['customer_address', 'supplier_address', 'delivery_address'], true)) {
            return is_string($value) ? $value : null;
        }

        if ($field === 'supplier_bank_accounts') {
            if (! is_array($value)) {
                return null;
            }

            $formatted = $this->formatBankAccounts($value);

            return $formatted === [] ? null : $formatted;
        }

        if (in_array($field, [
            'net_total', 'vat_amount', 'gross_total', 'discount_amount',
            'shipping_cost', 'freight_flat_rate', 'packaging_cost', 'minimum_quantity_surcharge',
        ], true) && is_numeric($value)) {
            return number_format((float) $value, 2, ',', '.');
        }

        if ($field === 'vat_rate' && is_numeric($value)) {
            return number_format((float) $value, 2, ',', '.').' %';
        }

        if ($field === 'discount_percent' && is_numeric($value)) {
            return number_format((float) $value, 2, ',', '.').' %';
        }

        if (in_array($field, ['invoice_date', 'due_date', 'order_date'], true) && is_string($value) && $value !== '') {
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
        if (in_array($field, self::FIELDS_WITHOUT_PERSISTED_SOURCE, true)) {
            return null;
        }

        if (array_key_exists($field, HeaderChargeResolver::FIELD_SPECS)) {
            return HeaderChargeResolver::resolveAmount($this->invoice->allowanceCharges, $field);
        }

        return match ($field) {
            'customer_name' => $this->invoice->buyer?->name,
            'customer_vat_id' => $this->invoice->buyer?->vat_id,
            'customer_address' => $this->formatEn16931Address(
                $this->invoice->buyer?->address,
                $this->invoice->buyer?->name,
            ),
            'country' => $this->invoice->buyer?->address?->country_code,
            'supplier_name' => $this->invoice->seller?->name,
            'supplier_vat_id' => $this->invoice->seller?->vat_id,
            'supplier_tax_number' => $this->invoice->seller?->tax_number,
            'supplier_address' => $this->formatEn16931Address(
                $this->invoice->seller?->address,
                $this->invoice->seller?->name,
            ),
            'agent' => $this->invoice->seller?->contact?->name,
            'supplier_bank_accounts' => $this->invoice->payment_means?->bank_accounts ?? [],
            'delivery_address' => $this->formatEn16931Address($this->invoice->delivery, null),
            default => $this->invoice->getAttribute($field),
        };
    }

    /**
     * Display order: party name (when provided), line2 segments (newline-split), line1, postal_code + city, country_code.
     */
    private function formatEn16931Address(?Address $address, ?string $partyName): ?string
    {
        if ($address === null) {
            return null;
        }

        $lines = [];

        if ($partyName !== null && trim($partyName) !== '') {
            $lines[] = trim($partyName);
        }

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

    /**
     * @param  list<BankAccount|array<string, mixed>>|array<int, BankAccount|array<string, mixed>>  $accounts
     * @return list<array{iban: ?string, bic: ?string, bank_name: ?string}>
     */
    private function formatBankAccounts(array $accounts): array
    {
        return array_values(array_map(static function (mixed $acc): array {
            if ($acc instanceof BankAccount) {
                return [
                    'iban' => $acc->iban !== '' ? $acc->iban : null,
                    'bic' => $acc->bic,
                    'bank_name' => $acc->bank_name,
                ];
            }

            if (! is_array($acc)) {
                return ['iban' => null, 'bic' => null, 'bank_name' => null];
            }

            return [
                'iban' => isset($acc['iban']) && is_string($acc['iban']) && $acc['iban'] !== '' ? $acc['iban'] : null,
                'bic' => isset($acc['bic']) && is_string($acc['bic']) ? $acc['bic'] : null,
                'bank_name' => isset($acc['bank_name']) && is_string($acc['bank_name']) ? $acc['bank_name'] : null,
            ];
        }, $accounts));
    }

    /**
     * @param  list<string>  $fieldNames
     * @return list<FieldViewData>
     */
    private function buildFields(array $fieldNames): array
    {
        $validations = is_array($this->document?->field_validations) ? $this->document->field_validations : [];

        return array_map(function (string $name) use ($validations): FieldViewData {
            $entry = $validations[$name] ?? null;
            $validation = is_array($entry) ? $entry : null;
            $status = is_array($validation) && isset($validation['status']) && is_string($validation['status'])
                ? $validation['status']
                : '';

            return new FieldViewData(
                field: $name,
                label: InvoiceFieldLabels::label($name),
                btNumber: InvoiceFieldLabels::btNumber($name),
                value: $this->formatValue($name),
                validation: $validation,
                hint: InvoiceFieldLabels::hint($name, $status),
            );
        }, $fieldNames);
    }
}
