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
    | Connections
    |--------------------------------------------------------------------------
    |
    | Named connection slots referenced by mailboxes. Credential keys depend on
    | the driver package that consumes each connection.
    |
    */
    'connections' => [
        'default' => [
            'tenant_id' => env('MAIL_INBOX_TENANT_ID'),
            'client_id' => env('MAIL_INBOX_CLIENT_ID'),
            'client_secret' => env('MAIL_INBOX_CLIENT_SECRET'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Mailboxes
    |--------------------------------------------------------------------------
    |
    | Each mailbox names a driver and references a connection by name.
    | The mailbox name is the pipeline `scope` (e.g. FetchMailsJob scope).
    |
    */
    'mailboxes' => [
        'default' => [
            'driver' => env('MAIL_INBOX_DRIVER'),
            'connection' => env('MAIL_INBOX_CONNECTION', 'default'),
            'address' => env('MAIL_INBOX_MAILBOX'),
        ],
    ],

    'poll_interval' => env('MAIL_INBOX_POLL_INTERVAL', 5),

    'delta_max_pages_per_poll' => (int) env('MAIL_INBOX_DELTA_MAX_PAGES_PER_POLL', 50),

    'cursor_reset_max_per_run' => (int) env('MAIL_INBOX_CURSOR_RESET_MAX_PER_RUN', 1),

    'cursor_reset_warning_minutes' => (int) env('MAIL_INBOX_CURSOR_RESET_WARNING_MINUTES', 60),

    'memory_limit' => env('MAIL_INBOX_MEMORY_LIMIT', '512M'),

    'retry_staleness_minutes' => env('MAIL_INBOX_RETRY_STALENESS_MINUTES', 30),

    'listener_timeout_minutes' => env('MAIL_INBOX_LISTENER_TIMEOUT_MINUTES', 5),

    'attachments' => [
        'disk' => env('MAIL_INBOX_ATTACHMENT_DISK', 'local'),
        'path' => env('MAIL_INBOX_ATTACHMENT_PATH', 'mail-inbox/attachments'),
    ],

    'zugferd' => [
        'path' => env('MAIL_INBOX_ZUGFERD_PATH', 'zugferd'),
        'pdf_password' => env('MAIL_INBOX_PDF_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | Filament operator UI for inbox messages and mailbox sync state.
    |
    */
    'resources' => [
        'inbox-messages' => [

            'single' => 'trans//mail-inbox::mail-inbox.message',
            'plural' => 'trans//mail-inbox::mail-inbox.messages',

            'tabs' => [
                'all' => [
                    'label' => 'trans//mail-inbox::fields.tab_all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
                'new' => [
                    'label' => 'trans//mail-inbox::fields.tab_new',
                    'icon' => 'gmdi-mark-email-unread',
                    'query' => [
                        [
                            'field' => 'processing_status',
                            'operator' => '=',
                            'value' => 'new',
                        ],
                    ],
                ],
                'failed' => [
                    'label' => 'trans//mail-inbox::fields.tab_failed',
                    'icon' => 'gmdi-error',
                    'query' => [
                        [
                            'field' => 'processing_status',
                            'operator' => 'in',
                            'value' => ['failed', 'partially_failed'],
                        ],
                    ],
                ],
                'processed' => [
                    'label' => 'trans//mail-inbox::fields.tab_processed',
                    'icon' => 'gmdi-check-circle',
                    'query' => [
                        [
                            'field' => 'processing_status',
                            'operator' => '=',
                            'value' => 'processed',
                        ],
                    ],
                ],
            ],
        ],

        'sync-states' => [

            'single' => 'trans//mail-inbox::mail-inbox.sync_state',
            'plural' => 'trans//mail-inbox::mail-inbox.sync_states',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    |
    | The navigation group for both inbox resources.
    |
    */
    'navigation_group' => 'trans//mail-inbox::mail-inbox.navigation_group',

];
