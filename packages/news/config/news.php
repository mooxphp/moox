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
        'news' => [
            /*
           |--------------------------------------------------------------------------
           | Title
           |--------------------------------------------------------------------------
           |
           | The translatable title of the Resource in singular and plural.
           |
           */

            'single' => 'trans//news::news.news',
            'plural' => 'trans//news::news.news',

            /*
           |--------------------------------------------------------------------------
           | <Tabs></Tabs>
           |--------------------------------------------------------------------------
           |
           | Define the tabs for the Resource table. They are optional, but
           | pretty awesome to filter the table by certain values.
           | You may simply do a 'tabs' => [], to disable them.
           |
           */

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
    'taxonomies' => [
        'category' => [
            'label' => 'trans//core::core.category',
            'model' => \Moox\Category\Models\Category::class,
            'table' => 'categorizables',
            'relationship' => 'categorizable',
            'foreignKey' => 'categorizable_id',
            'relatedKey' => 'category_id',
            'createForm' => \Moox\Category\Moox\Entities\Categories\Category\Forms\TaxonomyCreateForm::class,
            'hierarchical' => true,
        ],
        'tag' => [
            'label' => 'trans//core::core.tag',
            'model' => \Moox\Tag\Models\Tag::class,
            'table' => 'taggables',
            'relationship' => 'taggable',
            'foreignKey' => 'taggable_id',
            'relatedKey' => 'tag_id',
            'createForm' => \Moox\Tag\Forms\TaxonomyCreateForm::class,
            'hierarchical' => false,
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
    'navigation_group' => 'CMS',
];
