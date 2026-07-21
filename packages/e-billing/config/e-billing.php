<?php

use Moox\EBilling\Resources\InvoiceResource;
use Moox\KositValidator\Models\KositValidatable;
use Moox\KositValidator\Models\KositValidation;

/*
|--------------------------------------------------------------------------
| Moox Configuration
|--------------------------------------------------------------------------
|
| This configuration file uses translatable strings. If you want to
| translate the strings, you can do so in the language files
| published from moox_core. Example:
|
| 'trans//core::core.all',
| loads from common.php
| outputs 'All'
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Invoice parser
    |--------------------------------------------------------------------------
    |
    | The PDF → Invoice DTO parser. The package ships none because the layout is
    | host-specific; set this to a class implementing
    | Moox\EBilling\Contracts\InvoiceParserInterface. When null, the parser is left
    | unbound and resolving the EBilling service fails fast with a clear error.
    |
    */

    'parser' => null,

    /*
    |--------------------------------------------------------------------------
    | Default e-invoice format
    |--------------------------------------------------------------------------
    |
    | FormatRegistry key frozen onto ebilling_documents.format at generation.
    |
    */

    'default_format' => 'zugferd',

    /*
    |--------------------------------------------------------------------------
    | Allowed BT-3 document type codes (UNTDID 1001)
    |--------------------------------------------------------------------------
    |
    | DocumentTypeCodeResolver only accepts these codes. Anything else throws
    | UnresolvedCodelistLabelException (routes to needs-review). Defaults cover
    | commercial invoice (380) and credit note (381).
    |
    */

    'allowed_document_type_codes' => ['380', '381'],

    /*
    |--------------------------------------------------------------------------
    | Filament: Invoices (InvoiceResource)
    |--------------------------------------------------------------------------
    */

    'resources' => [
        'invoices' => [
            'enabled' => true,
            'label' => 'trans//e-billing::ebilling.invoice',
            'plural_label' => 'trans//e-billing::ebilling.invoices',
            'navigation_group' => 'trans//e-billing::ebilling.navigation_group',
            'navigation_icon' => 'heroicon-o-document-text',
            'navigation_sort' => 1,
            'navigation_count_badge' => true,
            /*
            |--------------------------------------------------------------------------
            | Soft Delete Tab Key
            |--------------------------------------------------------------------------
            |
            | This key must match a tab key under 'tabs.invoices'. It tells our custom
            | applySoftDeleteQuery() which tab to treat as the trash view.
            |
            | IMPORTANT: Moox Core's SingleSoftDeleteInResource::getHardDeleteBulkAction()
            | still hardcodes 'deleted' and 'trash' for visibility. If you rename this
            | key to something else, the hard-delete bulk action from the vendor trait
            | may not appear on the correct tab. This is a known limitation.
            |
            | @see https://github.com/mooxphp/core — open an issue if this needs
            |      to be configurable in the trait.
            |
            */
            'soft_delete_tab_key' => 'deleted',
            'resource' => InvoiceResource::class,
        ],
    ],

    'tabs' => [
        'invoices' => [
            'all' => [
                'label' => 'trans//e-billing::fields.tab_all',
                'icon' => 'gmdi-filter-list',
                'query' => [
                    [
                        'field' => 'deleted_at',
                        'operator' => '=',
                        'value' => null,
                    ],
                ],
            ],
            'gateway_failed' => [
                'label' => 'trans//e-billing::fields.tab_gateway_failed',
                'icon' => 'gmdi-error',
                'query' => [
                    [
                        'field' => 'gateway_status',
                        'operator' => 'in',
                        'value' => ['generation_failed', 'validation_failed', 'validator_error'],
                    ],
                    [
                        'field' => 'deleted_at',
                        'operator' => '=',
                        'value' => null,
                    ],
                ],
            ],
            'processing' => [
                'label' => 'trans//e-billing::fields.tab_processing',
                'icon' => 'gmdi-hourglass-empty',
                'query' => [
                    [
                        'field' => 'gateway_status',
                        'operator' => 'in',
                        'value' => ['generating', 'validating'],
                    ],
                    [
                        'field' => 'deleted_at',
                        'operator' => '=',
                        'value' => null,
                    ],
                ],
            ],
            'needs_review' => [
                'label' => 'trans//e-billing::fields.tab_needs_review',
                'icon' => 'gmdi-warning',
                'query' => [
                    [
                        'field' => 'review_status',
                        'operator' => 'in',
                        'value' => ['parser_created', 'db_validated'],
                    ],
                    [
                        'field' => 'deleted_at',
                        'operator' => '=',
                        'value' => null,
                    ],
                ],
            ],
            'confirmed' => [
                'label' => 'trans//e-billing::fields.tab_confirmed',
                'icon' => 'gmdi-check-circle',
                'query' => [
                    [
                        'field' => 'review_status',
                        'operator' => 'in',
                        'value' => ['human_confirmed', 'validated'],
                    ],
                    [
                        'field' => 'deleted_at',
                        'operator' => '=',
                        'value' => null,
                    ],
                ],
            ],
            'deleted' => [
                'label' => 'trans//e-billing::fields.tab_deleted',
                'icon' => 'gmdi-delete',
                'query' => [
                    [
                        'field' => 'deleted_at',
                        'operator' => '!=',
                        'value' => null,
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | ZUGFeRD storage (filesystem disk `zugferd`)
    |--------------------------------------------------------------------------
    |
    | Relative paths on this disk follow `{scope}/{Y-m}/{invoiceNumber}_{date}.xml|.pdf`.
    | When `storage_root` is null, it defaults to `storage/app/private/{mail-inbox.zugferd.path}`.
    |
    */

    'zugferd' => [
        'storage_disk' => 'zugferd',
        'storage_root' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Foreign invoice handling (pre-XML filter)
    |--------------------------------------------------------------------------
    |
    | Non-German invoices are moved to this Microsoft 365 folder and marked
    | ignored on the attachment row (no Invoice record).
    |
    */

    'foreign_invoice' => [
        'ignored_folder_name' => env('EBILLING_IGNORED_FOLDER', 'Ignored'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default customer country (TRANSITIONAL)
    |--------------------------------------------------------------------------
    |
    | Fallback buyer/delivery country when the parser derives none (domestic
    | addresses without a country line). Removed by the master-data phase
    | (Company/Address lookup). Foreign invoices are still detected via
    | BILLING_COUNTRY_MAP and filtered before XML.
    |
    */

    'default_customer_country' => 'DE',

    /*
    |--------------------------------------------------------------------------
    | Supplier (master data)
    |--------------------------------------------------------------------------
    |
    | Central supplier data for invoices.
    | Copied onto the invoice as a snapshot when the invoice is created.
    |
    */

    // Example supplier data — override in your application's config/e-billing.php
    // or set via environment variables.
    'supplier' => [
        'name' => 'Acme Stainless GmbH',
        'vat_id' => 'DE123456789',
        'tax_number' => '12345/67890',
        'address' => [
            'company' => 'Acme Stainless GmbH',
            'street' => 'Musterstraße 1',
            'zip' => '12345',
            'city' => 'Musterstadt',
            'country' => 'DE',
        ],
        'country_code' => 'DE',
        'phone' => '+49 (0)1234 567890',
        'email' => 'billing@example.com',

        /*
        |--------------------------------------------------------------------------
        | Bank accounts
        |--------------------------------------------------------------------------
        |
        | Multiple accounts allowed (e.g. different banks / currencies).
        |
        */

        'bank_accounts' => [

            [
                'bank_name' => 'Example Bank eG',
                'iban' => 'DE00100000000000001234',
                'bic' => 'EXMPDEDB',
            ],

            [
                'bank_name' => 'Muster Sparkasse',
                'iban' => 'DE00200000000000005678',
                'bic' => 'MSPKDEDB',
            ],

        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Field validation (MoSCoW)
    |--------------------------------------------------------------------------
    */

    'field_validation' => [

        /*
        |--------------------------------------------------------------------------
        | MoSCoW Priority per Field
        |--------------------------------------------------------------------------
        |
        | Determines how the InvoiceFieldValidator treats each field:
        | - must:   Missing/invalid → blocks transition to validated, forces human review
        | - should: Missing/invalid → warning, ideally reviewed but not blocking
        | - could:  Missing/invalid → info only, auto-accepted
        |
        | Fields not listed here are treated as 'could' by default.
        |
        */

        'invoice_fields' => [
            // Document identification — MUST
            'invoice_number' => 'must',    // BT-1
            'invoice_date' => 'must',    // BT-2
            'document_type' => 'must',    // BT-3
            'due_date' => 'should',  // BT-9
            'currency' => 'must',    // BT-5

            // Buyer — MUST (core identification)
            'customer_number' => 'must',
            'customer_name' => 'must',    // BT-44
            'customer_address' => 'must',    // BG-8
            'country' => 'could',    // BT-55
            'customer_vat_id' => 'should',  // BT-48

            // Buyer reference
            'customer_reference' => 'should', // BT-10
            'order_number' => 'should',  // BT-13
            'order_date' => 'could',   // BT-13 date

            // Delivery
            'delivery_address' => 'could',   // BG-15

            // Seller — MUST (own company data, from system settings later)
            'supplier_name' => 'must',    // BT-27
            'supplier_vat_id' => 'must',    // BT-31
            'supplier_tax_number' => 'should', // BT-32
            'supplier_address' => 'must',    // BG-5
            'supplier_bank_accounts' => 'should', // BG-17

            // Agent & terms
            'agent' => 'could',
            'payment_terms' => 'should',  // BT-20
            'pricing_basis' => 'could',
            'shipping_method' => 'could',

            // Amounts — MUST
            'net_total' => 'must',    // BT-109
            'vat_rate' => 'must',    // BT-119
            'vat_amount' => 'must',    // BT-110
            'gross_total' => 'must',    // BT-112

            // Optional amounts
            'discount_percent' => 'could',
            'discount_amount' => 'could',
            'shipping_cost' => 'could',
            'minimum_quantity_surcharge' => 'could',
            'freight_flat_rate' => 'could',
            'packaging_cost' => 'could',
        ],

        'invoice_line_fields' => [
            'position' => 'must',
            'description' => 'must',    // BT-153
            'quantity' => 'must',    // BT-129
            'unit' => 'must',    // BT-130
            'unit_price' => 'must',    // BT-146
            'line_total' => 'must',    // BT-131

            'article_number' => 'should',  // BT-155
            'material' => 'should',  // Werkstoffnummer (industry-specific)
            'customs_tariff_number' => 'could', // BT-158

            'description_detail' => 'could',
            'material_test_certificate' => 'could',
            'material_test_certificate_price' => 'could',
            'weight_kg_total' => 'could',
            'weight_kg_net' => 'could',
            'surcharge_amount' => 'could',
            'surcharge_description' => 'could',
            'delivery_date' => 'should',  // BT-134
            'delivery_note_number' => 'could', // BT-16
            'order_number' => 'could',   // BT-132 (item-level override)
            'order_date' => 'could',
            'delivery_address' => 'could',
        ],

        /*
        |--------------------------------------------------------------------------
        | Contextual Rules (should-priority fields)
        |--------------------------------------------------------------------------
        |
        | When a 'should' field is empty: if it appears in the contextual list below,
        | status is 'missing'; otherwise 'not_applicable'. Fields not listed default
        | to 'not_applicable' when empty.
        |
        */

        'invoice_contextual_should' => [
            'customer_vat_id',
            'payment_terms',
            'supplier_tax_number',
            'supplier_bank_accounts',
        ],

        'invoice_line_contextual_should' => [
            'article_number',
            'material',
            'delivery_date',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Morph pivots (owner side → kosit_validatables)
    |--------------------------------------------------------------------------
    */
    'morph_relations' => [
        'kosit_validatables' => [
            'relationship' => 'kositValidations',
            'model' => KositValidation::class,
            'pivot_model' => KositValidatable::class,
            'pivot_table' => 'kosit_validatables',
            'morph_name' => 'validatable',
            'pivot_columns' => [],
            'related_key' => 'kosit_validation_id',
        ],
    ],

];
