<?php

use Moox\Category\Models\Category;
use Moox\Category\Moox\Entities\Categories\Category\CategoryResource;
use Moox\Category\Moox\Entities\Categories\Category\Forms\TaxonomyCreateForm;
use Moox\Record\Models\Record;
use Moox\Tag\Models\Tag;
use Moox\User\Models\User;

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
        'record' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */
            'single' => 'trans//record::record.record',
            'plural' => 'trans//record::record.records',

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

            'scopes' => [
                'allowed' => [
                    'category' => [
                        'enabled' => true,
                        'resource' => CategoryResource::class,
                    ],
                ],
                'registry' => [
                    'origins' => [
                        'record' => Record::class,
                    ],
                ],
            ],
        ],
    ],
    'relations' => [],
    'taxonomies' => [
        'category' => [
            'label' => 'trans//core::core.category',
            'model' => Category::class,
            'table' => 'categorizables',
            'relationship' => 'categorizable',
            'foreignKey' => 'categorizable_id',
            'relatedKey' => 'category_id',
            'createForm' => TaxonomyCreateForm::class,
            'hierarchical' => true,
        ],
        'tag' => [
            'label' => 'trans//core::core.tag',
            'model' => Tag::class,
            'table' => 'taggables',
            'relationship' => 'taggable',
            'foreignKey' => 'taggable_id',
            'relatedKey' => 'tag_id',
            'createForm' => Moox\Tag\Forms\TaxonomyCreateForm::class,
            'hierarchical' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Models
    |--------------------------------------------------------------------------
    |
    | The User model classes available for author relationships.
    | You can define multiple user types with their display attributes.
    |
    */
    'user_models' => [
        App\Models\User::class => [
            'title_attribute' => 'name',
            'label' => 'App User',
        ],
        User::class => [
            'title_attribute' => 'name',
            'label' => 'Moox User',
        ],
        // Add more user models as needed:
        // \My\Custom\AdminUser::class => [
        //     'title_attribute' => 'full_name',
        //     'label' => 'Admin User',
        // ],
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
