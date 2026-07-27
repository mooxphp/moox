<?php

use Moox\ProductGroup\Resources\ProductGroupResource;

/*
|--------------------------------------------------------------------------
| Moox Configuration
|--------------------------------------------------------------------------
|
| Relations are resolved by moox/core (RelationService + ConfigRelationManager).
| Wire product ↔ productgroup assignments in the application config when both
| packages are installed.
|
*/
return [
    'readonly' => false,

    'resources' => [
        'productgroup' => [
            'single' => 'trans//productgroup::productgroup.productgroup',
            'plural' => 'trans//productgroup::productgroup.productgroups',

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
        ],
    ],

    'types' => [
        'family' => 'trans//productgroup::productgroup.type_family',
        'bundle_template' => 'trans//productgroup::productgroup.type_bundle_template',
        'service_family' => 'trans//productgroup::productgroup.type_service_family',
    ],

    'related_morph_defaults' => [
        'display_columns' => ['code', 'type', 'status'],
        'translation_prefix' => 'productgroup::productgroup',
        'related_resource' => ProductGroupResource::class,
        'record_select_search_columns' => ['code'],
    ],

    'navigation_group' => 'trans//core::core.cms',

    'statuses' => [
        'draft' => 'trans//productgroup::productgroup.status_draft',
        'active' => 'trans//productgroup::productgroup.status_active',
        'inactive' => 'trans//productgroup::productgroup.status_inactive',
        'archived' => 'trans//productgroup::productgroup.status_archived',
    ],
];
