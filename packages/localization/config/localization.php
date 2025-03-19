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
    | Title
    |--------------------------------------------------------------------------
    |
    | The translatable title of the Resource in singular and plural.
    |
    */
    'single' => 'trans//localization::localization.localization',
    'plural' => 'trans//localization::localization.localizations',

    /*
    |--------------------------------------------------------------------------
    | Tabs
    |--------------------------------------------------------------------------
    |
    | The translatable title, icon and query of the Tabs.
    |
    */
    'tabs' => [
        'all' => [
            'label' => 'trans//core::core.all',
            'icon' => 'gmdi-filter-list',
            'query' => [],
        ],
        '0' => [
            'label' => 'LTR',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'type',
                    'operator' => '=',
                    'value' => 'LTR',
                ],
            ],
        ],
        '1' => [
            'label' => 'RTL',
            'icon' => 'gmdi-filter-list',
            'query' => [
                [
                    'field' => 'type',
                    'operator' => '=',
                    'value' => 'RTL',
                ],
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
    'navigation_group' => 'trans//core::core.system',
    'navigation_sort' => 100,
    'enable-panel' => true,

];
