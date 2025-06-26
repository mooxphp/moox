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
    | These packages are symlinked (or if local, just added) into the project.
    |
    | Active:
    | Toggle the package on and off. By default all packages are disabled.
    |
    | Path:
    | The path to the package in the packages directory.
    |
    | Repo-URL:
    | The URL of the private repository (Satis).
    |
    | Types:
    | - public = installed from Packagist
    | - private = installed from Satis
    | - local = local package
    |
    | Dev:
    | The package is only installed in the dev environment (require-dev).
    |
    */
    'packages' => [

        // Public
        'audit' => [
            'active' => true,
            'path' => $public_base_path.'/audit',
            'type' => 'public',
        ],
        'backup-server-ui' => [
            'active' => true,
            'path' => $public_base_path.'/backup-server',
            'type' => 'public',
        ],
        'build' => [
            'active' => true,
            'path' => $public_base_path.'/build',
            'type' => 'public',
        ],
        'category' => [
            'active' => true,
            'path' => $public_base_path.'/category',
            'type' => 'public',
        ],
        'core' => [
            'active' => true,
            'path' => $public_base_path.'/core',
            'type' => 'public',
        ],
        'data' => [
            'active' => true,
            'path' => $public_base_path.'/data',
            'type' => 'public',
        ],
        'devlink' => [
            'active' => false,
            'linked' => false,
            'path' => $public_base_path.'/devlink',
            'type' => 'public',
            'dev' => true,
        ],
        'devops' => [
            'active' => true,
            'path' => $public_base_path.'/devops',
            'type' => 'public',
        ],
        'expiry' => [
            'active' => true,
            'path' => $public_base_path.'/expiry',
            'type' => 'public',
        ],
        'flag-icons-circle' => [
            'active' => true,
            'path' => $public_base_path.'/flag-icons-circle',
            'type' => 'public',
        ],
        'jobs' => [
            'active' => true,
            'path' => $public_base_path.'/jobs',
            'type' => 'public',
        ],
        'localization' => [
            'active' => true,
            'path' => $public_base_path.'/localization',
            'type' => 'public',
        ],
        'login-link' => [
            'active' => true,
            'path' => $public_base_path.'/login-link',
            'type' => 'public',
        ],
        'media' => [
            'active' => true,
            'path' => $public_base_path.'/media',
            'type' => 'public',
        ],
        'monorepo' => [
            'active' => true,
            'path' => $public_base_path.'/monorepo',
            'type' => 'public',
        ],
        'notifications' => [
            'active' => true,
            'path' => $public_base_path.'/notifications',
            'type' => 'public',
        ],
        'passkey' => [
            'active' => true,
            'path' => $public_base_path.'/passkey',
            'type' => 'public',
        ],
        'press' => [
            'active' => true,
            'path' => $public_base_path.'/press',
            'type' => 'public',
        ],
        'security' => [
            'active' => true,
            'path' => $public_base_path.'/security',
            'type' => 'public',
        ],
        'slug' => [
            'active' => true,
            'path' => $public_base_path.'/slug',
            'type' => 'public',
        ],
        'tag' => [
            'active' => true,
            'path' => $public_base_path.'/tag',
            'type' => 'public',
        ],
        'trainings' => [
            'active' => true,
            'path' => $public_base_path.'/trainings',
            'type' => 'public',
        ],
        'user' => [
            'active' => true,
            'path' => $public_base_path.'/user',
            'type' => 'public',
        ],
        'user-device' => [
            'active' => true,
            'path' => $public_base_path.'/user-device',
            'type' => 'public',
        ],
        'user-session' => [
            'active' => true,
            'path' => $public_base_path.'/user-session',
            'type' => 'public',
        ],

        // Private
        'connect' => [
            'active' => false,
            'path' => $private_base_path.'/connect',
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
        'your-local-package' => [
            'active' => true,
            'type' => 'local',
        ],
    ],

    'packages_path' => $packages_path,
    'public_base_path' => $public_base_path,
    'private_base_path' => $private_base_path,

];
