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
| 'trans//core::common.all',
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

    'audit' => [

        /*
        |--------------------------------------------------------------------------
        | Title
        |--------------------------------------------------------------------------
        |
        | The translatable title of the Resource in singular and plural.
        |
        */

        'single' => 'trans//core::audit.audit',
        'plural' => 'trans//core::audit.audits',

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

    'navigation_group' => 'trans//core::common.system',

    /*
    |--------------------------------------------------------------------------
    | Audit - Navigation Sort
    |--------------------------------------------------------------------------
    |
    | This value is the sort order of the navigation item in the
    | Filament Admin Panel. If you use a bunch of Moox
    | plugins, everything should be in order.
    |
    */

    'navigation_sort' => 7500,

];
