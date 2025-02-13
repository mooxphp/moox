<?php

return [

    // User-specific path from .env
    'packages_path' => env('DEVLINK_PACKAGES_PATH', 'packages'),

    // The base paths to the packages, project or branch-wise
    'base_paths' => [
        base_path('../moox/packages'),
    ],

    // The internal packages need to be copied on deploy, project or branch-wise,
    'copy_packages' => [
        // 'builder-pro',
    ],

    // The packages that need to be removed on deploy,project or branch-wise
    'skip_packages' => [
        'devlink',
    ],

    // The packages that are installed from Packagist on deploy, project or branch-wise
    'packages' => [
        // 'audit',
        // 'backup-server-ui',
        // 'builder',
        // 'category',
        // 'core',
        // 'connect',
        // 'data',
        // 'devops',
        // 'expiry',
        // 'flags',
        // 'jobs',
        // 'login-link',
        // 'localization',
        // 'media',
        // 'notifications',
        // 'page',
        // 'passkey',
        // 'permission',
        // 'press',
        // 'security',
        // 'sync',
        'tag',
        // 'trainings',
        // 'user',
        // 'user-device',
        // 'user-session',
    ],
];
