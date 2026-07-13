<?php

use Moox\Category\Forms\TaxonomyCreateForm;
use Moox\Category\Models\Category;
use Moox\Category\Resources\CategoryResource;
use Moox\Media\Resources\MediaResource;
use Moox\News\Resources\NewsResource;
use Moox\Page\Models\Page;
use Moox\Page\Models\PageTranslation;
use Moox\Page\Support\BlockContentRendererAdapter;
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
    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | Host applications can swap in subclasses via config('page.models.*').
    |
    */
    'models' => [
        'page' => Page::class,
        'page_translation' => PageTranslation::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Frontend
    |--------------------------------------------------------------------------
    */
    'frontend' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | The layout stored on pages.layout maps to a Blade view.
    |
    */
    'default_layout' => 'default',

    'layouts' => [
        'default' => [
            'label' => 'Standard',
            'view' => 'default.page',
        ],
        'heco-group' => [
            'label' => 'Heco Group',
            'view' => 'heco-group.page',
        ],
        'heco' => [
            'label' => 'Heco',
            'view' => 'heco.page',
        ],
        'heltec' => [
            'label' => 'Heltec',
            'view' => 'heltec.page',
        ],
        'hecoform' => [
            'label' => 'Hecoform',
            'view' => 'hecoform.page',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reserved slugs
    |--------------------------------------------------------------------------
    */
    'reserved_slugs' => [
        'admin',
        'up',
    ],

    /*
    |--------------------------------------------------------------------------
    | Content renderer
    |--------------------------------------------------------------------------
    |
    | Class implementing Moox\Page\Contracts\PageContentRenderer.
    |
    */
    'content_renderer' => BlockContentRendererAdapter::class,

    'readonly' => false,
    'resources' => [
        'page' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */
            'single' => 'trans//page::page.page',
            'plural' => 'trans//page::page.pages',

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
                    'news' => [
                        'resource' => NewsResource::class,
                    ],
                    'media' => [
                        'resource' => MediaResource::class,
                    ],
                    'tag' => [
                        'resource' => TagResource::class,
                    ],
                    'category' => [
                        'resource' => CategoryResource::class,
                    ],
                ],
                'registry' => [
                    'sources' => [
                        'page' => Page::class,
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
    'navigation_group' => 'CMS',

];
