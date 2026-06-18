<?php

use Moox\Category\Models\Category;
use Moox\Category\Models\CategoryTranslation;
use Moox\Category\Resources\CategoryResource;
use Moox\Media\Resources\MediaResource;

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
    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | The following configuration is done per Filament resource.
    |
    */

    'resources' => [
        'category' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//category::category.category',
            'plural' => 'trans//category::category.categories',

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
                    'media' => [
                        'resource' => MediaResource::class,
                    ],
                ],
                'registry' => [
                    'origins' => [
                        'category' => Category::class,
                    ],
                    'sources' => [
                        'category' => Category::class,
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation Group
    |--------------------------------------------------------------------------
    |
    | The translatable title of the navigation group in the
    | Filament Admin Panel. Instead of a translatable
    | string, you may also use a simple string.
    |
    */

    'navigation_group' => 'trans//core::core.cms',

    /*
    |--------------------------------------------------------------------------
    | Audit defaults
    |--------------------------------------------------------------------------
    |
    | Registered with moox/audit when installed. Override in config/audit.php.
    |
    */

    'audit' => [
        'enabled' => true,
        'models' => [
            Category::class => [
                'preset' => 'draft_main',
                'log_name' => 'category',
                'attributes' => [
                    'is_active',
                    'status',
                    'scope',
                    'parent_id',
                    'color',
                    'weight',
                ],
            ],
            CategoryTranslation::class => [
                'preset' => 'draft_translation',
                'log_name' => 'category',
                'attributes' => [
                    'title',
                    'slug',
                    'description',
                    'content',
                    'translation_status',
                    'author_id',
                    'author_type',
                ],
            ],
        ],
        'hooks' => [
            Category::class => [
                'deleting' => [
                    'handler' => 'categorizables_detached',
                    'log_name' => 'category',
                    'entry_type' => 'log',
                    'event' => 'categorizables_detached',
                    'description' => 'categorizables_detached',
                ],
            ],
        ],
        'filament' => [
            CategoryResource::class => [
                'owner_model' => Category::class,
                'aggregate_subjects' => [
                    CategoryTranslation::class => 'translations',
                ],
            ],
        ],
    ],

];
