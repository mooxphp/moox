<?php

use Moox\Address\Models\Address;
use Moox\Address\Models\Addressable;
use Moox\Category\Resources\CategoryResource;
use Moox\Media\Resources\MediaResource;
use Moox\Tag\Resources\TagResource;
use Moox\User\Models\User;
use Moox\User\Resources\UserResource;

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
        'address' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */
            'single' => 'trans//address::address.address',
            'plural' => 'trans//address::address.addresses',

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
                    'tag' => [
                        'resource' => TagResource::class,
                    ],
                    'category' => [
                        'resource' => CategoryResource::class,
                    ],
                    'user' => [
                        'resource' => UserResource::class,
                    ],
                ],
                'registry' => [
                    'sources' => [
                        'address' => Address::class,
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Relations (registry)
    |--------------------------------------------------------------------------
    |
    | Declarative list of notable Eloquent relations for this entity.
    | Pivot roles (billing, postal, delivery) live on addressables, not on
    | addresses. Register owner_types when Company / Contact packages exist.
    |
    */
    'relations' => [
        'addressables' => [
            'label' => 'trans//address::fields.assignments',
            'relationship' => 'addressables',
            'pivot_model' => Addressable::class,
            'pivot_table' => 'addressables',
            'morph_name' => 'addressable',
            'pivot_columns' => [
                'billing_address',
                'postal_address',
                'delivery_address',
            ],
            'owner_types' => [
                // 'Moox\Company\Models\Company' => [
                //     'label' => 'Company',
                //     'title_attribute' => 'display_name',
                // ],
                // 'Moox\Contact\Models\Contact' => [
                //     'label' => 'Contact',
                //     'title_attribute' => 'display_name',
                // ],
            ],
        ],
    ],

    'taxonomies' => [
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
    'navigation_group' => 'Portal',

];
