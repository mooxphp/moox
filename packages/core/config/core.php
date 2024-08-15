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
    | Moox Packages
    |--------------------------------------------------------------------------
    |
    | This config array registers all known Moox packages. You may add own
    | packages to this array. If you use Moox Builder, these packages
    | work out of the box. Otherwise check the builder config.
    |
    */

    'packages' => [
        'audit',
        'builder',
        'core',
        'expiry',
        'jobs',
        'login-link',
        'notifications',
        'page',
        'passkey',
        'permission',
        'press',
        'security',
        'sync',
        'training',
        'user',
        'user-device',
        'user-session',
    ],
];
