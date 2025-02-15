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
$public_base_path = env('DEVLINK_PUBLIC_PATH', '../moox/packages');
$private_base_path = env('DEVLINK_PRIVATE_PATH', 'disabled');

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
            'path' => $public_base_path.'/audit',
            'type' => 'public',
            'deploy' => true,
        ],
        'backup-server-ui' => [
            'active' => false,
            'linked' => true,
            'path' => $public_base_path.'/backup-server-ui',
            'type' => 'public',
            'deploy' => true,
        ],
        'builder' => [
            'active' => false,
            'linked' => true,
            'path' => $public_base_path.'/builder',
            'type' => 'public',
            'deploy' => false,
        ],
        'category' => [
            'active' => false,
            'linked' => true,
            'path' => $public_base_path.'/category',
            'type' => 'public',
            'deploy' => true,
        ],
        'core' => [
            'active' => false,
            'linked' => true,
            'path' => $public_base_path.'/core',
            'type' => 'public',
            'deploy' => true,
        ],
        'devlink' => [
            'active' => false,
            'linked' => false,
            'path' => $public_base_path.'/devlink',
            'type' => 'public',
            'deploy' => false,
        ],
        'devops' => [
            'active' => false,
            'linked' => true,
            'path' => $public_base_path.'/devops',
            'type' => 'public',
            'deploy' => true,
        ],
        'expiry' => [
            'active' => false,
            'linked' => true,
            'path' => $public_base_path.'/expiry',
            'type' => 'public',
            'deploy' => true,
        ],
        'flags' => [
            'active' => false,
            'linked' => true,
            'path' => $public_base_path.'/flags',
            'type' => 'public',
            'deploy' => true,
        ],
        'jobs' => [
            'active' => false,
            'linked' => true,
            'path' => $public_base_path.'/jobs',
            'type' => 'public',
            'deploy' => true,
        ],
        'login-link' => [
            'active' => false,
            'linked' => true,
            'path' => $public_base_path.'/login-link',
            'type' => 'public',
            'deploy' => true,
        ],
        'notifications' => [
            'active' => false,
            'linked' => true,
            'path' => $public_base_path.'/notifications',
            'type' => 'public',
            'deploy' => true,
        ],
        'passkey' => [
            'active' => false,
            'linked' => true,
            'path' => $public_base_path.'/passkey',
            'type' => 'public',
            'deploy' => true,
        ],
        'press' => [
            'active' => false,
            'linked' => true,
            'path' => $public_base_path.'/press',
            'type' => 'public',
            'deploy' => true,
        ],
        'security' => [
            'active' => false,
            'linked' => true,
            'path' => $public_base_path.'/security',
            'type' => 'public',
            'deploy' => true,
        ],
        'sync' => [
            'active' => false,
            'linked' => true,
            'path' => $public_base_path.'/sync',
            'type' => 'public',
            'deploy' => true,
        ],
        'tag' => [
            'active' => false,
            'linked' => true,
            'path' => $public_base_path.'/tag',
            'type' => 'public',
            'deploy' => true,
        ],
        'trainings' => [
            'active' => false,
            'linked' => true,
            'path' => $public_base_path.'/trainings',
            'type' => 'public',
            'deploy' => true,
        ],
        'user' => [
            'active' => false,
            'linked' => true,
            'path' => $public_base_path.'/user',
            'type' => 'public',
            'deploy' => true,
        ],
        'user-device' => [
            'active' => false,
            'linked' => true,
            'path' => $public_base_path.'/user-device',
            'type' => 'public',
            'deploy' => true,
        ],
        'user-session' => [
            'active' => false,
            'linked' => true,
            'path' => $public_base_path.'/user-session',
            'type' => 'public',
            'deploy' => true,
        ],

        // Moox Pro
        'connect' => [
            'active' => false,
            'linked' => true,
            'path' => $private_base_path.'/connect',
            'type' => 'private',
            'deploy' => true,
        ],
        'creator' => [
            'active' => false,
            'linked' => true,
            'path' => $private_base_path.'/creator',
            'type' => 'private',
            'deploy' => false,
        ],
        'data' => [
            'active' => false,
            'linked' => true,
            'path' => $private_base_path.'/data',
            'type' => 'private',
            'deploy' => true,
        ],
        'localize' => [
            'active' => false,
            'linked' => true,
            'path' => $private_base_path.'/localize',
            'type' => 'private',
            'deploy' => true,
        ],
        'media' => [
            'active' => false,
            'linked' => true,
            'path' => $private_base_path.'/media',
            'type' => 'private',
            'deploy' => true,
        ],
        'page' => [
            'active' => false,
            'linked' => true,
            'path' => $private_base_path.'/page',
            'type' => 'private',
            'deploy' => true,
        ],
        'permission' => [
            'active' => false,
            'linked' => true,
            'path' => $private_base_path.'/permission',
            'type' => 'private',
            'deploy' => true,
        ],
    ],

    'packages_path' => $packages_path,
    'public_base_path' => $public_base_path,
    'private_base_path' => $private_base_path,

];
