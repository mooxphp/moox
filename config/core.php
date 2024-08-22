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
    | work out of the box. Adding a non-compatible package fails.
    |
    */

    'packages' => [
        'audit' => 'Moox Audit',
        'builder' => 'Moox Builder',
        'core' => 'Moox Core',
        'expiry' => 'Moox Expiry',
        'jobs' => 'Moox Jobs',
        'login-link' => 'Moox Login Link',
        'notifications' => 'Moox Notifications',
        'page' => 'Moox Page',
        'passkey' => 'Moox Passkey',
        'permission' => 'Moox Permission',
        'press' => 'Moox Press',
        'press-wiki' => 'Moox Press Wiki',
        'security' => 'Moox Security',
        'sync' => 'Moox Sync',
        'training' => 'Moox Trainings',
        'user' => 'Moox User',
        'user-device' => 'Moox User Device',
        'user-session' => 'Moox User Session',
    ],
];
