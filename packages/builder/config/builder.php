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

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | The following configuration is done per Filament resource.
    |
    */

    'resources' => [
        'item' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//builder::translations.item',
            'plural' => 'trans//builder::translations.items',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Builder table. They are optional, but
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
                'published' => [
                    'label' => 'trans//core::core.published',
                    'icon' => 'gmdi-check-circle',
                    'query' => [
                        [
                            'field' => 'publish_at',
                            'operator' => '<=',
                            'value' => function () {
                                return now();
                            },
                        ],
                        [
                            'field' => 'deleted_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                    ],
                ],
                'scheduled' => [
                    'label' => 'trans//core::core.scheduled',
                    'icon' => 'gmdi-schedule',
                    'query' => [
                        [
                            'field' => 'publish_at',
                            'operator' => '>',
                            'value' => function () {
                                return now();
                            },
                        ],
                        [
                            'field' => 'deleted_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                    ],
                ],
                'draft' => [
                    'label' => 'trans//core::core.draft',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'publish_at',
                            'operator' => '=',
                            'value' => null,
                        ],
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

            /*
            |--------------------------------------------------------------------------
            | Taxonomies
            |--------------------------------------------------------------------------
            |
            | This array contains the taxonomies that should be shown.
            | This is work in progress and not yet fully documented.
            |
            */

            'taxonomies' => [
                'categories' => [
                    'label' => 'Categories',
                    'model' => \Moox\Category\Models\Category::class,
                    'table' => 'categorizables',
                    'relationship' => 'categorizable',
                    'foreignKey' => 'categorizable_id',
                    'relatedKey' => 'category_id',
                    'createForm' => \Moox\Category\Forms\TaxonomyCreateForm::class,
                    'hierarchical' => true,
                ],
                'tags' => [
                    'label' => 'Tags',
                    'model' => \Moox\Tag\Models\Tag::class,
                    'table' => 'taggables',
                    'relationship' => 'taggable',
                    'foreignKey' => 'taggable_id',
                    'relatedKey' => 'tag_id',
                    'createForm' => \Moox\Tag\Forms\TaxonomyCreateForm::class,
                ],
            ],
        ],
        'full-item' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//builder::translations.full-item',
            'plural' => 'trans//builder::translations.full-items',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Builder table. They are optional, but
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
                'published' => [
                    'label' => 'trans//core::core.published',
                    'icon' => 'gmdi-check-circle',
                    'query' => [
                        [
                            'field' => 'publish_at',
                            'operator' => '<=',
                            'value' => function () {
                                return now();
                            },
                        ],
                        [
                            'field' => 'deleted_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                    ],
                ],
                'scheduled' => [
                    'label' => 'trans//core::core.scheduled',
                    'icon' => 'gmdi-schedule',
                    'query' => [
                        [
                            'field' => 'publish_at',
                            'operator' => '>',
                            'value' => function () {
                                return now();
                            },
                        ],
                        [
                            'field' => 'deleted_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                    ],
                ],
                'draft' => [
                    'label' => 'trans//core::core.draft',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'publish_at',
                            'operator' => '=',
                            'value' => null,
                        ],
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

            /*
            |--------------------------------------------------------------------------
            | Taxonomies
            |--------------------------------------------------------------------------
            |
            | This array contains the taxonomies that should be shown.
            | This is work in progress and not yet fully documented.
            |
            */

            'taxonomies' => [
                'categories' => [
                    'label' => 'Categories',
                    'model' => \Moox\Category\Models\Category::class,
                    'table' => 'categorizables',
                    'relationship' => 'categorizable',
                    'foreignKey' => 'categorizable_id',
                    'relatedKey' => 'category_id',
                    'createForm' => \Moox\Category\Forms\TaxonomyCreateForm::class,
                    'hierarchical' => true,
                ],
                'tags' => [
                    'label' => 'Tags',
                    'model' => \Moox\Tag\Models\Tag::class,
                    'table' => 'taggables',
                    'relationship' => 'taggable',
                    'foreignKey' => 'taggable_id',
                    'relatedKey' => 'tag_id',
                    'createForm' => \Moox\Tag\Forms\TaxonomyCreateForm::class,
                ],
            ],
        ],
        'simple-item' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//builder::translations.simple-item',
            'plural' => 'trans//builder::translations.simple-items',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Builder table. They are optional, but
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
                'published' => [
                    'label' => 'trans//core::core.published',
                    'icon' => 'gmdi-check-circle',
                    'query' => [
                        [
                            'field' => 'publish_at',
                            'operator' => '<=',
                            'value' => function () {
                                return now();
                            },
                        ],
                        [
                            'field' => 'deleted_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                    ],
                ],
                'scheduled' => [
                    'label' => 'trans//core::core.scheduled',
                    'icon' => 'gmdi-schedule',
                    'query' => [
                        [
                            'field' => 'publish_at',
                            'operator' => '>',
                            'value' => function () {
                                return now();
                            },
                        ],
                        [
                            'field' => 'deleted_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                    ],
                ],
                'draft' => [
                    'label' => 'trans//core::core.draft',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'publish_at',
                            'operator' => '=',
                            'value' => null,
                        ],
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

            /*
            |--------------------------------------------------------------------------
            | Taxonomies
            |--------------------------------------------------------------------------
            |
            | This array contains the taxonomies that should be shown.
            | This is work in progress and not yet fully documented.
            |
            */

            'taxonomies' => [
                'categories' => [
                    'label' => 'Categories',
                    'model' => \Moox\Category\Models\Category::class,
                    'table' => 'categorizables',
                    'relationship' => 'categorizable',
                    'foreignKey' => 'categorizable_id',
                    'relatedKey' => 'category_id',
                    'createForm' => \Moox\Category\Forms\TaxonomyCreateForm::class,
                    'hierarchical' => true,
                ],
                'tags' => [
                    'label' => 'Tags',
                    'model' => \Moox\Tag\Models\Tag::class,
                    'table' => 'taggables',
                    'relationship' => 'taggable',
                    'foreignKey' => 'taggable_id',
                    'relatedKey' => 'tag_id',
                    'createForm' => \Moox\Tag\Forms\TaxonomyCreateForm::class,
                ],
            ],
        ],
        'simple-taxonomy' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//builder::translations.simple-taxonomy',
            'plural' => 'trans//builder::translations.simple-taxonomies',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Builder table. They are optional, but
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
                'published' => [
                    'label' => 'trans//core::core.published',
                    'icon' => 'gmdi-check-circle',
                    'query' => [
                        [
                            'field' => 'publish_at',
                            'operator' => '<=',
                            'value' => function () {
                                return now();
                            },
                        ],
                        [
                            'field' => 'deleted_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                    ],
                ],
                'scheduled' => [
                    'label' => 'trans//core::core.scheduled',
                    'icon' => 'gmdi-schedule',
                    'query' => [
                        [
                            'field' => 'publish_at',
                            'operator' => '>',
                            'value' => function () {
                                return now();
                            },
                        ],
                        [
                            'field' => 'deleted_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                    ],
                ],
                'draft' => [
                    'label' => 'trans//core::core.draft',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'publish_at',
                            'operator' => '=',
                            'value' => null,
                        ],
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

            /*
            |--------------------------------------------------------------------------
            | Taxonomies
            |--------------------------------------------------------------------------
            |
            | This array contains the taxonomies that should be shown.
            | This is work in progress and not yet fully documented.
            |
            */

            'taxonomies' => [
                'categories' => [
                    'label' => 'Categories',
                    'model' => \Moox\Category\Models\Category::class,
                    'table' => 'categorizables',
                    'relationship' => 'categorizable',
                    'foreignKey' => 'categorizable_id',
                    'relatedKey' => 'category_id',
                    'createForm' => \Moox\Category\Forms\TaxonomyCreateForm::class,
                    'hierarchical' => true,
                ],
                'tags' => [
                    'label' => 'Tags',
                    'model' => \Moox\Tag\Models\Tag::class,
                    'table' => 'taggables',
                    'relationship' => 'taggable',
                    'foreignKey' => 'taggable_id',
                    'relatedKey' => 'tag_id',
                    'createForm' => \Moox\Tag\Forms\TaxonomyCreateForm::class,
                ],
            ],
        ],
        'nested-taxonomy' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//builder::translations.nested-taxonomy',
            'plural' => 'trans//builder::translations.nested-taxonomies',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Builder table. They are optional, but
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
                'published' => [
                    'label' => 'trans//core::core.published',
                    'icon' => 'gmdi-check-circle',
                    'query' => [
                        [
                            'field' => 'publish_at',
                            'operator' => '<=',
                            'value' => function () {
                                return now();
                            },
                        ],
                        [
                            'field' => 'deleted_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                    ],
                ],
                'scheduled' => [
                    'label' => 'trans//core::core.scheduled',
                    'icon' => 'gmdi-schedule',
                    'query' => [
                        [
                            'field' => 'publish_at',
                            'operator' => '>',
                            'value' => function () {
                                return now();
                            },
                        ],
                        [
                            'field' => 'deleted_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                    ],
                ],
                'draft' => [
                    'label' => 'trans//core::core.draft',
                    'icon' => 'gmdi-text-snippet',
                    'query' => [
                        [
                            'field' => 'publish_at',
                            'operator' => '=',
                            'value' => null,
                        ],
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

            /*
            |--------------------------------------------------------------------------
            | Taxonomies
            |--------------------------------------------------------------------------
            |
            | This array contains the taxonomies that should be shown.
            | This is work in progress and not yet fully documented.
            |
            */

            'taxonomies' => [
                'categories' => [
                    'label' => 'Categories',
                    'model' => \Moox\Category\Models\Category::class,
                    'table' => 'categorizables',
                    'relationship' => 'categorizable',
                    'foreignKey' => 'categorizable_id',
                    'relatedKey' => 'category_id',
                    'createForm' => \Moox\Category\Forms\TaxonomyCreateForm::class,
                    'hierarchical' => true,
                ],
                'tags' => [
                    'label' => 'Tags',
                    'model' => \Moox\Tag\Models\Tag::class,
                    'table' => 'taggables',
                    'relationship' => 'taggable',
                    'foreignKey' => 'taggable_id',
                    'relatedKey' => 'tag_id',
                    'createForm' => \Moox\Tag\Forms\TaxonomyCreateForm::class,
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

    'navigation_group' => 'trans//builder::translations.builder',

    /*
    |--------------------------------------------------------------------------
    | Navigation Sort
    |--------------------------------------------------------------------------
    |
    | This value is the sort order of the navigation item in the
    | Filament Admin Panel. If you use a bunch of Moox
    | plugins, everything should be in order.
    |
    */

    'navigation_sort' => 9990,

    /*
    |--------------------------------------------------------------------------
    | Item Types
    |--------------------------------------------------------------------------
    |
    | This array contains the types of items entities. You can delete
    | the types you don't need and add new ones. If you don't need
    | types, you can empty this array like this: 'types' => [],
    |
    */

    'types' => [
        'post' => 'Post',
        'page' => 'Page',
    ],

    /*
    |--------------------------------------------------------------------------
    | Author Model
    |--------------------------------------------------------------------------
    |
    | This sets the user model that can be used as author. It should be an
    | authenticatable model and support the morph relationship.
    | It should have fields similar to Moox User or WpUser.
    |
    */

    'author_model' => \Moox\User\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Allow Slug Change - WIP
    |--------------------------------------------------------------------------
    |
    | // TODO: Work in progress.
    |
    */

    'allow_slug_change_after_saved' => env('ALLOW_SLUG_CHANGE_AFTER_SAVED', true),
    'allow_slug_change_after_publish' => env('ALLOW_SLUG_CHANGE_AFTER_PUBLISH', false),

    /*
    |--------------------------------------------------------------------------
    | Contexts
    |--------------------------------------------------------------------------
    |
    | Define the available build contexts and their configurations.
    | Each context can have its own path and namespace settings.
    |
    */
    'contexts' => [
        'app' => [
            'class' => \Moox\Builder\Contexts\AppContext::class,
            'base_path' => app_path(),
            'base_namespace' => 'App',
            'paths' => [
                'model' => 'Models',
                'resource' => 'Filament\Resources',
                'migration' => 'database\migrations',
            ],
        ],
        'package' => [
            'class' => \Moox\Builder\Contexts\PackageContext::class,
            'paths' => [
                'model' => 'src\Models',
                'resource' => 'src\Resources',
                'migration' => 'database\migrations',
            ],
        ],
        'preview' => [
            'class' => \Moox\Builder\Contexts\PreviewContext::class,
            'base_path' => app_path('Builder'),
            'base_namespace' => 'App\\Builder',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Templates
    |--------------------------------------------------------------------------
    |
    | Define the templates used by generators. You can override these
    | with your own templates by specifying the full path.
    |
    */
    'templates' => [
        'model' => [
            'path' => __DIR__.'/../src/Templates/model.php.stub',
        ],
        'migration' => [
            'path' => __DIR__.'/../src/Templates/migration.php.stub',
        ],
        'resource' => [
            'path' => __DIR__.'/../src/Templates/resource.php.stub',
        ],
        'plugin' => [
            'path' => __DIR__.'/../src/Templates/plugin.php.stub',
        ],
        'resource-List' => [
            'path' => __DIR__.'/../src/Templates/pages/list.php.stub',
        ],
        'resource-Create' => [
            'path' => __DIR__.'/../src/Templates/pages/create.php.stub',
        ],
        'resource-Edit' => [
            'path' => __DIR__.'/../src/Templates/pages/edit.php.stub',
        ],
        'resource-View' => [
            'path' => __DIR__.'/../src/Templates/pages/view.php.stub',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Generators
    |--------------------------------------------------------------------------
    |
    | Configure the generators and their specific settings.
    | Each generator can have its own configuration.
    |
    */
    'generators' => [
        'model' => [
            'class' => \Moox\Builder\Generators\ModelGenerator::class,
            'template' => 'model',
        ],
        'migration' => [
            'class' => \Moox\Builder\Generators\MigrationGenerator::class,
            'template' => 'migration',
        ],
        'resource' => [
            'class' => \Moox\Builder\Generators\ResourceGenerator::class,
            'template' => 'resource',
        ],
        'plugin' => [
            'class' => \Moox\Builder\Generators\PluginGenerator::class,
            'template' => 'plugin',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Presets
    |--------------------------------------------------------------------------
    |
    | Register available presets that can be used to quickly scaffold resources.
    | Each preset key must match the class name in lowercase without 'Preset'.
    |
    */

    'presets' => [
        'simple-item' => [
            'class' => \Moox\Builder\Presets\SimpleItemPreset::class,
            'generators' => ['model', 'migration', 'resource'],
            'features' => [],
        ],
        'publishable-item' => [
            'class' => \Moox\Builder\Presets\PublishableItemPreset::class,
            'generators' => ['model', 'migration', 'resource'],
            'features' => ['publish', 'softdelete'],
        ],
        'full-item' => [
            'class' => \Moox\Builder\Presets\FullItemPreset::class,
            'generators' => ['model', 'migration', 'resource', 'factory'],
            'features' => ['publish', 'softdelete', 'author'],
        ],
        'simple-taxonomy' => [
            'class' => \Moox\Builder\Presets\SimpleTaxonomyPreset::class,
            'generators' => ['model', 'migration', 'resource'],
            'features' => ['taxonomy'],
        ],
        'nested-taxonomy' => [
            'class' => \Moox\Builder\Presets\NestedTaxonomyPreset::class,
            'generators' => ['model', 'migration', 'resource'],
            'features' => ['nested-taxonomy'],
        ],
    ],
];
