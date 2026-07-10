<?php

use Moox\Static\Models\StaticEntry;

/*
| Moox Configuration
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
    | Readonly
    |
    | When true, create/edit/delete actions are disabled in the admin UI.
    |
    */
    'readonly' => false,

    'resources' => [
        'static_entry' => [

            /*
            | Title
            |
            | The translatable title of the Resource in singular and plural.
            |
            */
            'single' => 'trans//static::static.static_entry',
            'plural' => 'trans//static::static.static_entries',

            /*
            | <Tabs></Tabs>
            |
            | Define the tabs for the Resource table.
            |
            */
            'tabs' => [
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
            ],

            'scopes' => [
                'allowed' => [],
                'registry' => [
                    'sources' => [
                        'static_entry' => StaticEntry::class,
                    ],
                ],
            ],
        ],
    ],

    /*
    | Navigation
    |
    | The navigation group and sort of the Resource.
    |
    */
    'navigation_group' => 'DEV',
];
