<?php

use App\Models\User;
use Moox\Category\Models\Category;
use Moox\Category\Moox\Entities\Categories\Category\CategoryResource;
use Moox\Draft\Models\Draft;
use Moox\Media\Resources\MediaResource;
use Moox\Tag\Forms\TaxonomyCreateForm;
use Moox\Tag\Models\Tag;
use Moox\Tag\Resources\TagResource;

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
        'draft' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */
            'single' => 'trans//draft::draft.draft',
            'plural' => 'trans//draft::draft.drafts',

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
                'media' => [
                    'enabled' => true,
                    'resource' => MediaResource::class,
                    'origin' => 'media',
                    'boundary' => 'private',
                    'label' => 'Media Private',
                ],
                'media_public' => [
                    'enabled' => true,
                    'resource' => MediaResource::class,
                    'origin' => 'media',
                    'boundary' => 'public',
                    'label' => 'Media Public',
                ],
                'tag' => [
                    'enabled' => true,
                    'resource' => TagResource::class,
                ],
                'category' => [
                    'enabled' => true,
                    'resource' => CategoryResource::class,
                    'origin' => 'category',
                    'boundary' => 'private',
                    'label' => 'Category Private',
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
            'createForm' => Moox\Category\Moox\Entities\Categories\Category\Forms\TaxonomyCreateForm::class,
            'hierarchical' => true,
        ],
        'tag' => [
            'label' => 'trans//core::core.tag',
            'model' => Tag::class,
            'table' => 'taggables',
            'relationship' => 'taggable',
            'foreignKey' => 'taggable_id',
            'relatedKey' => 'tag_id',
            'createForm' => TaxonomyCreateForm::class,
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
        User::class => [
            'title_attribute' => 'name',
            'label' => 'App User',
        ],
        Moox\User\Models\User::class => [
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

    'scope_registry' => [
        'origins' => [
            'draft' => Draft::class,
        ],
        'sources' => [
            'draft' => Draft::class,
        ],
    ],
];
