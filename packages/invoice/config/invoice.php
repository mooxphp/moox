<?php

use Moox\Invoice\Models\Invoice;
use Moox\Invoice\Models\InvoiceAllowanceCharge;
use Moox\Invoice\Models\InvoiceLine;

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
    'models' => [
        'invoice' => Invoice::class,
        'invoice_line' => InvoiceLine::class,
        'invoice_allowance_charge' => InvoiceAllowanceCharge::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit
    |--------------------------------------------------------------------------
    |
    | Registered with moox/audit when it is installed. Keys are the package
    | models; configured subclasses in `models` are audited in their place.
    | Override per model in the host's config/audit.php.
    |
    */

    'audit' => [
        'enabled' => true,
        'models' => [
            Invoice::class => [
                'log_name' => 'invoice',
                'attributes' => [
                    'invoice_number',
                    'invoice_date',
                    'document_type',
                    'due_date',
                    'currency',
                    'customer_number',
                    'customer_reference',
                    'order_number',
                    'order_date',
                    'delivery_date',
                    'payment_terms',
                    'shipping_method',
                    'delivery_terms',
                    'seller',
                    'buyer',
                    'delivery',
                    'payment_means',
                    'net_total',
                    'vat_rate',
                    'vat_amount',
                    'gross_total',
                ],
            ],
            InvoiceLine::class => [
                'log_name' => 'invoice',
                'attributes' => [
                    'position',
                    'unit',
                    'unit_code',
                    'quantity',
                    'description',
                    'description_detail',
                    'article_number',
                    'customs_tariff_number',
                    'unit_price',
                    'line_total',
                    'delivery',
                    'delivery_date',
                    'delivery_note_number',
                    'order_number',
                    'order_date',
                ],
            ],
            InvoiceAllowanceCharge::class => [
                'log_name' => 'invoice',
                'attributes' => [
                    'is_charge',
                    'amount',
                    'reason_code',
                    'reason_text',
                    'base_amount',
                    'percentage',
                ],
            ],
        ],
    ],
];
