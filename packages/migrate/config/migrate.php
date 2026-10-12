<?php

/*
|--------------------------------------------------------------------------
| moox Configuration
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
        'migration' => [
            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */
            'single' => 'trans//migrate::migrate.migration',
            'plural' => 'trans//migrate::migrate.migrations',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Resource table. They are optional.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */
            'tabs' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    |
    | The navigation group of the Resource.
    |
    */
    'navigation_group' => 'trans//core::core.system',

    /*
    |--------------------------------------------------------------------------
    | Audit logging
    |--------------------------------------------------------------------------
    |
    | When moox/audit is installed, each migrate run is written as an activity
    | log entry (user, outcome, exit code, pending count). Disable to skip.
    |
    */
    'audit' => [
        'enabled' => env('MOOX_MIGRATE_AUDIT', true),
        'log_name' => env('MOOX_MIGRATE_AUDIT_LOG', 'migrate'),
    ],
];
