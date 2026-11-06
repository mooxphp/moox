<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Max storage plan (GB)
    |--------------------------------------------------------------------------
    |
    | Optional storage quota used by the Backups list stats. When set, Size and
    | Real size percentages are calculated against this plan instead of against
    | each other. Leave null/empty to keep the relative Real size / Size share.
    |
    */
    'max_storage_plan_gb' => env('BACKUP_MAX_STORAGE_PLAN_GB'),

    'backup_source' => [
        'inline_help' => [
            'fields' => [
                'name' => [
                    'title' => '',
                    'url' => '',
                ],
                'host' => [
                    'title' => '',
                    'url' => '',
                ],
                'ssh_user' => [
                    'title' => '',
                    'url' => '',
                ],
                'ssh_port' => [
                    'title' => '',
                    'url' => '',
                ],
                'ssh_private_key_file' => [
                    'title' => '',
                    'url' => '',
                ],
                'cron_expression' => [
                    'title' => '',
                    'url' => '',
                ],
                'destination_id' => [
                    'title' => '',
                    'url' => '',
                ],
                'pre_backup_commands' => [
                    'title' => '',
                    'url' => 'https://github.com/mooxphp/backup-server-ui/#pre_backup_commands',
                ],
                'post_backup_commands' => [
                    'title' => '',
                    'url' => 'https://moox.org/docs/backup-server-ui/#post_backup_commands',
                ],
                'includes' => [
                    'title' => '',
                    'url' => 'includes',
                ],
                'excludes' => [
                    'title' => '',
                    'url' => '',
                ],
                'cleanup_strategy_class' => [
                    'title' => '',
                    'url' => '',
                ],
                'keep_all_backups_for_days' => [
                    'title' => '',
                    'url' => '',
                ],
                'keep_daily_backups_for_days' => [
                    'title' => '',
                    'url' => '',
                ],
                'keep_weekly_backups_for_weeks' => [
                    'title' => '',
                    'url' => '',
                ],
                'keep_monthly_backups_for_months' => [
                    'title' => '',
                    'url' => '',
                ],
                'keep_yearly_backups_for_years' => [
                    'title' => '',
                    'url' => '',
                ],
                'delete_oldest_backups_when_using_more_megabytes_than' => [
                    'title' => '',
                    'url' => '',
                ],
                'healthy_maximum_backup_age_in_days' => [
                    'title' => '',
                    'url' => 'url',
                ],
                'healthy_maximum_storage_in_mb' => [
                    'title' => '',
                    'url' => '',
                ],
            ],
            'icon' => 'heroicon-c-question-mark-circle',
        ],
    ],

];
