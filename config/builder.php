<?php

use Moox\Builder\Presets\LightItemPreset;
use Moox\Builder\Presets\SimpleItemPreset;
use Moox\Builder\Presets\SoftDeleteItemPreset;
use Moox\Builder\Presets\FullItemPreset;
use App\Builder\Presets\StaticLanguagePreset;
use Moox\Category\Models\Category;
use Moox\Category\Forms\TaxonomyCreateForm;
use Moox\Tag\Models\Tag;
use Moox\Room\Models\Room;
use Moox\Brand\Forms\RelationCreateForm;
use Moox\Booking\Models\Booking;
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

    'presets' => [
        'light-item' => [
            'class' => LightItemPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'simple-item' => [
            'class' => SimpleItemPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'soft-delete-item' => [
            'class' => SoftDeleteItemPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'full-item' => [
            'class' => FullItemPreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
        'static-language' => [
            'class' => StaticLanguagePreset::class,
            'generators' => ['model', 'migration', 'resource'],
        ],
    ],

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
                            'value' => fn() => now(),
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
                            'value' => fn() => now(),
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
                    'model' => Category::class,
                    'table' => 'categorizables',
                    'relationship' => 'categorizable',
                    'foreignKey' => 'categorizable_id',
                    'relatedKey' => 'category_id',
                    'createForm' => TaxonomyCreateForm::class,
                    'hierarchical' => true,
                ],
                'tags' => [
                    'label' => 'Tags',
                    'model' => Tag::class,
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
                            'value' => fn() => now(),
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
                            'value' => fn() => now(),
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
                    'model' => Category::class,
                    'table' => 'categorizables',
                    'relationship' => 'categorizable',
                    'foreignKey' => 'categorizable_id',
                    'relatedKey' => 'category_id',
                    'createForm' => TaxonomyCreateForm::class,
                    'hierarchical' => true,
                ],
                'tags' => [
                    'label' => 'Tags',
                    'model' => Tag::class,
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

            'tabs' => [],

            /*
            |--------------------------------------------------------------------------
            | Taxonomies
            |--------------------------------------------------------------------------
            |
            | This array contains the taxonomies that should be shown.
            | This is work in progress and not yet fully documented.
            |
            */

            'taxonomies' => [],
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
                    'model' => Category::class,
                    'table' => 'categorizables',
                    'relationship' => 'categorizable',
                    'foreignKey' => 'categorizable_id',
                    'relatedKey' => 'category_id',
                    'createForm' => TaxonomyCreateForm::class,
                    'hierarchical' => true,
                ],
                'tags' => [
                    'label' => 'Tags',
                    'model' => Tag::class,
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
                    'model' => Category::class,
                    'table' => 'categorizables',
                    'relationship' => 'categorizable',
                    'foreignKey' => 'categorizable_id',
                    'relatedKey' => 'category_id',
                    'createForm' => TaxonomyCreateForm::class,
                    'hierarchical' => true,
                ],
                'tags' => [
                    'label' => 'Tags',
                    'model' => Tag::class,
                    'table' => 'taggables',
                    'relationship' => 'taggable',
                    'foreignKey' => 'taggable_id',
                    'relatedKey' => 'tag_id',
                    'createForm' => \Moox\Tag\Forms\TaxonomyCreateForm::class,
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | Relations
            |--------------------------------------------------------------------------
            |
            | This array contains the relations that should be shown.
            | This is work in progress and not yet fully documented.
            |
            */

            'relations' => [
                'rooms' => [
                    'label' => 'Rooms',
                    'model' => Room::class,
                    'table' => 'roomables',
                    'type' => 'has-many',
                    'relationship' => 'roomable',
                    'foreignKey' => 'roomable_id',
                    'relatedKey' => 'room_id',
                    'createForm' => RelationCreateForm::class,
                    'hierarchical' => true,
                ],
                'bookings' => [
                    'label' => 'Bookings',
                    'model' => Booking::class,
                    'table' => 'bookings',
                    'type' => 'has-many',
                    'relationship' => 'bookable',
                    'foreignKey' => 'bookable_id',
                    'relatedKey' => 'booking_id',
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

    'user_model' => User::class,

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
];
