<?php

return [

    'packages' => [
        'audit' => [
            'package' => 'Moox Audit',
            'models' => [
                'Audit' => [
                    'authenticatable' => false,
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
            ],
        ],
        'builder' => [
            'package' => 'Moox Builder',
            'models' => [
                'Builder' => [
                    'authenticatable' => false,
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
            ],
        ],
        'core' => [
            'package' => 'Moox Core',
            'models' => [],
        ],
        'expiry' => [
            'package' => 'Moox Expiry',
            'models' => [
                'Expiry' => [
                    'authenticatable' => false,
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
            ],
        ],
        'jobs' => [
            'package' => 'Moox Jobs',
            'models' => [
                'Job' => [
                    'authenticatable' => false,
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
                'FailedJob' => [
                    'authenticatable' => false,
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
                'JobBatch' => [
                    'authenticatable' => false,
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
                'JobManager' => [
                    'authenticatable' => false,
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
            ],
        ],
        'login-link' => [
            'package' => 'Moox Login Link',
            'models' => [
                'LoginLink' => [
                    'authenticatable' => false,
                    'api' => [
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
        ],
        'notifications' => [
            'package' => 'Moox Notifications',
            'models' => [
                'Notification' => [
                    'authenticatable' => false,
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
            ],
        ],
        'press' => [
            'package' => 'Moox Press',
            'models' => [
                'WpPost' => [
                    'authenticatable' => false,
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
                'WpUser' => [
                    'authenticatable' => true,
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
            ],
        ],
        'user' => [
            'package' => 'Moox User',
            'models' => [
                'User' => [
                    'authenticatable' => true,
                    'api' => [
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
        ],
    ],
];
