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
        'audit' => [
            'active' => true,
            'path' => $public_base_path . '/audit',
            'type' => 'public',
        ],
        'backup' => [
            'active' => false,
            'path' => $public_base_path . '/backup',
            'type' => 'public',
        ],
        'backup-server' => [
            'active' => true,
            'path' => $public_base_path . '/backup-server',
            'type' => 'public',
        ],
        'brand' => [
            'active' => false,
            'path' => $public_base_path . '/brand',
            'type' => 'public',
        ],
        'build' => [
            'active' => true,
            'path' => $public_base_path . '/build',
            'type' => 'public',
        ],
        'calendar' => [
            'active' => false,
            'path' => $public_base_path . '/calendar',
            'type' => 'public',
        ],
        'cart' => [
            'active' => false,
            'path' => $public_base_path . '/cart',
            'type' => 'public',
        ],
        'category' => [
            'active' => true,
            'path' => $public_base_path . '/category',
            'type' => 'public',
        ],
        'clipboard' => [
            'active' => true,
            'path' => $public_base_path . '/clipboard',
            'type' => 'public',
        ],
        'components' => [
            'active' => true,
            'path' => $public_base_path . '/components',
            'type' => 'public',
        ],
        'connect' => [
            'active' => false,
            'path' => $private_base_path . '/connect',
            'repo_url' => $private_repo_url,
            'type' => 'private',
        ],
        'core' => [
            'active' => true,
            'path' => $public_base_path . '/core',
            'type' => 'public',
        ],
        'customer' => [
            'active' => false,
            'path' => $public_base_path . '/customer',
            'type' => 'public',
        ],
        'data' => [
            'active' => true,
            'path' => $public_base_path . '/data',
            'type' => 'public',
        ],
        'demo' => [
            'active' => false,
            'path' => $public_base_path . '/demo',
            'type' => 'public',
        ],
        'devlink' => [
            'active' => false,
            'linked' => false,
            'path' => $public_base_path . '/devlink',
            'type' => 'public',
            'dev' => true,
        ],
        'devops' => [
            'active' => true,
            'path' => $public_base_path . '/devops',
            'type' => 'public',
        ],
        'devtools' => [
            'active' => true,
            'path' => $public_base_path . '/devtools',
            'type' => 'public',
        ],
        'draft' => [
            'active' => true,
            'path' => $public_base_path . '/draft',
            'type' => 'public',
        ],
        'expiry' => [
            'active' => true,
            'path' => $public_base_path . '/expiry',
            'type' => 'public',
        ],
        'featherlight' => [
            'active' => true,
            'path' => $public_base_path . '/featherlight',
            'type' => 'public',
        ],
        'flag-icons-circle' => [
            'active' => true,
            'path' => $public_base_path . '/flag-icons-circle',
            'type' => 'public',
        ],
        'flag-icons-origin' => [
            'active' => true,
            'path' => $public_base_path . '/flag-icons-origin',
            'type' => 'public',
        ],
        'flag-icons-rect' => [
            'active' => true,
            'path' => $public_base_path . '/flag-icons-rect',
            'type' => 'public',
        ],
        'flag-icons-square' => [
            'active' => true,
            'path' => $public_base_path . '/flag-icons-square',
            'type' => 'public',
        ],
        'forge' => [
            'active' => true,
            'path' => $public_base_path . '/forge',
            'type' => 'public',
        ],
        'forms' => [
            'active' => false,
            'path' => $public_base_path . '/forms',
            'type' => 'public',
        ],
        'frontend' => [
            'active' => false,
            'path' => $public_base_path . '/frontend',
            'type' => 'public',
        ],
        'github' => [
            'active' => false,
            'path' => $public_base_path . '/github',
            'type' => 'public',
        ],
        'impersonate' => [
            'active' => false,
            'path' => $public_base_path . '/impersonate',
            'type' => 'public',
        ],
        'item' => [
            'active' => true,
            'path' => $public_base_path . '/item',
            'type' => 'public',
        ],
        'jobs' => [
            'active' => true,
            'path' => $public_base_path . '/jobs',
            'type' => 'public',
        ],
        'json' => [
            'active' => false,
            'path' => $public_base_path . '/json',
            'type' => 'public',
        ],
        'laravel-icons' => [
            'active' => true,
            'path' => $public_base_path . '/laravel-icons',
            'type' => 'public',
        ],
        'localization' => [
            'active' => true,
            'path' => $public_base_path . '/localization',
            'type' => 'public',
        ],
        'login-link' => [
            'active' => true,
            'path' => $public_base_path . '/login-link',
            'type' => 'public',
        ],
        'markdown' => [
            'active' => false,
            'path' => $public_base_path . '/markdown',
            'type' => 'public',
        ],
        'media' => [
            'active' => true,
            'path' => $public_base_path . '/media',
            'type' => 'public',
        ],
        'media-pro' => [
            'active' => false,
            'path' => $private_base_path . '/media-pro',
            'repo_url' => $private_repo_url,
            'type' => 'private',
        ],
        'module' => [
            'active' => false,
            'path' => $public_base_path . '/module',
            'type' => 'public',
        ],
        'monorepo' => [
            'active' => false,
            'path' => $public_base_path . '/monorepo',
            'type' => 'public',
        ],
        'news' => [
            'active' => true,
            'path' => $public_base_path . '/news',
            'type' => 'public',
        ],
        'notifications' => [
            'active' => true,
            'path' => $public_base_path . '/notifications',
            'type' => 'public',
        ],
        'packages' => [
            'active' => true,
            'path' => $public_base_path . '/packages',
            'type' => 'public',
        ],
        'packagist' => [
            'active' => false,
            'path' => $public_base_path . '/packagist',
            'type' => 'public',
        ],
        'page' => [
            'active' => true,
            'path' => $public_base_path . '/page',
            'type' => 'public',
        ],
        'passkey' => [
            'active' => true,
            'path' => $public_base_path . '/passkey',
            'type' => 'public',
        ],
        'permission' => [
            'active' => false,
            'path' => $public_base_path . '/permission',
            'type' => 'public',
        ],
        'post' => [
            'active' => false,
            'path' => $public_base_path . '/post',
            'type' => 'public',
        ],
        'press' => [
            'active' => true,
            'path' => $public_base_path . '/press',
            'type' => 'public',
        ],
        'press-trainings' => [
            'active' => true,
            'path' => $public_base_path . '/press-trainings',
            'type' => 'public',
        ],
        'press-wiki' => [
            'active' => true,
            'path' => $public_base_path . '/press-wiki',
            'type' => 'public',
        ],
        'product' => [
            'active' => false,
            'path' => $public_base_path . '/product',
            'type' => 'public',
        ],
        'progress' => [
            'active' => true,
            'path' => $public_base_path . '/progress',
            'type' => 'public',
        ],
        'record' => [
            'active' => false,
            'path' => $public_base_path . '/record',
            'type' => 'public',
        ],
        'redis' => [
            'active' => false,
            'path' => $public_base_path . '/redis',
            'type' => 'public',
        ],
        'restore' => [
            'active' => true,
            'path' => $public_base_path . '/restore',
            'type' => 'public',
        ],
        'schedule' => [
            'active' => false,
            'path' => $public_base_path . '/schedule',
            'type' => 'public',
        ],
        'search' => [
            'active' => false,
            'path' => $public_base_path . '/search',
            'type' => 'public',
        ],
        'security' => [
            'active' => true,
            'path' => $public_base_path . '/security',
            'type' => 'public',
        ],
        'seo' => [
            'active' => false,
            'path' => $public_base_path . '/seo',
            'type' => 'public',
        ],
        'settings' => [
            'active' => false,
            'path' => $public_base_path . '/settings',
            'type' => 'public',
        ],
        'skeleton' => [
            'active' => true,
            'path' => $public_base_path . '/skeleton',
            'type' => 'public',
        ],
        'slug' => [
            'active' => true,
            'path' => $public_base_path . '/slug',
            'type' => 'public',
        ],
        'tag' => [
            'active' => true,
            'path' => $public_base_path . '/tag',
            'type' => 'public',
        ],
        'taxonomy' => [
            'active' => false,
            'path' => $public_base_path . '/taxonomy',
            'type' => 'public',
        ],
        'themes' => [
            'active' => false,
            'path' => $public_base_path . '/themes',
            'type' => 'public',
        ],
        'trainings' => [
            'active' => true,
            'path' => $public_base_path . '/trainings',
            'type' => 'public',
        ],
        'user' => [
            'active' => true,
            'path' => $public_base_path . '/user',
            'type' => 'public',
        ],
        'user-device' => [
            'active' => true,
            'path' => $public_base_path . '/user-device',
            'type' => 'public',
        ],
        'user-session' => [
            'active' => true,
            'path' => $public_base_path . '/user-session',
            'type' => 'public',
        ],
        'website' => [
            'active' => false,
            'path' => $public_base_path . '/website',
            'type' => 'public',
        ],
        'wishlist' => [
            'active' => false,
            'path' => $public_base_path . '/wishlist',
            'type' => 'public',
        ],
        'zzz-your-local-package' => [
            'active' => false,
            'type' => 'local',
        ],
    ],

    'packages_path' => $packages_path,
    'public_base_path' => $public_base_path,
    'private_base_path' => $private_base_path,

];
