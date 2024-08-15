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
| 'trans//core::core.all',
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
                    // Todo: Not implemented yet, uses default policy
                    // 'model' => \Moox\Audit\Models\Audit::class,
                    //'policy' => \Moox\Audit\Policies\AuditPolicy::class,
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
                    'model' => \Moox\Builder\Models\Item::class,
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
                    'model' => \Moox\Expiry\Models\Expiry::class,
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
                    'model' => \Moox\Jobs\Models\Job::class,
                ],
                'FailedJob' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                    'model' => \Moox\Jobs\Models\FailedJob::class,
                ],
                'JobBatch' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                    'model' => \Moox\Jobs\Models\JobBatch::class,
                ],
                'JobManager' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                    'model' => \Moox\Jobs\Models\JobManager::class,
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
                    'model' => \Moox\LoginLink\Models\LoginLink::class,
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
                    'model' => \Moox\Notification\Models\Notification::class,
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
                    'model' => \Moox\Page\Models\Page::class,
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
                    'model' => \Moox\Passkey\Models\Passkey::class,
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
                    'model' => \Moox\Press\Models\WpComment::class,
                ],
                'WpCommentMeta' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                    'model' => \Moox\Press\Models\WpCommentMeta::class,
                ],
                'WpOption' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                    'model' => \Moox\Press\Models\WpOption::class,
                ],
                'WpPost' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                    'model' => \Moox\Press\Models\WpPost::class,
                ],
                'WpPostMeta' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                    'model' => \Moox\Press\Models\WpPostMeta::class,
                ],
                'WpTerm' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                    'model' => \Moox\Press\Models\WpTerm::class,
                ],
                'WpTermMeta' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                    'model' => \Moox\Press\Models\WpTermMeta::class,
                ],
                'WpTermRelationship' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                    'model' => \Moox\Press\Models\WpTermRelationship::class,
                ],
                'WpTermTaxonomy' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                    'model' => \Moox\Press\Models\WpTermTaxonomy::class,
                ],
                'WpUser' => [
                    'authenticatable' => true,
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                    'model' => \Moox\Press\Models\WpUser::class,
                ],
                'WpUserMeta' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                    'model' => \Moox\Press\Models\WpUserMeta::class,
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
                    'model' => \Moox\Security\Models\ResetPassword::class,
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
                    'model' => \Moox\Sync\Models\Platform::class,
                    // Todo: Not implemented yet, uses default policy
                    //'policy' => \Moox\Sync\Policies\PlatformPolicy::class,
                ],
                'Sync' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                    'model' => \Moox\Sync\Models\Sync::class,
                    // Todo: Not implemented yet, uses default policy
                    //'policy' => \Moox\Sync\Policies\SyncPolicy::class,
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
                    'model' => \Moox\Training\Models\Training::class,
                ],
                'TrainingDate' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                    'model' => \Moox\Training\Models\TrainingDate::class,
                ],
                'TrainingInvitation' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                    'model' => \Moox\Training\Models\TrainingInvitation::class,
                ],
                'TrainingType' => [
                    'api' => [
                        'Index' => '',
                        'Show' => '',
                        'Update' => '',
                        'Delete' => '',
                    ],
                    'model' => \Moox\Training\Models\TrainingType::class,
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
                    'model' => \Moox\User\Models\User::class,
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
                    'model' => \Moox\UserDevice\Models\UserDevice::class,
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
                    'model' => \Moox\UserSession\Models\UserSession::class,
                ],
            ],
        ],
    ],
];
