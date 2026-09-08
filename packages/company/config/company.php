<?php

use Moox\Company\Models\Company;
use Moox\Company\Resources\CompanyResource;
use Moox\User\Models\User;

/*
|--------------------------------------------------------------------------
| Moox Configuration
|--------------------------------------------------------------------------
|
| Greenfield ERP company entity. Relations are resolved by moox/core
| (RelationService + ConfigRelationManager). Override model classes and
| pivot details in the application config/company.php.
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

    'default_currency_code' => 'EUR',

    'resources' => [
        'company' => [

            'single' => 'trans//company::company.company',
            'plural' => 'trans//company::company.companies',

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
                    'label' => 'trans//company::fields.active',
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
                        'company' => Company::class,
                    ],
                ],
            ],
        ],
    ],

    'relations' => [
        'parent' => [
            'kind' => 'belongs_to',
            'presentation' => 'tab',
            'label' => 'trans//company::fields.parent',
            'relationship' => 'parent',
            'model' => Company::class,
            'related_resource' => CompanyResource::class,
            'foreign_key' => 'parent_id',
            'display_columns' => ['name', 'status'],
            'badge_columns' => ['status'],
            'record_select_search_columns' => ['name', 'display_name', 'legal_name'],
            'actions' => [
                'header' => ['associate'],
                'record' => ['view', 'edit', 'dissociate'],
            ],
            'associate' => [
                'exclude' => ['self', 'inverse'],
            ],
        ],
        'children' => [
            'kind' => 'has_many',
            'presentation' => 'tab',
            'label' => 'trans//company::fields.children',
            'relationship' => 'children',
            'inverse_relationship' => 'parent',
            'model' => Company::class,
            'related_resource' => CompanyResource::class,
            'foreign_key' => 'parent_id',
            'display_columns' => ['name', 'status'],
            'badge_columns' => ['status'],
            'create_prefill' => [
                'parent_id' => 'owner.id',
            ],
            'record_select_search_columns' => ['name', 'display_name', 'legal_name'],
            'actions' => [
                'header' => ['associate', 'create'],
                'record' => ['view', 'edit', 'dissociate'],
            ],
            'associate' => [
                'strategy' => 'inverse',
                'multiple' => true,
            ],
        ],
    ],

    'taxonomies' => [
    ],

    'user_models' => [
        App\Models\User::class => [
            'title_attribute' => 'name',
            'label' => 'App User',
        ],
        User::class => [
            'title_attribute' => 'name',
            'label' => 'Moox User',
        ],
    ],

    'navigation_group' => 'Portal',

    /*
    |--------------------------------------------------------------------------
    | Audit defaults
    |--------------------------------------------------------------------------
    |
    | Registered with moox/audit when installed. Override in config/audit.php.
    | Disable this package: set enabled => false (or AUDIT_ENABLED=false globally).
    |
    */

    'audit' => [
        'enabled' => true,
        'models' => [
            Company::class => [
                'log_name' => 'company',
                'attributes' => [
                    'status',
                    'is_active',
                    'name',
                    'display_name',
                    'legal_name',
                    'note',
                    'parent_id',
                    'external_reference',
                    'phone',
                    'fax',
                    'url',
                    'email',
                    'tax_number',
                    'vat_number',
                    'default_currency_code',
                    'language_id',
                ],
            ],
        ],
        'filament' => [
            CompanyResource::class => [
                'owner_model' => Company::class,
            ],
        ],
    ],
];
