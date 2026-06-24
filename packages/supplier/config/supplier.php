<?php

use Moox\Company\Models\Company;
use Moox\Company\Resources\CompanyResource;
use Moox\Supplier\Models\Supplier;
use Moox\Supplier\Models\SupplierAssignment;
use Moox\Supplier\Resources\SupplierResource;

/*
|--------------------------------------------------------------------------
| Moox Configuration
|--------------------------------------------------------------------------
|
| Supplier profiles extend company master data with procurement fields.
| Wire the company relation in the published application config (config/supplier.php).
|
*/
return [
    'readonly' => false,

    'statuses' => [
        'draft',
        'active',
        'inactive',
        'approved',
        'archived',
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy Comwork mapping
    |--------------------------------------------------------------------------
    |
    | Lieferantenstamm.ID_Lieferant → external_reference
    | Lieferantenstamm.Kreditorennummer → supplier_number
    | Lieferantenstamm.Rabatt → discount_percent
    | Lieferantenstamm.delivery_period → lead_time_days
    | Lieferantenstamm.is_main_supplier → is_preferred
    |
    | Payment terms, currency, tax and shipping belong in moox/payment (future).
    |
    */
    'company_roles' => [
        'general',
        'account_manager',
    ],

    'resources' => [
        'supplier' => [
            'single' => 'trans//supplier::supplier.supplier',
            'plural' => 'trans//supplier::supplier.suppliers',

            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [
                        [
                            'field' => 'deleted_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                    ],
                ],
                'active' => [
                    'label' => 'trans//supplier::fields.active',
                    'icon' => 'gmdi-check-circle-o',
                    'query' => [
                        [
                            'field' => 'is_active',
                            'operator' => '=',
                            'value' => true,
                        ],
                        [
                            'field' => 'deleted_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                    ],
                ],
                'deleted' => [
                    'label' => 'trans//core::core.deleted',
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

            'scopes' => [
                'registry' => [
                    'sources' => [
                        'supplier' => Supplier::class,
                    ],
                ],
            ],
        ],
    ],

    'related_morph_defaults' => [
        'display_columns' => ['supplier_number', 'status', 'lead_time_days'],
        'translation_prefix' => 'supplier::fields',
        'related_resource' => SupplierResource::class,
        'record_select_search_columns' => ['supplier_number', 'external_reference'],
    ],

    'relations' => [
        'supplier_assignments' => [
            'kind' => 'morph_pivot',
            'perspective' => 'related',
            'presentation' => 'tab',
            'label' => 'trans//supplier::fields.companies',
            'inverse_label' => 'trans//supplier::fields.suppliers',
            'translation_prefix' => 'supplier::fields',
            'relationship' => 'companies',
            'inverse_relationship' => 'suppliers',
            'model' => Company::class,
            'related_resource' => CompanyResource::class,
            'pivot_model' => SupplierAssignment::class,
            'pivot_table' => 'supplier_assignments',
            'morph_name' => 'assignable',
            'related_key' => 'supplier_id',
            'pivot_columns' => [
                'is_primary',
                'role',
            ],
            'actions' => [
                'header' => ['attach', 'create'],
                'record' => ['edit_pivot', 'detach'],
                'toolbar' => ['detach_bulk'],
            ],
        ],
    ],

    'taxonomies' => [
    ],

    'navigation_group' => 'Portal',
];
