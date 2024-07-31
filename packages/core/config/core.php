<?php

return [

    /*
     | Set to false to disable advanced tables
     | If true, the advanced tables plugin will be
     | automatically loaded, if it is installed and enabled
     | https://filamentphp.com/plugins/kenneth-sese-advanced-tables
    */
    'use_advanced_tables' => true,

    /*
     | Blueprint of all Moox-compatible models,
     | if they are authenticatable models (like Users)
     | and what APIs Services and Routes they possibly provide.
    */
    'models' => [
        'User' => [
            'Authenticatable' => true,
            'API' => [
                'Index' => '',
                'Show' => '',
                'Update' => '',
                'Delete' => '',
            ],
            'AuthRoutes' => [
                'Login' => '',
                'PasswordReset' => '',
                'Register' => '',
            ],
        ],
        'WpPost' => [
            'Authenticatable' => false,
            'API' => [
                'Index' => '',
                'Show' => '',
                'Update' => '',
                'Delete' => '',
            ],
        ],
        'WpUser' => [
            'Authenticatable' => true,
            'API' => [
                'Index' => '',
                'Show' => '',
                'Update' => '',
                'Delete' => '',
            ],
            'AuthRoutes' => [
                'Login' => '',
                'PasswordReset' => '',
                'Register' => '',
            ],
        ],

    ],

];
