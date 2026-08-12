<?php

declare(strict_types=1);

namespace Moox\EBilling\Services;

use Moox\Company\Models\Company;
use Moox\Customer\Models\Customer;
use Moox\EBilling\Enums\AttributionSource;
use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\EBilling\Events\InvoiceValidationCompleted;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Support\AttributionCorroborator;
use Moox\EBilling\Support\CompanyNameMatcher;
use Moox\EBilling\Support\CustomerMatcher;
use Moox\EBilling\Support\HeaderChargeResolver;
use Moox\EBilling\Support\LineAllowanceChargeResolver;
use Moox\EBilling\Support\VatIdNormalizer;
use Moox\Invoice\Models\Invoice;
use Moox\Invoice\Models\InvoiceLine;
use Moox\Invoice\Support\En16931\Address;
use RuntimeException;

class InvoiceFieldValidator
{
    /**
     * Populate field_validations on the document (invoice-level + lines sub-structure),
     * attribute the document to a {@see Customer} via buyer identifier when present,
     * and set company_id by derivation (or name fallback when no identifier match).
     *
     * Does NOT change review_status or fire events.
     */
    public function fillFieldValidations(EbillingDocument $document): void
    {
        $this->guardAgainstRevalidation($document);

        $invoice = $this->resolveInvoice($document);

        $invoiceFields = config('e-billing.field_validation.invoice_fields', []);
        $lineFields = config('e-billing.field_validation.invoice_line_fields', []);

        if (! is_array($invoiceFields)) {
            $invoiceFields = [];
        }
        if (! is_array($lineFields)) {
            $lineFields = [];
        }

        $invoice->loadMissing(['allowanceCharges', 'lines.allowanceCharges']);

        $isManualAttribution = $document->attribution_source === AttributionSource::Manual;

        if ($isManualAttribution) {
            $matchedCustomer = $document->customer_id !== null
                ? Customer::query()->withTrashed()->find($document->customer_id)
                : null;
            $derivedCompanyId = $document->company_id;
            $matchedCompany = $derivedCompanyId !== null
                ? Company::query()->find($derivedCompanyId)
                : null;
        } else {
            $hasIdentifier = ! $this->isScalarEmpty($invoice->customer_number);
            $matchedCustomer = $hasIdentifier
                ? $this->resolveCustomerMatch($invoice)
                : null;

            $derivedCompanyId = null;
            $matchedCompany = null;
            if ($matchedCustomer !== null) {
                $derivedCompanyId = (new CustomerMatcher)->resolveCompanyId($matchedCustomer);
                if ($derivedCompanyId !== null) {
                    $matchedCompany = Company::query()->find($derivedCompanyId);
                }
            } else {
                // Name fallback when no identifier, or identifier present but unmatched (#21).
                $matchedCompany = $this->resolveCompanyMatch($invoice);
                $derivedCompanyId = $matchedCompany?->id;
            }
        }

        $invoiceValidations = [];
        foreach ($invoiceFields as $field => $priority) {
            if (! is_string($field) || ! is_string($priority)) {
                continue;
            }
            $invoiceValidations[$field] = $this->validateInvoiceField(
                $invoice,
                $field,
                $priority,
                $matchedCompany,
                $matchedCustomer,
                $derivedCompanyId,
                $isManualAttribution,
            );
        }

        $lineValidations = [];
        foreach ($invoice->lines as $line) {
            $lineValidations[(string) $line->getKey()] = $this->validateInvoiceLine($line, $lineFields);
        }

        $invoiceValidations['lines'] = $lineValidations;

        $document->field_validations = $invoiceValidations;

        if (! $isManualAttribution) {
            $document->customer_id = $matchedCustomer?->id;
            $document->company_id = $derivedCompanyId;
            $document->attribution_source = $matchedCustomer !== null
                ? AttributionSource::Auto
                : null;
        }

        $document->validation_score = $document->calculateValidationScore();
        $document->save();
    }

    /**
     * Full validation flow: populates field_validations, advances review_status, fires {@see InvoiceValidationCompleted}.
     */
    public function validate(EbillingDocument $document): void
    {
        $this->fillFieldValidations($document);

        $invoiceFields = config('e-billing.field_validation.invoice_fields', []);
        $lineFields = config('e-billing.field_validation.invoice_line_fields', []);

        if (! is_array($invoiceFields)) {
            $invoiceFields = [];
        }
        if (! is_array($lineFields)) {
            $lineFields = [];
        }

        $allMustAndShouldClean = $this->allMustAndShouldFieldsAreClean($document, $invoiceFields, $lineFields);

        if ($allMustAndShouldClean) {
            $document->transitionTo(InvoiceProcessingStatus::Validated);
        } else {
            $document->transitionTo(InvoiceProcessingStatus::DbValidated);
        }

        $document->refresh();

        event(new InvoiceValidationCompleted(
            document: $document,
            needsHumanReview: $document->needsHumanReview(),
        ));
    }

    private function guardAgainstRevalidation(EbillingDocument $document): void
    {
        $status = $document->review_status;
        if ($status instanceof InvoiceProcessingStatus) {
            $current = $status;
        } else {
            $raw = $document->getAttributes()['review_status'] ?? InvoiceProcessingStatus::ParserCreated->value;
            $current = InvoiceProcessingStatus::from((string) $raw);
        }

        if (in_array($current, [InvoiceProcessingStatus::HumanConfirmed, InvoiceProcessingStatus::Validated], true)) {
            throw new RuntimeException(
                "Cannot re-validate EbillingDocument #{$document->id}: review_status is '{$current->value}'. "
                .'Reset to an earlier status before re-validating.'
            );
        }
    }

    private function resolveInvoice(EbillingDocument $document): Invoice
    {
        $invoice = $document->invoice;

        if (! $invoice instanceof Invoice) {
            throw new RuntimeException(
                "Cannot validate EbillingDocument #{$document->id}: no linked invoice."
            );
        }

        return $invoice;
    }

    private function resolveCompanyMatch(Invoice $invoice): ?Company
    {
        return (new CompanyNameMatcher)->match($invoice->buyer?->name);
    }

    private function resolveCustomerMatch(Invoice $invoice): ?Customer
    {
        return (new CustomerMatcher)->match(
            is_string($invoice->customer_number) ? $invoice->customer_number : null,
        );
    }

    /**
     * @param  array<string, string>  $invoiceFields
     * @param  array<string, string>  $lineFields
     */
    private function allMustAndShouldFieldsAreClean(
        EbillingDocument $document,
        array $invoiceFields,
        array $lineFields,
    ): bool {
        // `parsed` is clean: present on the document without a master-data check.
        // Without it, must/should fields that only ever become `parsed` block
        // automatic progression to Validated forever (#21 / #25).
        $cleanStatuses = ['validated', 'db_validated', 'not_applicable', 'parsed'];

        $validations = is_array($document->field_validations) ? $document->field_validations : [];

        foreach ($invoiceFields as $field => $priority) {
            if (! is_string($field) || ! is_string($priority)) {
                continue;
            }
            if (! in_array($priority, ['must', 'should'], true)) {
                continue;
            }
            $status = $this->readNestedFieldStatus($validations, $field);
            if (! in_array($status, $cleanStatuses, true)) {
                return false;
            }
        }

        $linesValidations = $validations['lines'] ?? null;
        if (! is_array($linesValidations)) {
            return true;
        }

        foreach ($linesValidations as $lineFieldsValidations) {
            if (! is_array($lineFieldsValidations)) {
                continue;
            }
            foreach ($lineFields as $field => $priority) {
                if (! is_string($field) || ! is_string($priority)) {
                    continue;
                }
                if (! in_array($priority, ['must', 'should'], true)) {
                    continue;
                }
                $status = $this->readNestedFieldStatus($lineFieldsValidations, $field);
                if (! in_array($status, $cleanStatuses, true)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return array{status: string, source?: string, matched_id?: string}
     */
    private function validateInvoiceField(
        Invoice $invoice,
        string $field,
        string $priority,
        ?Company $matchedCompany,
        ?Customer $matchedCustomer = null,
        ?string $derivedCompanyId = null,
        bool $isManualAttribution = false,
    ): array {
        return match ($field) {
            'customer_number' => $this->validateCustomerNumberField(
                $invoice,
                $priority,
                $matchedCustomer,
                $derivedCompanyId,
                $isManualAttribution,
            ),
            'customer_name' => $this->validateCustomerNameField(
                $invoice,
                $priority,
                $matchedCompany,
                $matchedCustomer,
            ),
            'customer_vat_id' => $this->validateCustomerVatField(
                $invoice,
                $priority,
                $matchedCompany,
                $matchedCustomer,
            ),
            'country' => $this->validateCountryField(
                $invoice,
                $priority,
                $matchedCompany,
                $matchedCustomer,
            ),
            'customer_address' => $this->validateCustomerAddressField(
                $invoice,
                $priority,
                $matchedCompany,
                $matchedCustomer,
            ),
            'shipping_cost', 'packaging_cost', 'minimum_quantity_surcharge', 'freight_flat_rate',
            'discount_amount', 'discount_percent' => $this->validateHeaderChargeField($invoice, $field, $priority),
            default => $this->validateGenericInvoiceField($invoice, $field, $priority),
        };
    }

    /**
     * @return array{status: string, source?: string, matched_id?: string}
     */
    private function validateCustomerNumberField(
        Invoice $invoice,
        string $priority,
        ?Customer $matchedCustomer,
        ?string $derivedCompanyId = null,
        bool $isManualAttribution = false,
    ): array {
        if ($isManualAttribution && $matchedCustomer !== null) {
            return [
                'status' => (new CustomerMatcher)->isReviewableMatch($matchedCustomer, $derivedCompanyId)
                    ? 'needs_review'
                    : 'db_validated',
                'source' => AttributionSource::Manual->value,
                'matched_id' => (string) $matchedCustomer->id,
            ];
        }

        $raw = $invoice->customer_number;
        if ($this->isScalarEmpty($raw)) {
            return $this->entryForEmptyField('customer_number', $priority, false);
        }

        if ($matchedCustomer === null) {
            return ['status' => 'needs_review'];
        }

        return [
            'status' => (new CustomerMatcher)->isReviewableMatch($matchedCustomer, $derivedCompanyId)
                ? 'needs_review'
                : 'db_validated',
            'source' => AttributionSource::Auto->value,
            'matched_id' => (string) $matchedCustomer->id,
        ];
    }

    /**
     * @return array{status: string, source?: string, matched_id?: string}
     */
    private function validateCustomerNameField(
        Invoice $invoice,
        string $priority,
        ?Company $matchedCompany,
        ?Customer $matchedCustomer = null,
    ): array {
        $raw = $invoice->buyer?->name;
        if ($this->isScalarEmpty($raw)) {
            return $this->entryForEmptyField('customer_name', $priority, false);
        }

        if ($matchedCustomer !== null) {
            $result = (new AttributionCorroborator)->corroborateName(
                is_string($raw) ? $raw : null,
                $matchedCustomer,
                $matchedCompany,
            );

            return [
                'status' => $result['corroborates'] ? 'db_validated' : 'needs_review',
                'source' => 'auto',
                'matched_id' => $result['matched_id'],
            ];
        }

        // Name-fallback path (no customer attribution): exact/loose company name match.
        if ($matchedCompany !== null) {
            if ($this->stringsLooselyMatch($raw, $matchedCompany->name)) {
                return [
                    'status' => 'db_validated',
                    'source' => 'auto',
                    'matched_id' => $matchedCompany->id,
                ];
            }

            return ['status' => 'needs_review', 'source' => 'auto', 'matched_id' => $matchedCompany->id];
        }

        return ['status' => 'parsed'];
    }

    /**
     * @return array{status: string, source?: string, matched_id?: string}
     */
    private function validateCustomerVatField(
        Invoice $invoice,
        string $priority,
        ?Company $matchedCompany,
        ?Customer $matchedCustomer = null,
    ): array {
        $raw = $invoice->buyer?->vat_id;
        if ($this->isScalarEmpty($raw)) {
            return $this->entryForEmptyField('customer_vat_id', $priority, false);
        }

        if ($matchedCustomer !== null) {
            $comparison = (new AttributionCorroborator)->compareVat(
                is_string($raw) ? $raw : null,
                $matchedCompany?->vat_number,
            );

            if ($comparison === null) {
                // Absent on master-data side is not a divergence (#25).
                return ['status' => 'parsed'];
            }

            return [
                'status' => $comparison ? 'validated' : 'needs_review',
                'source' => 'auto',
                'matched_id' => (string) ($matchedCompany?->id ?? $matchedCustomer->id),
            ];
        }

        if ($matchedCompany !== null) {
            $expected = $matchedCompany->vat_number;
            if ($this->isScalarEmpty($expected)) {
                return ['status' => 'parsed'];
            }

            if ($this->normalizeVat($raw) === $this->normalizeVat($expected)) {
                return [
                    'status' => 'validated',
                    'source' => 'auto',
                    'matched_id' => $matchedCompany->id,
                ];
            }

            return ['status' => 'needs_review', 'source' => 'auto', 'matched_id' => $matchedCompany->id];
        }

        return ['status' => 'parsed'];
    }

    /**
     * @return array{status: string, source?: string, matched_id?: string}
     */
    private function validateCountryField(
        Invoice $invoice,
        string $priority,
        ?Company $matchedCompany,
        ?Customer $matchedCustomer,
    ): array {
        $raw = $invoice->buyer?->address?->country_code;
        if ($this->isScalarEmpty($raw)) {
            return $this->entryForEmptyField('country', $priority, false);
        }

        // Address/country corroboration runs only after a successful customer match.
        if ($matchedCustomer === null) {
            return ['status' => 'parsed'];
        }

        $comparison = (new AttributionCorroborator)->compareCountry(
            is_string($raw) ? $raw : null,
            $matchedCompany,
        );

        if ($comparison === null) {
            return ['status' => 'parsed'];
        }

        return [
            'status' => $comparison ? 'db_validated' : 'needs_review',
            'source' => 'auto',
            'matched_id' => (string) ($matchedCompany?->id ?? $matchedCustomer->id),
        ];
    }

    /**
     * @return array{status: string, source?: string, matched_id?: string}
     */
    private function validateCustomerAddressField(
        Invoice $invoice,
        string $priority,
        ?Company $matchedCompany,
        ?Customer $matchedCustomer,
    ): array {
        $address = $invoice->buyer?->address;
        if ($this->isEn16931AddressEmpty($address)) {
            return $this->entryForEmptyField('customer_address', $priority, false);
        }

        // Address check runs only after a successful customer match (#25).
        if ($matchedCustomer === null) {
            return ['status' => 'parsed'];
        }

        $result = (new AttributionCorroborator)->findMatchingAddress($address, $matchedCompany);

        if ($result === null) {
            return $this->entryForEmptyField('customer_address', $priority, false);
        }

        if ($result['exists']) {
            return [
                'status' => 'db_validated',
                'source' => 'auto',
                'matched_id' => $result['matched_id'],
            ];
        }

        return [
            'status' => 'needs_review',
            'source' => 'auto',
            'matched_id' => $result['matched_id'] !== ''
                ? $result['matched_id']
                : (string) $matchedCustomer->id,
        ];
    }

    /**
     * @return array{status: string, source?: string, matched_id?: string}
     */
    private function validateHeaderChargeField(Invoice $invoice, string $field, string $priority): array
    {
        if ($field === 'discount_percent') {
            if (HeaderChargeResolver::hasDiscountPercentSignal($invoice->allowanceCharges)) {
                return ['status' => 'parsed'];
            }

            return $this->entryForEmptyField($field, $priority, false);
        }

        if (HeaderChargeResolver::hasMatchingCharge($invoice->allowanceCharges, $field)) {
            return ['status' => 'parsed'];
        }

        return $this->entryForEmptyField($field, $priority, false);
    }

    /**
     * @return array{status: string, source?: string, matched_id?: string}
     */
    private function validateGenericInvoiceField(Invoice $invoice, string $field, string $priority): array
    {
        $value = $this->getInvoiceFieldValue($invoice, $field);

        if ($this->isInvoiceFieldValueEmpty($field, $value)) {
            return $this->entryForEmptyField($field, $priority, false);
        }

        return ['status' => 'parsed'];
    }

    private function getInvoiceFieldValue(Invoice $invoice, string $field): mixed
    {
        return match ($field) {
            'customer_name' => $invoice->buyer?->name,
            'customer_vat_id' => $invoice->buyer?->vat_id,
            'customer_address' => $invoice->buyer?->address,
            'country' => $invoice->buyer?->address?->country_code,
            'supplier_name' => $invoice->seller?->name,
            'supplier_vat_id' => $invoice->seller?->vat_id,
            'supplier_tax_number' => $invoice->seller?->tax_number,
            'supplier_address' => $invoice->seller?->address,
            'agent' => $invoice->seller?->contact?->name,
            'supplier_bank_accounts' => $invoice->payment_means?->bank_accounts ?? [],
            'delivery_address' => $invoice->delivery,
            default => $invoice->getAttribute($field),
        };
    }

    /**
     * @param  array<string, string>  $lineFields
     * @return array<string, array{status: string, source?: string, matched_id?: string}>
     */
    private function validateInvoiceLine(InvoiceLine $line, array $lineFields): array
    {
        $line->loadMissing('allowanceCharges');

        $validations = [];

        foreach ($lineFields as $field => $priority) {
            if (! is_string($field) || ! is_string($priority)) {
                continue;
            }

            $validations[$field] = $this->validateInvoiceLineField($line, $field, $priority);
        }

        return $validations;
    }

    /**
     * @return array{status: string, source?: string, matched_id?: string}
     */
    private function validateInvoiceLineField(InvoiceLine $line, string $field, string $priority): array
    {
        return match ($field) {
            'surcharge_amount', 'surcharge_description' => $this->validateLineSurchargeField($line, $field, $priority),
            'material_test_certificate_price' => $this->validateLineMaterialTestCertificatePriceField($line, $priority),
            default => $this->validateGenericInvoiceLineField($line, $field, $priority),
        };
    }

    /**
     * @return array{status: string, source?: string, matched_id?: string}
     */
    private function validateLineSurchargeField(InvoiceLine $line, string $field, string $priority): array
    {
        if (LineAllowanceChargeResolver::hasSurchargeCharge($line->allowanceCharges)) {
            return ['status' => 'parsed'];
        }

        return $this->entryForEmptyField($field, $priority, true);
    }

    /**
     * @return array{status: string, source?: string, matched_id?: string}
     */
    private function validateLineMaterialTestCertificatePriceField(InvoiceLine $line, string $priority): array
    {
        if (LineAllowanceChargeResolver::hasMaterialTestCertificateCharge($line->allowanceCharges, $line)) {
            return ['status' => 'parsed'];
        }

        return $this->entryForEmptyField('material_test_certificate_price', $priority, true);
    }

    /**
     * @return array{status: string, source?: string, matched_id?: string}
     */
    private function validateGenericInvoiceLineField(InvoiceLine $line, string $field, string $priority): array
    {
        $value = match ($field) {
            'delivery_address' => $line->delivery,
            default => $line->getAttribute($field),
        };

        if ($field === 'delivery_address') {
            if ($this->isEn16931AddressEmpty($value instanceof Address ? $value : null)) {
                return $this->entryForEmptyField($field, $priority, true);
            }

            return ['status' => 'parsed'];
        }

        if ($this->isScalarEmpty($value)) {
            return $this->entryForEmptyField($field, $priority, true);
        }

        return ['status' => 'parsed'];
    }

    private function isInvoiceFieldValueEmpty(string $field, mixed $value): bool
    {
        return match ($field) {
            'customer_address', 'delivery_address', 'supplier_address' => $this->isEn16931AddressEmpty(
                $value instanceof Address ? $value : null
            ),
            'supplier_bank_accounts' => ! is_array($value) || $value === [],
            default => $this->isScalarEmpty($value),
        };
    }

    /**
     * @return array{status: string, source?: string, matched_id?: string}
     */
    private function entryForEmptyField(string $field, string $priority, bool $isInvoiceLine): array
    {
        if ($priority === 'could') {
            return ['status' => 'not_applicable'];
        }

        if ($priority === 'must') {
            return ['status' => 'missing'];
        }

        $key = $isInvoiceLine ? 'invoice_line_contextual_should' : 'invoice_contextual_should';
        $list = config("e-billing.field_validation.{$key}", []);

        if (! is_array($list)) {
            return ['status' => 'not_applicable'];
        }

        if (in_array($field, $list, true)) {
            return ['status' => 'missing'];
        }

        return ['status' => 'not_applicable'];
    }

    private function isEn16931AddressEmpty(?Address $address): bool
    {
        if ($address === null) {
            return true;
        }

        foreach (['line1', 'city', 'postal_code', 'country_code'] as $key) {
            $value = match ($key) {
                'line1' => $address->line1,
                'city' => $address->city,
                'postal_code' => $address->postal_code,
                'country_code' => $address->country_code,
                default => '',
            };

            if (! $this->isScalarEmpty($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $validations
     */
    private function readNestedFieldStatus(array $validations, string $field): ?string
    {
        if (! isset($validations[$field]) || ! is_array($validations[$field])) {
            return null;
        }

        $status = $validations[$field]['status'] ?? null;

        return is_string($status) ? $status : null;
    }

    private function stringsLooselyMatch(mixed $a, mixed $b): bool
    {
        $left = $this->normalizeString(is_string($a) ? $a : (is_scalar($a) ? (string) $a : ''));
        $right = $this->normalizeString(is_string($b) ? $b : (is_scalar($b) ? (string) $b : ''));

        if ($left === '' && $right === '') {
            return true;
        }

        return strcasecmp($left, $right) === 0;
    }

    private function normalizeString(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $trimmed = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return $trimmed;
    }

    private function normalizeVat(?string $value): string
    {
        return strtoupper(VatIdNormalizer::normalize($value) ?? '');
    }

    private function isScalarEmpty(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return $this->normalizeString($value) === '';
        }

        if (is_numeric($value)) {
            return false;
        }

        return $value === '';
    }
}
