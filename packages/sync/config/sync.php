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

    'sync' => [

        /*
        |--------------------------------------------------------------------------
        | Title
        |--------------------------------------------------------------------------
        |
        | The translatable title of the Resource in singular and plural.
        |
        */

        'single' => 'trans//core::sync.sync',
        'plural' => 'trans//core::sync.syncs',

        /*
        |--------------------------------------------------------------------------
        | Tabs
        |--------------------------------------------------------------------------
        |
        | Define the tabs for the Expiry table. They are optional, but
        | pretty awesome to filter the table by certain values.
        | You may simply do a 'tabs' => [], to disable them.
        |
        */

        'tabs' => [
            'all' => [
                'label' => 'trans//core::core.all',
                'icon' => 'gmdi-filter-list',
                'query' => [],
            ],
            /*
            'error' => [
                'label' => 'trans//core::core.error',
                'icon' => 'gmdi-text-snippet',
                'query' => [
                    [
                        'field' => 'subject_type',
                        'operator' => '=',
                        'value' => 'Error',
                    ],
                ],
            ],
            */
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

    'navigation_group' => 'trans//core::core.tools',

    /*
    |--------------------------------------------------------------------------
    | Sync - Navigation Sort
    |--------------------------------------------------------------------------
    |
    | This values are the sort order of the navigation items in the
    | Filament Admin Panel. If you use a bunch of Moox
    | plugins, everything should be in order.
    |
    */

    'navigation_sort' => 9500,
];
