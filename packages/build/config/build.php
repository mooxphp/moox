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
            'name' => 'Empty Package',
            'select' => 'No Entities, just an empty package',
            'motivation' => 'Easy!',
            'emoji' => 'emojiSmile',
            'subject' => 'Package',
            'sentence' => 'using Moox Skeleton.',
            'path' => 'packages/skeleton',
        ],
        'item' => [
            'name' => 'Simple Item',
            'select' => 'Moox Simple Item - entity with simple fields',
            'motivation' => 'Cool!',
            'emoji' => 'emojiCool',
            'subject' => 'Item',
            'sentence' => 'with simple fields.',
            'path' => 'packages/item',
        ],
        'item_archive' => [
            'name' => 'Archive Item',
            'select' => 'Moox Archive Item - entity with soft delete',
            'motivation' => 'Great!',
            'emoji' => 'emojiParty',
            'subject' => 'Item',
            'sentence' => 'with Soft Delete.',
            'path' => 'packages/item-archive',
        ],
        'item_publish' => [
            'name' => 'Publish Item',
            'select' => 'Moox Publish Item - entity with publish feature',
            'motivation' => 'Wheew!',
            'emoji' => 'emojiRocket',
            'subject' => 'Item',
            'sentence' => 'with Publish feature.',
            'path' => 'packages/item-publish',
        ],
        'taxonomy' => [
            'name' => 'Simple Taxonomy',
            'select' => 'Moox Simple Taxonomy - a flat taxonomy',
            'motivation' => 'Cool!',
            'emoji' => 'emojiCool',
            'subject' => 'Taxonomy',
            'sentence' => 'for tagging.',
            'path' => 'packages/taxonomy',
        ],
        'nested_taxonomy' => [
            'name' => 'Nested Taxonomy',
            'select' => 'Moox Nested Taxonomy - a hierarchical taxonomy',
            'motivation' => 'Great!',
            'emoji' => 'emojiParty',
            'subject' => 'Taxonomy',
            'sentence' => 'with Nested Set.',
            'path' => 'packages/nested-taxonomy',
        ],
        'module' => [
            'name' => 'Module',
            'select' => 'Moox Module - to extend an existing entity',
            'motivation' => 'Wheew!',
            'emoji' => 'emojiRocket',
            'subject' => 'Module',
            'sentence' => 'that provides additional fields.',
            'path' => 'packages/module',
        ],
        'theme' => [
            'name' => 'Theme',
            'select' => 'Moox Theme - to style the Frontend',
            'motivation' => 'Stylish!',
            'emoji' => 'emojiRainbow',
            'subject' => 'Theme',
            'sentence' => 'and create an awesome Website.',
            'path' => 'packages/theme-base',
        ],
    ],

    'default_author' => [
        'name' => 'Moox',
        'email' => 'devs@moox.org',
    ],

    'default_namespace' => 'Moox',

    'default_packagist' => 'moox',
];
