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

    'audit' => [
        'enabled' => true,
        'models' => [
            Item::class => [
                'log_name' => 'item',
                'entry_type' => 'audit',
                'attributes' => [
                    'title',
                    'description',
                ],
                'events' => ['created', 'updated', 'deleted'],
            ],
        ],
        'filament' => [
            ItemResource::class => [
                'owner_model' => Item::class,
            ],
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
