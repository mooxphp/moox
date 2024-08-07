<?php

/*
|--------------------------------------------------------------------------
| Moox Configuration
|--------------------------------------------------------------------------
|
| This configuration file uses translatable strings. If you want to
| translate the strings, you can do so in the language files
| published from moox_core. Example:
|
| 'trans//core::common.all',
| loads from common.php
| outputs 'All'
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Moox Packages
    |--------------------------------------------------------------------------
    |
    | This config array contains all Moox packages and their models.
    | You may add your own packages and models to this array.
    | Only edit the array, if you know what you're doing.
    |
    */

    'packages' => [
        'audit' => [
            'package' => 'Moox Audit',
            'models' => [
                'Audit' => [
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
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
                'FailedJob' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
                'JobBatch' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
                'JobManager' => [
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
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
            ],
        ],
        'notifications' => [
            'package' => 'Moox Notifications',
            'models' => [
                'Notification' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
            ],
        ],
        'page' => [
            'package' => 'Moox Page',
            'models' => [
                'Page' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
            ],
        ],
        'passkey' => [
            'package' => 'Moox Passkey',
            'models' => [
                'Passkey' => [
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
                'WpComment' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
                'WpCommentMeta' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
                'WpOption' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
                'WpPost' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
                'WpPostMeta' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
                'WpTerm' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
                'WpTermMeta' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
                'WpTermRelationship' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
                'WpTermTaxonomy' => [
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
                'WpUserMeta' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
            ],
        ],
        'security' => [
            'package' => 'Moox Security',
            'models' => [
                'ResetPassword' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
            ],
        ],
        'sync' => [
            'package' => 'Moox Sync',
            'models' => [
                'Platform' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
                'Sync' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
            ],
        ],
        'training' => [
            'package' => 'Moox Training',
            'models' => [
                'Training' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
                'TrainingDate' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
                'TrainingInvitation' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
                'TrainingType' => [
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
                ],
            ],
        ],
        'user-device' => [
            'package' => 'Moox User Device',
            'models' => [
                'UserDevice' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
            ],
        ],
        'user-session' => [
            'package' => 'Moox User Session',
            'models' => [
                'UserSession' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                ],
            ],
        ],
    ],
];
