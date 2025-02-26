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

/*
|--------------------------------------------------------------------------
| Private Packages Repo URL
|--------------------------------------------------------------------------
|
| The URL of the Moox package repository. Can be set in the .env file.
|
*/
$private_repo_url = env('DEVLINK_PRIVATE_REPO_URL', 'https://pkg.moox.pro/');

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
            'path' => $public_base_path.'/audit',
            'type' => 'public',
        ],
        'backup-server-ui' => [
            'active' => false,
            'path' => $public_base_path.'/backup-server-ui',
            'type' => 'public',
        ],
        'builder' => [
            'active' => false,
            'path' => $public_base_path.'/builder',
            'type' => 'public',
        ],
        'category' => [
            'active' => false,
            'path' => $public_base_path.'/category',
            'type' => 'public',
        ],
        'core' => [
            'active' => false,
            'path' => $public_base_path.'/core',
            'type' => 'public',
        ],
        'devlink' => [
            'active' => false,
            'linked' => false,
            'path' => $public_base_path.'/devlink',
            'type' => 'public',
        ],
        'devops' => [
            'active' => false,
            'path' => $public_base_path.'/devops',
            'type' => 'public',
        ],
        'expiry' => [
            'active' => false,
            'path' => $public_base_path.'/expiry',
            'type' => 'public',
        ],
        'flags' => [
            'active' => false,
            'path' => $public_base_path.'/flags',
            'type' => 'public',
        ],
        'jobs' => [
            'active' => false,
            'path' => $public_base_path.'/jobs',
            'type' => 'public',
        ],
        'login-link' => [
            'active' => false,
            'path' => $public_base_path.'/login-link',
            'type' => 'public',
        ],
        'notifications' => [
            'active' => false,
            'path' => $public_base_path.'/notifications',
            'type' => 'public',
        ],
        'passkey' => [
            'active' => false,
            'path' => $public_base_path.'/passkey',
            'type' => 'public',
        ],
        'press' => [
            'active' => false,
            'path' => $public_base_path.'/press',
            'type' => 'public',
        ],
        'security' => [
            'active' => false,
            'path' => $public_base_path.'/security',
            'type' => 'public',
        ],
        'sync' => [
            'active' => false,
            'path' => $public_base_path.'/sync',
            'type' => 'public',
        ],
        'tag' => [
            'active' => false,
            'path' => $public_base_path.'/tag',
            'type' => 'public',
        ],
        'trainings' => [
            'active' => false,
            'path' => $public_base_path.'/trainings',
            'type' => 'public',
        ],
        'user' => [
            'active' => false,
            'path' => $public_base_path.'/user',
            'type' => 'public',
        ],
        'user-device' => [
            'active' => false,
            'path' => $public_base_path.'/user-device',
            'type' => 'public',
        ],
        'user-session' => [
            'active' => false,
            'path' => $public_base_path.'/user-session',
            'type' => 'public',
        ],

        // Moox Pro
<<<<<<< Updated upstream
        'connect' => [
            'active' => false,
            'path' => $private_base_path.'/connect',
            'repo_url' => $private_repo_url,
            'type' => 'private',
        ],
        'creator' => [
            'active' => false,
            'path' => $private_base_path.'/creator',
            'repo_url' => $private_repo_url,
            'type' => 'private',
        ],
        'data' => [
            'active' => false,
            'path' => $private_base_path.'/data',
            'repo_url' => $private_repo_url,
            'type' => 'private',
        ],
        'localize' => [
            'active' => false,
            'path' => $private_base_path.'/localize',
            'repo_url' => $private_repo_url,
            'type' => 'private',
        ],
=======
>>>>>>> Stashed changes
        'media' => [
            'active' => false,
            'path' => $private_base_path.'/media',
            'repo_url' => $private_repo_url,
            'type' => 'private',
        ],
        'page' => [
            'active' => false,
            'path' => $private_base_path.'/page',
            'repo_url' => $private_repo_url,
            'type' => 'private',
        ],
        'permission' => [
            'active' => false,
            'path' => $private_base_path.'/permission',
            'repo_url' => $private_repo_url,
            'type' => 'private',
        ],

        // Local
        'connect' => [
            'active' => false,
            'type' => 'local',
        ],
        'hecoweb' => [
            'active' => true,
            'type' => 'local',
        ],
        'myheco' => [
            'active' => true,
            'type' => 'local',
        ],
        'data' => [
            'active' => true,
            'type' => 'local',
        ],
        'localization' => [
            'active' => true,
            'type' => 'local',
        ],
    ],

    'packages_path' => $packages_path,
    'public_base_path' => $public_base_path,
    'private_base_path' => $private_base_path,

];
