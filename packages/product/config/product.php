<?php

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

    'relations' => [],

    'taxonomies' => [],

    'navigation_group' => 'trans//core::core.cms',

    'statuses' => [
        'draft' => 'trans//product::product.status_draft',
        'active' => 'trans//product::product.status_active',
        'inactive' => 'trans//product::product.status_inactive',
        'archived' => 'trans//product::product.status_archived',
    ],
];
