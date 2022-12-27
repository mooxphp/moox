<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Icons Sets
    |--------------------------------------------------------------------------
    |
    | With this config option you can define a couple of
    | default icon sets. Provide a key name for your icon
    | set and a combination from the options below.
    |
    */

    'sets' => [

        'default' => [

            /*
            |-----------------------------------------------------------------
            | Icons Path
            |-----------------------------------------------------------------
            |
            | Provide the relative path from your app root to your SVG icons
            | directory. Icons are loaded recursively so there's no need to
            | list every sub-directory.
            |
            | Relative to the disk root when the disk option is set.
            |
            */

            'path' => 'vendor/usetall/tallui-web-icons/resources/svg/black',

            /*
            |-----------------------------------------------------------------
            | Filesystem Disk
            |-----------------------------------------------------------------
            |
            | Optionally, provide a specific filesystem disk to read
            | icons from. When defining a disk, the "path" option
            | starts relatively from the disk root.
            |
            */

            'disk' => '',

            /*
            |-----------------------------------------------------------------
            | Default Prefix
            |-----------------------------------------------------------------
            |
            | This config option allows you to define a default prefix for
            | your icons. The dash separator will be applied automatically
            | to every icon name. It's required and needs to be unique.
            |
            */

            'prefix' => 'icon',

            /*
            |-----------------------------------------------------------------
            | Fallback Icon
            |-----------------------------------------------------------------
            |
            | This config option allows you to define a fallback
            | icon when an icon in this set cannot be found.
            |
            */

            'fallback' => '',

            /*
            |-----------------------------------------------------------------
            | Default Set Classes
            |-----------------------------------------------------------------
            |
            | This config option allows you to define some classes which
            | will be applied by default to all icons within this set.
            |
            */

            'class' => '',

            /*
            |-----------------------------------------------------------------
            | Default Set Attributes
            |-----------------------------------------------------------------
            |
            | This config option allows you to define some attributes which
            | will be applied by default to all icons within this set.
            |
            */

            'attributes' => [
                // 'width' => 50,
                // 'height' => 50,
            ],

        ],

        'black' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/black',
            'prefix' => 'black',
            'fallback' => 'default',
        ],
        'bright' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/bright',
            'prefix' => 'bright',
            'fallback' => 'default',
        ],
        'bw' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/bw',
            'prefix' => 'bw',
        ],
        'bw-circle' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/bw-circle',
            'prefix' => 'bw-circle',
        ],
        'bw-square' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/bw-square',
            'prefix' => 'bw-square',
        ],
        'cartoon' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/cartoon',
            'prefix' => 'cartoon',
        ],
        'circle' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/circle',
            'prefix' => 'circle',
        ],
        'circle-filled' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/circle-filled',
            'prefix' => 'circle-filled',
        ],
        'colorcircle' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/colorcircle',
            'prefix' => 'colorcircle',
        ],
        'colored' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/colored',
            'prefix' => 'colored',
        ],
        'filled' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/filled',
            'prefix' => 'filled',
        ],
        'flat' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/flat',
            'prefix' => 'flat',
        ],
        'gouache' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/gouache',
            'prefix' => 'gouache',
        ],
        'gradient' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/gradient',
            'prefix' => 'gradient',
        ],
        'gray' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/gray',
            'prefix' => 'gray',
        ],
        'hand' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/hand',
            'prefix' => 'hand',
        ],
        'handcolor' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/handcolor',
            'prefix' => 'handcolor',
        ],
        'line' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/line',
            'prefix' => 'line',
        ],
        'linear' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/linear',
            'prefix' => 'linear',
        ],
        'orig' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/orig',
            'prefix' => 'orig',
        ],
        'origsocial' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/origsocial',
            'prefix' => 'origsocial',
        ],
        'rectangle' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/rectangle',
            'prefix' => 'rectangle',
        ],
        'shaded' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/shaded',
            'prefix' => 'shaded',
        ],
        'shadow' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/shadow',
            'prefix' => 'shadow',
        ],
        'simple' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/simple',
            'prefix' => 'simple',
        ],
        'slim' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/slim',
            'prefix' => 'slim',
        ],
        'square' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/square',
            'prefix' => 'square',
        ],
        'symbols' => [
            'path' => '/vendor/usetall/tallui-web-icons/resources/svg/symbols',
            'prefix' => 'symbols',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global Default Classes
    |--------------------------------------------------------------------------
    |
    | This config option allows you to define some classes which
    | will be applied by default to all icons.
    |
    */

    'class' => '',

    /*
    |--------------------------------------------------------------------------
    | Global Default Attributes
    |--------------------------------------------------------------------------
    |
    | This config option allows you to define some attributes which
    | will be applied by default to all icons.
    |
    */

    'attributes' => [
        // 'width' => 50,
        // 'height' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Fallback Icon
    |--------------------------------------------------------------------------
    |
    | This config option allows you to define a global fallback
    | icon when an icon in any set cannot be found. It can
    | reference any icon from any configured set.
    |
    */

    'fallback' => '',

    /*
    |--------------------------------------------------------------------------
    | Components
    |--------------------------------------------------------------------------
    |
    | These config options allow you to define some
    | settings related to Blade Components.
    |
    */

    'components' => [

        /*
        |----------------------------------------------------------------------
        | Disable Components
        |----------------------------------------------------------------------
        |
        | This config option allows you to disable Blade components
        | completely. It's useful to avoid performance problems
        | when working with large icon libraries.
        |
        */

        'disabled' => false,

        /*
        |----------------------------------------------------------------------
        | Default Icon Component Name
        |----------------------------------------------------------------------
        |
        | This config option allows you to define the name
        | for the default Icon class component.
        |
        */

        'default' => 'icon',

    ],

];
