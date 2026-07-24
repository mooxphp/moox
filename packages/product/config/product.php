<?php

/*
|--------------------------------------------------------------------------
| Moox Configuration
|--------------------------------------------------------------------------
|
| Relations are resolved by moox/core (RelationService + ConfigRelationManager).
| Wire product ↔ productgroup assignments in the application config/product.php
| when both packages are installed.
|
*/
return [
    'readonly' => false,

    'resources' => [
        'product' => [
            'single' => 'trans//product::product.product',
            'plural' => 'trans//product::product.products',

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
        'simple' => 'trans//product::product.type_simple',
        'variant' => 'trans//product::product.type_variant',
        'bundle' => 'trans//product::product.type_bundle',
        'service' => 'trans//product::product.type_service',
    ],

    'navigation_group' => 'trans//core::core.cms',

    'statuses' => [
        'draft' => 'trans//product::product.status_draft',
        'active' => 'trans//product::product.status_active',
        'inactive' => 'trans//product::product.status_inactive',
        'archived' => 'trans//product::product.status_archived',
    ],

    'currency' => 'EUR',
];
