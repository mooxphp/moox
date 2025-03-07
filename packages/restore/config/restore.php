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
    | Resources
    |--------------------------------------------------------------------------
    |
    | The following configuration is done per Filament resource.
    |
    */

    'resources' => [
        'destination' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//restore::translations.destination',
            'plural' => 'trans//restore::translations.destinations',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Resource table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
            /*'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [
                        [
                            'field' => 'deleted_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                    ],
                ],*/],
        ],
        'backup' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//restore::translations.backup',
            'plural' => 'trans//restore::translations.backups',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Resource table. They are optional, but
            | pretty awesome to filter the table by certain values.
            | You may simply do a 'tabs' => [], to disable them.
            |
            */

            'tabs' => [
            /*'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [
                        [
                            'field' => 'deleted_at',
                            'operator' => '=',
                            'value' => null,
                        ],
                    ],
                ],*/],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation Group
    |--------------------------------------------------------------------------
    |
    | The translatable title of the navigation group in the
    | Filament Admin Panel. Instead of a translatable
    | string, you may also use a simple string.
    |
    */

    'navigation_group' => 'Backup server',

    /*
    |--------------------------------------------------------------------------
    | Restore - Navigation Sort
    |--------------------------------------------------------------------------
    |
    | This value is the sort order of the navigation item in the
    | Filament Admin Panel. If you use a bunch of Moox
    | plugins, everything should be in order.
    |
    */

    'navigation_sort' => 4,

    /*
    |--------------------------------------------------------------------------
    | RestorDestination Types
    |--------------------------------------------------------------------------
    |
    | This array contains the types of restore_destination entities. You can delete
    | the types you don't need and add new ones. If you don't need
    | types, you can empty this array like this: 'types' => [],
    |
    */

    'types' => [
    /*'post' => 'Post',
        'page' => 'Page',*/],

    /*
    |--------------------------------------------------------------------------
    | Author Model
    |--------------------------------------------------------------------------
    |
    | This sets the user model that can be used as author. It should be an
    | authenticatable model and support the morph relationship.
    | It should have fields similar to Moox User or WpUser.
    |
    */

    // 'author_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Allow Slug Change - WIP
    |--------------------------------------------------------------------------
    |
    | // TODO: Work in progress.
    |
    */

    // 'allow_slug_change_after_saved' => env('ALLOW_SLUG_CHANGE_AFTER_SAVED', true),
    // 'allow_slug_change_after_publish' => env('ALLOW_SLUG_CHANGE_AFTER_PUBLISH', false),

    /*
    |--------------------------------------------------------------------------
    | Sql Configuration
    |--------------------------------------------------------------------------
    |
    |    $this->sqlFilePath = config('restore.sqlFilePath');
            $this->oldDomain = config('restore.oldDomain');
            $this->newDomain = config('restore.newDomain');
    |
    */
    'backup_host' => env('BACKUP_HOST'),
    'sql_file_name' => 'dump.sql',
    'old_domain' => 'test.com',
    'new_domain' => 'test.eu',

    /*
    |--------------------------------------------------------------------------
    | Debug mode
    |--------------------------------------------------------------------------
    |
    |   When set to true, this will log the steps which are executed during the restore process.
    */

    'debug_mode' => false,

    /*
    |--------------------------------------------------------------------------
    | Job Queue Configuration
    |--------------------------------------------------------------------------
    |
    | This section is used to configure the job queue for the restore process.
    | The 'queue_connection' parameter is used to specify the connection to be used.
    | The 'queue' parameter is used to specify the queue to be used.
    |
    */

    'queue_connection' => env('QUEUE_CONNECTION', 'redis'),
    'queue' => 'backup-restore',

];
