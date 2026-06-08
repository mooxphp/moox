<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Illuminate\Support\Str;

final class InvoiceFieldLabels
{
    public static function get(string $fieldName): string
    {
        return match ($fieldName) {
            'invoice_number' => __('e-billing::fields.invoice_number'),
            'invoice_date' => __('e-billing::fields.invoice_date'),
            'document_type' => __('e-billing::fields.document_type'),
            'due_date' => __('e-billing::fields.due_date'),
            'currency' => __('e-billing::fields.currency'),
            'customer_number' => __('e-billing::fields.customer_number'),
            'customer_name' => __('e-billing::fields.customer_name'),
            'customer_address' => __('e-billing::fields.customer_address'),
            'country' => __('e-billing::fields.country'),
            'customer_vat_id' => __('e-billing::fields.customer_vat_id'),
            'customer_reference' => __('e-billing::fields.customer_reference'),
            'order_number' => __('e-billing::fields.order_number'),
            'order_date' => __('e-billing::fields.order_date'),
            'delivery_address' => __('e-billing::fields.delivery_address'),
            'supplier_name' => __('e-billing::fields.supplier_name'),
            'supplier_vat_id' => __('e-billing::fields.supplier_vat_id'),
            'supplier_tax_number' => __('e-billing::fields.tax_number'),
            'supplier_address' => __('e-billing::fields.supplier_address'),
            'supplier_number' => __('e-billing::fields.supplier_number'),
            'supplier_phone' => __('e-billing::fields.supplier_phone'),
            'supplier_email' => __('e-billing::fields.supplier_email'),
            'supplier_bank_accounts' => __('e-billing::fields.bank_accounts'),
            'agent' => __('e-billing::fields.agent'),
            'payment_terms' => __('e-billing::fields.payment_terms'),
            'pricing_basis' => __('e-billing::fields.pricing_basis'),
            'shipping_method' => __('e-billing::fields.shipping_method'),
            'net_total' => __('e-billing::fields.net_total'),
            'vat_rate' => __('e-billing::fields.vat_rate'),
            'vat_amount' => __('e-billing::fields.vat_amount'),
            'gross_total' => __('e-billing::fields.gross_total'),
            'discount_percent' => __('e-billing::fields.discount_percent'),
            'discount_amount' => __('e-billing::fields.discount_amount'),
            'shipping_cost' => __('e-billing::fields.shipping_cost'),
            'minimum_quantity_surcharge' => __('e-billing::fields.minimum_quantity_surcharge'),
            'freight_flat_rate' => __('e-billing::fields.freight_flat_rate'),
            'packaging_cost' => __('e-billing::fields.packaging_cost'),
            'notes' => __('e-billing::fields.notes'),
            'position' => __('e-billing::fields.position'),
            'description' => __('e-billing::fields.description'),
            'description_detail' => __('e-billing::fields.description_detail'),
            'quantity' => __('e-billing::fields.quantity'),
            'unit' => __('e-billing::fields.unit'),
            'unit_price' => __('e-billing::fields.unit_price'),
            'line_total' => __('e-billing::fields.line_total'),
            'article_number' => __('e-billing::fields.article_number'),
            'material' => __('e-billing::fields.material'),
            'material_number' => __('e-billing::fields.material_number'),
            'material_test_certificate' => __('e-billing::fields.material_test_certificate'),
            'material_test_certificate_price' => __('e-billing::fields.material_test_certificate_price'),
            'customs_tariff_number' => __('e-billing::fields.customs_tariff_number'),
            'weight_kg_total' => __('e-billing::fields.weight_kg_total'),
            'weight_kg_net' => __('e-billing::fields.weight_kg_net'),
            'weight' => __('e-billing::fields.weight'),
            'weight_unit' => __('e-billing::fields.weight_unit'),
            'surcharge_amount' => __('e-billing::fields.surcharge_amount'),
            'surcharge_description' => __('e-billing::fields.surcharge_description'),
            'surcharge_rate' => __('e-billing::fields.surcharge_rate'),
            'delivery_date' => __('e-billing::fields.delivery_date'),
            'delivery_note_number' => __('e-billing::fields.delivery_note_number'),
            'seller_address' => __('e-billing::fields.seller_address'),
            'seller_tax_id' => __('e-billing::fields.seller_tax_id'),
            'seller_phone' => __('e-billing::fields.seller_phone'),
            'seller_email' => __('e-billing::fields.seller_email'),
            'seller_bank_iban' => __('e-billing::fields.seller_bank_iban'),
            'seller_bank_bic' => __('e-billing::fields.seller_bank_bic'),
            'seller_bank_name' => __('e-billing::fields.seller_bank_name'),
            'buyer_address' => __('e-billing::fields.buyer_address'),
            'buyer_tax_id' => __('e-billing::fields.buyer_tax_id'),
            default => Str::headline(str_replace('_', ' ', $fieldName)),
        };
    }

    public static function getValidationStatusLabel(string $status): string
    {
        return match ($status) {
            'valid', 'validated' => __('e-billing::fields.validation_status_valid'),
            'db_validated' => __('e-billing::fields.validation_status_db_validated'),
            'parsed' => __('e-billing::fields.validation_status_parsed'),
            'not_applicable' => __('e-billing::fields.validation_status_not_applicable'),
            'needs_review' => __('e-billing::fields.validation_status_needs_review'),
            'invalid' => __('e-billing::fields.validation_status_invalid'),
            'missing' => __('e-billing::fields.validation_status_missing'),
            'unmatched' => __('e-billing::fields.validation_status_unmatched'),
            default => $status,
        };
    }

    public static function getValidationMessage(string $status, string $priority): string
    {
        return match (true) {
            in_array($status, ['valid', 'validated', 'db_validated', 'parsed', 'not_applicable'], true) => __('e-billing::fields.validation_message_ok'),
            $status === 'missing' && $priority === 'must' => __('e-billing::fields.validation_message_required_missing'),
            $status === 'missing' && $priority === 'should' => __('e-billing::fields.validation_message_recommended_missing'),
            $status === 'missing' => __('e-billing::fields.validation_message_field_missing'),
            $status === 'unmatched' && $priority === 'must' => __('e-billing::fields.validation_message_required_not_in_master'),
            $status === 'unmatched' => __('e-billing::fields.validation_message_not_in_master'),
            $status === 'needs_review' && $priority === 'must' => __('e-billing::fields.validation_message_required_review'),
            $status === 'needs_review' => __('e-billing::fields.validation_message_review_deviation'),
            $status === 'invalid' && $priority === 'must' => __('e-billing::fields.validation_message_required_invalid'),
            $status === 'invalid' => __('e-billing::fields.validation_message_invalid_value'),
            default => __('e-billing::fields.validation_message_please_review'),
        };
    }

    public static function label(string $fieldName): string
    {
        return self::get($fieldName);
    }

    public static function btNumber(string $field, ?string $context = null): ?string
    {
        // NOTE: $context === 'invoice_line' is used by Filament ViewModels for line-level BT hints
        if ($context === 'invoice_line' && $field === 'order_number') {
            return 'BT-132';
        }

        return match ($field) {
            'invoice_number' => 'BT-1',
            'invoice_date' => 'BT-2',
            'document_type' => 'BT-3',
            'currency' => 'BT-5',
            'due_date' => 'BT-9',
            'customer_reference' => 'BT-10',
            'order_number' => 'BT-13',
            'payment_terms' => 'BT-20',
            'supplier_name' => 'BT-27',
            'supplier_vat_id' => 'BT-31',
            'supplier_tax_number' => 'BT-32',
            'supplier_address' => 'BG-5',
            'supplier_bank_accounts' => 'BG-17',
            'customer_name' => 'BT-44',
            'customer_vat_id' => 'BT-48',
            'customer_address' => 'BG-8',
            // Parsed from supplier address block
            'country' => 'BT-55',
            'delivery_address' => 'BG-15',
            'net_total' => 'BT-109',
            'vat_amount' => 'BT-110',
            'gross_total' => 'BT-112',
            'minimum_quantity_surcharge' => 'BG-22 / BT-99',
            'freight_flat_rate' => 'BG-22 / BT-99',
            'vat_rate' => 'BT-119',
            'quantity' => 'BT-129',
            'unit' => 'BT-130',
            'line_total' => 'BT-131',
            'delivery_date' => 'BT-134',
            'unit_price' => 'BT-146',
            'description' => 'BT-153',
            'article_number' => 'BT-155',
            'customs_tariff_number' => 'BT-158',
            'delivery_note_number' => 'BT-16',
            default => null,
        };
    }

    public static function hint(string $field, string $status): ?string
    {
        if ($status === 'missing') {
            return match ($field) {
                'invoice_number' => __('e-billing::fields.hint_missing_invoice_number'),
                'customer_number' => __('e-billing::fields.hint_missing_customer_number'),
                'customer_name' => __('e-billing::fields.hint_missing_customer_name'),
                'net_total' => __('e-billing::fields.hint_missing_net_total'),
                'vat_rate' => __('e-billing::fields.hint_missing_vat_rate'),
                'gross_total' => __('e-billing::fields.hint_missing_gross_total'),
                'invoice_date' => __('e-billing::fields.hint_missing_invoice_date'),
                'currency' => __('e-billing::fields.hint_missing_currency'),
                'supplier_name' => __('e-billing::fields.hint_missing_supplier_name'),
                'minimum_quantity_surcharge' => __('e-billing::fields.hint_missing_minimum_quantity_surcharge'),
                'freight_flat_rate' => __('e-billing::fields.hint_missing_freight_flat_rate'),
                'quantity' => __('e-billing::fields.hint_missing_quantity'),
                'description' => __('e-billing::fields.hint_missing_description'),
                default => __('e-billing::fields.hint_missing_default'),
            };
        }

        if ($status === 'needs_review') {
            return match ($field) {
                'customer_number' => __('e-billing::fields.hint_review_customer_number'),
                'customer_name' => __('e-billing::fields.hint_review_customer_name'),
                'article_number' => __('e-billing::fields.hint_review_article_number'),
                'material' => __('e-billing::fields.hint_review_material'),
                'customer_vat_id' => __('e-billing::fields.hint_review_customer_vat_id'),
                'supplier_vat_id' => __('e-billing::fields.hint_review_supplier_vat_id'),
                'unit_price' => __('e-billing::fields.hint_review_unit_price'),
                'minimum_quantity_surcharge' => __('e-billing::fields.hint_review_minimum_quantity_surcharge'),
                'freight_flat_rate' => __('e-billing::fields.hint_review_freight_flat_rate'),
                default => __('e-billing::fields.hint_review_default'),
            };
        }

        return null;
    }
}
