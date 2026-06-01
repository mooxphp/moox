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
        
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    |
    | The navigation group of the Resource,
    | and if the panel is enabled.
    |
    */
    'navigation_group' => 'trans//core::core.system',
    'enable-panel' => false,

];
