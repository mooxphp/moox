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
        ],
    ],

    'related_morph_defaults' => [
        'display_columns' => ['title'],
        'translation_prefix' => 'productgroup::fields',
        'related_resource' => ProductGroupResource::class,
        'record_select_search_columns' => ['external_reference'],
    ],

    'navigation_group' => 'trans//core::core.cms',
];
