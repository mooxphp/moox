<?php

/*
|--------------------------------------------------------------------------
| Moox Configuration
|--------------------------------------------------------------------------
|
| Relations are resolved by moox/core (RelationService + ConfigRelationManager).
| product_category_id and manufacturer_id stay on the product. The app config sets
| the belongs_to model for each, so another model can be wired without a schema change.
|
*/
return [
    'readonly' => false,

    'resources' => [
        'product' => [
            'single' => 'trans//product::product.product',
            'plural' => 'trans//product::product.products',
        ],
        'variant' => [
            'single' => 'trans//product::variant.variant',
            'plural' => 'trans//product::variant.variants',
        ],
    ],

    'navigation_group' => 'trans//core::core.cms',
];
