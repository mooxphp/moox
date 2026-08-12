<?php

use Moox\Company\Models\Company;
use Moox\Company\Resources\CompanyResource;
use Moox\Customer\Models\Customer;
use Moox\Customer\Models\CustomerAssignment;
use Moox\Customer\Resources\CustomerResource;

/*
|--------------------------------------------------------------------------
| Moox Configuration
|--------------------------------------------------------------------------
|
| Customer profiles extend company master data with commercial customer fields.
| Wire the company relation in the published application config (config/customer.php).
|
*/
return [
    'readonly' => false,

    'statuses' => [
        'draft',
        'active',
        'inactive',
        'needs_review',
        'approved',
        'archived',
    ],

    'price_types' => [
        'standard',
        'dealer',
    ],

    /*
    |--------------------------------------------------------------------------
    | Preferred e-billing formats
    |--------------------------------------------------------------------------
    |
    | Allowed values for customers.preferred_ebilling_format. Kept as a plain
    | string list so moox/customer does not depend on moox/e-billing. Portal /
    | e-billing UI may enrich labels from FormatRegistry at runtime.
    |
    */
    'preferred_ebilling_formats' => [
        'xrechnung',
        'zugferd',
        'factur-x',
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy Comwork mapping
    |--------------------------------------------------------------------------
    |
    | Kundenstamm.ID_Kunde → external_reference
    | Kundenstamm.Debitorennummer → customer_number
    | Kundenstamm.ID_Preisart → price_type (1=standard, 2=dealer)
    | Kundenstamm.ID_Kundengruppe → customer_group
    |
    | Payment terms, currency, tax and shipping belong in moox/payment (future).
    |
    */
    'company_roles' => [
        'general',
        'account_manager',
    ],

    'resources' => [
        'customer' => [
            'single' => 'trans//customer::customer.customer',
            'plural' => 'trans//customer::customer.customers',

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
                    'label' => 'trans//customer::fields.active',
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
                'needs_review' => [
                    'label' => 'Nachprüfung',
                    'icon' => 'gmdi-warning-amber-o',
                    'query' => [
                        [
                            'field' => 'status',
                            'operator' => '=',
                            'value' => 'needs_review',
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
                        'customer' => Customer::class,
                    ],
                ],
            ],
        ],
    ],

    'related_morph_defaults' => [
        'display_columns' => ['customer_name', 'customer_number', 'status', 'price_type'],
        'translation_prefix' => 'customer::fields',
        'related_resource' => CustomerResource::class,
        'record_select_search_columns' => ['customer_name', 'customer_number', 'external_reference'],
    ],

    'relations' => [
        'customer_assignments' => [
            'kind' => 'morph_pivot',
            'perspective' => 'related',
            'presentation' => 'tab',
            'label' => 'trans//customer::fields.companies',
            'inverse_label' => 'trans//customer::fields.customers',
            'translation_prefix' => 'company::fields',
            'relationship' => 'companies',
            'inverse_relationship' => 'customers',
            'model' => Company::class,
            'related_resource' => CompanyResource::class,
            'pivot_model' => CustomerAssignment::class,
            'pivot_table' => 'customer_assignments',
            'morph_name' => 'assignable',
            'related_key' => 'customer_id',
            'display_columns' => ['name', 'display_name', 'status'],
            'badge_columns' => ['status'],
            'record_select_search_columns' => ['name', 'display_name', 'legal_name'],
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
