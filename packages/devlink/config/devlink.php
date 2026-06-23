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

/*
|--------------------------------------------------------------------------
| Export Path
|--------------------------------------------------------------------------
|
| The file the package list is exported to (relative to the project root),
| used by CI and the export script. Can be set in the .env file.
|
*/
$export_path = env('DEVLINK_EXPORT_PATH', '.github/moox-packages.txt');

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
    | - bundle = meta package; active bundles resolve moox/* from composer.json (require + require-dev);
    |   any required moox/* not listed here aborts with an error
    | - public = installed from Packagist
    | - private = installed from Satis
    | - local = local package
    |
    | Dev:
    | The package is only installed in the dev environment (require-dev).
    |
    */
    'packages' => [

        /*
        |--------------------------------------------------------------------------
        | Bundles
        |--------------------------------------------------------------------------
        |
        | moox Bundles are used to install
        | a set of packages at once.
        |
        */
        'identity' => [
            'active' => false,
            'path' => $public_base_path.'/identity',
            'type' => 'bundle',
        ],
        'content' => [
            'active' => false,
            'path' => $public_base_path.'/content',
            'type' => 'bundle',
        ],
        'commerce' => [
            'active' => false,
            'path' => $public_base_path.'/commerce',
            'type' => 'bundle',
        ],
        'infra' => [
            'active' => false,
            'path' => $public_base_path.'/infra',
            'type' => 'bundle',
        ],
        'full' => [
            'active' => true,
            'path' => $public_base_path.'/full',
            'type' => 'bundle',
        ],

        /*
        |--------------------------------------------------------------------------
        | FOSS Packages
        |--------------------------------------------------------------------------
        |
        | Single packages from the FOSS repository.
        |
        */
        'address' => [
            'active' => false,
            'path' => $public_base_path.'/address',
            'type' => 'public',
        ],
        'attribute' => [
            'active' => false,
            'path' => $public_base_path.'/attribute',
            'type' => 'public',
        ],
        'audit' => [
            'active' => false,
            'path' => $public_base_path.'/audit',
            'type' => 'public',
        ],
        'backup' => [
            'active' => false,
            'path' => $public_base_path.'/backup',
            'type' => 'public',
        ],
        'backup-server' => [
            'active' => false,
            'path' => $public_base_path.'/backup-server',
            'type' => 'public',
        ],
        'brand' => [
            'active' => false,
            'path' => $public_base_path.'/brand',
            'type' => 'public',
        ],
        'build' => [
            'active' => false,
            'path' => $public_base_path.'/build',
            'type' => 'public',
        ],
        'cache' => [
            'active' => false,
            'path' => $public_base_path.'/cache',
            'type' => 'public',
        ],
        'cache-static' => [
            'active' => false,
            'path' => $public_base_path.'/cache-static',
            'type' => 'public',
        ],
        'calendar' => [
            'active' => false,
            'path' => $public_base_path.'/calendar',
            'type' => 'public',
        ],
        'cart' => [
            'active' => false,
            'path' => $public_base_path.'/cart',
            'type' => 'public',
        ],
        'category' => [
            'active' => false,
            'path' => $public_base_path.'/category',
            'type' => 'public',
        ],
        'clipboard' => [
            'active' => false,
            'path' => $public_base_path.'/clipboard',
            'type' => 'public',
        ],
        'cloudflare' => [
            'active' => false,
            'path' => $public_base_path.'/cloudflare',
            'type' => 'public',
        ],
        'company' => [
            'active' => false,
            'path' => $public_base_path.'/company',
            'type' => 'public',
        ],
        'components' => [
            'active' => false,
            'path' => $public_base_path.'/components',
            'type' => 'public',
        ],
        'contact' => [
            'active' => false,
            'path' => $public_base_path.'/contact',
            'type' => 'public',
        ],
        'connect' => [
            'active' => false,
            'path' => $private_base_path.'/connect',
            'repo_url' => $private_repo_url,
            'type' => 'private',
        ],
        'core' => [
            'active' => false,
            'path' => $public_base_path.'/core',
            'type' => 'public',
        ],
        'customer' => [
            'active' => false,
            'path' => $public_base_path.'/customer',
            'type' => 'public',
        ],
        'data' => [
            'active' => false,
            'path' => $public_base_path.'/data',
            'type' => 'public',
        ],
        'demo' => [
            'active' => false,
            'path' => $public_base_path.'/demo',
            'type' => 'public',
        ],
        'device' => [
            'active' => false,
            'path' => $public_base_path.'/device',
            'type' => 'public',
        ],
        'department' => [
            'active' => false,
            'path' => $public_base_path.'/department',
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
            'active' => false,
            'path' => $public_base_path.'/devops',
            'type' => 'public',
        ],
        'devtools' => [
            'active' => false,
            'path' => $public_base_path.'/devtools',
            'type' => 'public',
        ],
        'draft' => [
            'active' => false,
            'path' => $public_base_path.'/draft',
            'type' => 'public',
        ],
        'expiry' => [
            'active' => false,
            'path' => $public_base_path.'/expiry',
            'type' => 'public',
        ],
        'featherlight' => [
            'active' => false,
            'path' => $public_base_path.'/featherlight',
            'type' => 'public',
        ],
        'file-icons' => [
            'active' => false,
            'path' => $public_base_path.'/file-icons',
            'type' => 'public',
        ],
        'firewall' => [
            'active' => false,
            'path' => $public_base_path.'/firewall',
            'type' => 'public',
        ],
        'flag-icons-circle' => [
            'active' => false,
            'path' => $public_base_path.'/flag-icons-circle',
            'type' => 'public',
        ],
        'flag-icons-origin' => [
            'active' => false,
            'path' => $public_base_path.'/flag-icons-origin',
            'type' => 'public',
        ],
        'flag-icons-rect' => [
            'active' => false,
            'path' => $public_base_path.'/flag-icons-rect',
            'type' => 'public',
        ],
        'flag-icons-square' => [
            'active' => false,
            'path' => $public_base_path.'/flag-icons-square',
            'type' => 'public',
        ],
        'forge' => [
            'active' => false,
            'path' => $public_base_path.'/forge',
            'type' => 'public',
        ],
        'forms' => [
            'active' => false,
            'path' => $public_base_path.'/forms',
            'type' => 'public',
        ],
        'frontend' => [
            'active' => false,
            'path' => $public_base_path.'/frontend',
            'type' => 'public',
        ],
        'github' => [
            'active' => false,
            'path' => $public_base_path.'/github',
            'type' => 'public',
        ],
        'impersonate' => [
            'active' => false,
            'path' => $public_base_path.'/impersonate',
            'type' => 'public',
        ],
        'item' => [
            'active' => false,
            'path' => $public_base_path.'/item',
            'type' => 'public',
        ],
        'jobs' => [
            'active' => false,
            'path' => $public_base_path.'/jobs',
            'type' => 'public',
        ],
        'json' => [
            'active' => false,
            'path' => $public_base_path.'/json',
            'type' => 'public',
        ],
        'laravel-icons' => [
            'active' => false,
            'path' => $public_base_path.'/laravel-icons',
            'type' => 'public',
        ],
        'localization' => [
            'active' => false,
            'path' => $public_base_path.'/localization',
            'type' => 'public',
        ],
        'login-link' => [
            'active' => false,
            'path' => $public_base_path.'/login-link',
            'type' => 'public',
        ],
        'markdown' => [
            'active' => false,
            'path' => $public_base_path.'/markdown',
            'type' => 'public',
        ],
        'media' => [
            'active' => false,
            'path' => $public_base_path.'/media',
            'type' => 'public',
        ],
        'media-pro' => [
            'active' => false,
            'path' => $private_base_path.'/media-pro',
            'repo_url' => $private_repo_url,
            'type' => 'private',
        ],
        'module' => [
            'active' => false,
            'path' => $public_base_path.'/module',
            'type' => 'public',
        ],
        'monorepo' => [
            'active' => false,
            'path' => $public_base_path.'/monorepo',
            'type' => 'public',
        ],
        'news' => [
            'active' => false,
            'path' => $public_base_path.'/news',
            'type' => 'public',
        ],
        'notifications' => [
            'active' => false,
            'path' => $public_base_path.'/notifications',
            'type' => 'public',
        ],
        'packages' => [
            'active' => false,
            'path' => $public_base_path.'/packages',
            'type' => 'public',
        ],
        'packagist' => [
            'active' => false,
            'path' => $public_base_path.'/packagist',
            'type' => 'public',
        ],
        'page' => [
            'active' => false,
            'path' => $public_base_path.'/page',
            'type' => 'public',
        ],
        'passkey' => [
            'active' => false,
            'path' => $public_base_path.'/passkey',
            'type' => 'public',
        ],
        'permission' => [
            'active' => false,
            'path' => $public_base_path.'/permission',
            'type' => 'public',
        ],
        'post' => [
            'active' => false,
            'path' => $public_base_path.'/post',
            'type' => 'public',
        ],
        'press' => [
            'active' => false,
            'path' => $public_base_path.'/press',
            'type' => 'public',
        ],
        'press-trainings' => [
            'active' => false,
            'path' => $public_base_path.'/press-trainings',
            'type' => 'public',
        ],
        'press-wiki' => [
            'active' => false,
            'path' => $public_base_path.'/press-wiki',
            'type' => 'public',
        ],
        'product' => [
            'active' => false,
            'path' => $public_base_path.'/product',
            'type' => 'public',
        ],
        'progress' => [
            'active' => false,
            'path' => $public_base_path.'/progress',
            'type' => 'public',
        ],
        'prompts' => [
            'active' => false,
            'path' => $public_base_path.'/prompts',
            'type' => 'public',
        ],
        'record' => [
            'active' => false,
            'path' => $public_base_path.'/record',
            'type' => 'public',
        ],
        'redis' => [
            'active' => false,
            'path' => $public_base_path.'/redis',
            'type' => 'public',
        ],
        'restore' => [
            'active' => false,
            'path' => $public_base_path.'/restore',
            'type' => 'public',
        ],
        'schedule' => [
            'active' => false,
            'path' => $public_base_path.'/schedule',
            'type' => 'public',
        ],
        'scopes' => [
            'active' => false,
            'path' => $public_base_path.'/scopes',
            'type' => 'public',
        ],
        'search' => [
            'active' => false,
            'path' => $public_base_path.'/search',
            'type' => 'public',
        ],
        'security' => [
            'active' => false,
            'path' => $public_base_path.'/security',
            'type' => 'public',
        ],
        'seo' => [
            'active' => false,
            'path' => $public_base_path.'/seo',
            'type' => 'public',
        ],
        'settings' => [
            'active' => false,
            'path' => $public_base_path.'/settings',
            'type' => 'public',
        ],
        'skeleton' => [
            'active' => false,
            'path' => $public_base_path.'/skeleton',
            'type' => 'public',
        ],
        'slug' => [
            'active' => false,
            'path' => $public_base_path.'/slug',
            'type' => 'public',
        ],
        'staff' => [
            'active' => false,
            'path' => $public_base_path.'/staff',
            'type' => 'public',
        ],
        'tag' => [
            'active' => false,
            'path' => $public_base_path.'/tag',
            'type' => 'public',
        ],
        'taxonomy' => [
            'active' => false,
            'path' => $public_base_path.'/taxonomy',
            'type' => 'public',
        ],
        'themes' => [
            'active' => false,
            'path' => $public_base_path.'/themes',
            'type' => 'public',
        ],
        'transform' => [
            'active' => false,
            'path' => $public_base_path.'/transform',
            'type' => 'public',
        ],
        'trainings' => [
            'active' => false,
            'path' => $public_base_path.'/trainings',
            'type' => 'public',
        ],
        'tree' => [
            'active' => false,
            'path' => $public_base_path.'/tree',
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
        'website' => [
            'active' => false,
            'path' => $public_base_path.'/website',
            'type' => 'public',
        ],
        'wishlist' => [
            'active' => false,
            'path' => $public_base_path.'/wishlist',
            'type' => 'public',
        ],

        /*
        |--------------------------------------------------------------------------
        | Pro Packages
        |--------------------------------------------------------------------------
        |
        | These packages are moox Pro packages
        | that are installed from the
        | private repository.
        |
        */
        'pro' => [
            'active' => false,
            'path' => $private_base_path.'/pro',
            'repo_url' => $private_repo_url,
            'type' => 'private',
        ],

        /*
        |--------------------------------------------------------------------------
        | Local Packages
        |--------------------------------------------------------------------------
        |
        | These packages are local packages
        | that are installed from the
        | packages directory.
        |
        */
        'zzz-your-local-package' => [
            'active' => false,
            'type' => 'local',
        ],
    ],

    'packages_path' => $packages_path,
    'public_base_path' => $public_base_path,
    'private_base_path' => $private_base_path,
    'export_path' => $export_path,

];
