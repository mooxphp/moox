<?php

use Moox\Item\Models\Item;
use Moox\Item\Resources\ItemResource;

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
        'item' => [
            'single' => 'trans//item::item.item',
            'plural' => 'trans//item::item.items',
            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [
                        [
                            'field' => 'title',
                            'operator' => '!=',
                            'value' => null,
                        ],
                    ],
                ],
            ],

            // No scopes.allowed: Item has no taxonomies/tags/media scoping (dev skeleton).
            'scopes' => [
                'registry' => [
                    'sources' => [
                        'item' => Item::class,
                    ],
                ],
            ],
        ],
    ],

    'relations' => [],

    /*
    |--------------------------------------------------------------------------
    | Audit defaults
    |--------------------------------------------------------------------------
    |
    | Empty model/resource entries use moox/audit defaults: fillable attributes
    | (minus noise like custom_properties), standard CRUD events, log_name from
    | getResourceName(), and owner_model from the Filament resource.
    |
    */

    'audit' => [
        'models' => [
            Item::class => [],
        ],
        'filament' => [
            ItemResource::class => [],
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
    'auth' => [
        'user' => 'Moox\\DevTools\\Models\\TestUser',
    ],
    'navigation_group' => 'DEV',
];
