<?php

use Moox\ProductCategory\Resources\ProductCategoryResource;

/*
|--------------------------------------------------------------------------
| Moox Configuration
|--------------------------------------------------------------------------
|
| Relations are resolved by moox/core (RelationService + ConfigRelationManager).
| Wire product ↔ productcategory assignments in the application config when both
| packages are installed.
|
*/
return [
    'readonly' => false,

    'resources' => [
        'productcategory' => [
            'single' => 'trans//productcategory::productcategory.productcategory',
            'plural' => 'trans//productcategory::productcategory.productcategories',
        ],
    ],

    'related_morph_defaults' => [
        'display_columns' => ['title'],
        'translation_prefix' => 'productcategory::fields',
        'related_resource' => ProductCategoryResource::class,
        'record_select_search_columns' => ['external_reference'],
    ],

    'navigation_group' => 'trans//core::core.cms',
];
