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
    'single' => 'trans//data::static-countries-static-currencies.static_countries_static_currencies',
    'plural' => 'trans//data::static-countries-static-currencies.static_countries_static_currencies',
    'tabs' => [
        'all' => [
            'label' => 'trans//core::core.all',
            'icon' => 'gmdi-filter-list',
            'query' => [],
        ],
    ],
    'relations' => [],
    'taxonomies' => [],
];
