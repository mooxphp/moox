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
    | The navigation group of the Resource,
    | and if the panel is enabled.
    |
    */
    'navigation_group' => 'trans//core::core.system',
    'enable-panel' => false,

    /*
    |--------------------------------------------------------------------------
    | Language Selector
    |--------------------------------------------------------------------------
    |
    | Configuration for the language selector component.
    |
    */
    /*
    | Defaults for display_name (language selector labels). Can be overridden per
    | localization row in language_settings. Does NOT control flags — see use_country_icon.
    |
    | See README: Language selector section
    */
    'language_selector' => [
        // Native name (Deutsch) vs English name (German)
        'use_native_names' => true,
        // Append country from locale_variant: Deutsch (Schweiz) for de_CH
        'show_regional_variants' => true,
        // Translated country in parentheses: Schweiz vs Switzerland
        'use_country_translations' => true,
    ],

];
