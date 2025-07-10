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

    'single' => 'trans//news::news.news_single',
    'plural' => 'trans//news::news.news_plural',
    'tabs' => [
        'all' => [
            'label' => 'trans//core::core.all',
            'icon' => 'gmdi-filter-list',
            'query' => [
            ],
        ],
        '0' => [
            'label' => 'Post',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'type',
                    'operator' => '=',
                    'value' => 'Post',
                ],
            ],
        ],
        '1' => [
            'label' => 'Page',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'type',
                    'operator' => '=',
                    'value' => 'Page',
                ],
            ],
        ],
    ],
    'relations' => [],
    'taxonomies' => [
        'category' => [
            'label' => 'Categories',
            'model' => \Moox\Category\Models\Category::class,
            'table' => 'categorizables',
            'relationship' => 'categorizable',
            'foreignKey' => 'categorizable_id',
            'relatedKey' => 'category_id',
            'createForm' => \Moox\Category\Moox\Entities\Categories\Category\Forms\TaxonomyCreateForm::class,
            'hierarchical' => true,
        ],
    ],

    'rules' => [
        'excerpt' => 'max:30',
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    |
    | The navigation group and sort of the Resource,
    | and if the panel is enabled.
    |
    */
    'navigation_group' => 'DEV',
];
