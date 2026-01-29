<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Installer Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the behavior of Moox package installers.
    | Each installer can be enabled/disabled, prioritized, and customized.
    |
    */

    'installers' => [
        'migrations' => [
            'enabled' => true,
            'priority' => 10,
            'run_after_publish' => true,
            'skip_existing' => true,
            'force' => false,
        ],

        'configs' => [
            'enabled' => true,
            'priority' => 20,
            'skip_existing' => true,
            'force' => false,
        ],

        // 'translations' => [
        //     'enabled' => true,
        //     'priority' => 30,
        //     'skip_existing' => true,
        //     'force' => false,
        // ],

        // 'seeders' => [
        //     'enabled' => true,
        //     'priority' => 50,
        //     'require_confirmation' => true,
        // ],

        'plugins' => [
            'enabled' => true,
            'priority' => 100,
            'allow_multiple_panels' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Skip Installers
    |--------------------------------------------------------------------------
    |
    | List of installer types to skip during installation.
    | These installers won't be available for selection.
    |
    */

    'skip' => [
        // 'seeders',
        // 'translations',
    ],

    /*
    |--------------------------------------------------------------------------
    | Only Installers
    |--------------------------------------------------------------------------
    |
    | If set, ONLY these installer types will be available.
    | Takes precedence over 'skip'.
    |
    */

    'only' => [
        // 'migrations',
        // 'configs',
        // 'plugins',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Installers
    |--------------------------------------------------------------------------
    |
    | Register additional installer classes.
    | Each entry should be a fully qualified class name.
    |
    */

    'custom_installers' => [
        // \App\Installers\MyCustomInstaller::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Hooks
    |--------------------------------------------------------------------------
    |
    | Callback classes for installation lifecycle hooks.
    | Each class should implement the appropriate hook interface.
    |
    */

    'hooks' => [
        'before_install' => null,
        'after_install' => null,
        'before_migrations' => null,
        'after_migrations' => null,
        'before_configs' => null,
        'after_configs' => null,
        'before_plugins' => null,
        'after_plugins' => null,
    ],
];
