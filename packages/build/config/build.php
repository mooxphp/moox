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

    'package_templates' => [
        'skeleton' => [
            'name' => 'Moox Skeleton',
            'select' => 'No Entities, just an empty package.',
            'composer' => 'moox/skeleton',
            'website' => 'https://moox.org/docs/skeleton',
        ],
        'item' => [
            'name' => 'Moox Item',
            'select' => 'Simple entity for logs, settings, etc.',
            'composer' => 'moox/item',
            'website' => 'https://moox.org/docs/item',
        ],
        'record' => [
            'name' => 'Moox Record',
            'select' => 'Entity with soft delete, simple status and author.',
            'composer' => 'moox/record',
            'website' => 'https://moox.org/docs/record',
        ],
        'draft' => [
            'name' => 'Moox Draft',
            'select' => 'Entity with languages, publish, schedule and frontend.',
            'composer' => 'moox/draft',
            'website' => 'https://moox.org/docs/draft',
        ],
        'category' => [
            'name' => 'Moox Category',
            'select' => 'Taxonomy with nested set and parent-child relations.',
            'composer' => 'moox/category',
            'website' => 'https://moox.org/docs/category',
        ],
        'tag' => [
            'name' => 'Moox Tag',
            'select' => 'Simple Taxonomy with flat structure.',
            'composer' => 'moox/tag',
            'website' => 'https://moox.org/docs/tag',
        ],
        'module' => [
            'name' => 'Moox Module',
            'select' => 'Modules are able to extend existing entities.',
            'composer' => 'moox/module',
            'website' => 'https://moox.org/docs/module',
        ],
    ],

    'default_author' => [
        'name' => 'Moox',
        'email' => 'dev@moox.org',
    ],

    'default_namespace' => 'Moox',

    'default_packagist' => 'moox',

    'package_path' => 'packages',
];
