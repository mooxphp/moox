<?php

/*
|--------------------------------------------------------------------------
| Packages Path
|--------------------------------------------------------------------------
|
| The path to the packages directory. Can be set in the .env file.
|
*/
$packages_path = env('DEVLINK_PACKAGES_PATH', 'packages');

/*
|--------------------------------------------------------------------------
| Moox Base Paths
|--------------------------------------------------------------------------
|
| The paths to the Moox packages directories. Can be set in the .env file.
|
*/
$moox_base_path = env('DEVLINK_MOOX_BASE_PATH', '../moox');
$mooxpro_base_path = env('DEVLINK_MOOXPRO_BASE_PATH', '../mooxpro');

return [

    /*
    |--------------------------------------------------------------------------
    | Packages
    |--------------------------------------------------------------------------
    |
    | The packages that should be linked into the project.
    |
    | Active:
    | Toggle the package on and off. By default all packages are disabled.
    |
    | Linked:
    | Toggle the package off for linking, for example devlink itself.
    |
    | Path:
    | The path to the package in the packages directory.
    |
    | Types:
    | - public = installed from Packagist
    | - private = copied into the project
    |
    | Deploy:
    | Toggle the package on and off for deployment. Better would be to use
    | require-dev in composer.json, but this also works.
    |
    */
    'packages' => [

        // Moox
        'audit' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($moox_base_path.'/audit'),
            'type' => 'public',
            'deploy' => true,
        ],
        'backup-server-ui' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($moox_base_path.'/backup-server-ui'),
            'type' => 'public',
            'deploy' => true,
        ],
        'builder' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($moox_base_path.'/builder'),
            'type' => 'public',
            'deploy' => false,
        ],
        'category' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($moox_base_path.'/category'),
            'type' => 'public',
            'deploy' => true,
        ],
        'core' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($moox_base_path.'/core'),
            'type' => 'public',
            'deploy' => true,
        ],
        'devlink' => [
            'active' => false,
            'linked' => false,
            'path' => base_path($moox_base_path.'/devlink'),
            'type' => 'public',
            'deploy' => false,
        ],
        'devops' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($moox_base_path.'/devops'),
            'type' => 'public',
            'deploy' => true,
        ],
        'expiry' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($moox_base_path.'/expiry'),
            'type' => 'public',
            'deploy' => true,
        ],
        'flags' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($moox_base_path.'/flags'),
            'type' => 'public',
            'deploy' => true,
        ],
        'jobs' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($moox_base_path.'/jobs'),
            'type' => 'public',
            'deploy' => true,
        ],
        'login-link' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($moox_base_path.'/login-link'),
            'type' => 'public',
            'deploy' => true,
        ],
        'notifications' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($moox_base_path.'/notifications'),
            'type' => 'public',
            'deploy' => true,
        ],
        'passkey' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($moox_base_path.'/passkey'),
            'type' => 'public',
            'deploy' => true,
        ],
        'press' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($moox_base_path.'/press'),
            'type' => 'public',
            'deploy' => true,
        ],
        'security' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($moox_base_path.'/security'),
            'type' => 'public',
            'deploy' => true,
        ],
        'sync' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($moox_base_path.'/sync'),
            'type' => 'public',
            'deploy' => true,
        ],
        'tag' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($moox_base_path.'/tag'),
            'type' => 'public',
            'deploy' => true,
        ],
        'trainings' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($moox_base_path.'/trainings'),
            'type' => 'public',
            'deploy' => true,
        ],
        'user' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($moox_base_path.'/user'),
            'type' => 'public',
            'deploy' => true,
        ],
        'user-device' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($moox_base_path.'/user-device'),
            'type' => 'public',
            'deploy' => true,
        ],
        'user-session' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($moox_base_path.'/user-session'),
            'type' => 'public',
            'deploy' => true,
        ],

        // Moox Pro
        'connect' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($mooxpro_base_path.'/connect'),
            'type' => 'public',
            'deploy' => true,
        ],
        'creator' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($mooxpro_base_path.'/creator'),
            'type' => 'public',
            'deploy' => false,
        ],
        'data' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($mooxpro_base_path.'/data'),
            'type' => 'public',
            'deploy' => true,
        ],
        'localize' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($mooxpro_base_path.'/localize'),
            'type' => 'public',
            'deploy' => true,
        ],
        'media' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($mooxpro_base_path.'/media'),
            'type' => 'public',
            'deploy' => true,
        ],
        'page' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($mooxpro_base_path.'/page'),
            'type' => 'public',
            'deploy' => true,
        ],
        'permission' => [
            'active' => false,
            'linked' => true,
            'path' => base_path($mooxpro_base_path.'/permission'),
            'type' => 'public',
            'deploy' => true,
        ],
    ],

    'packages_path' => $packages_path,
    'moox_base_path' => $moox_base_path,
    'mooxpro_base_path' => $mooxpro_base_path,

];
