<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Connections
    |--------------------------------------------------------------------------
    |
    | Each connection holds provider-specific credentials. A connection name is
    | a plain string referenced by mailboxes below — never a class.
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
    | A mailbox's role follows from which config file it appears in —
    | there is no direction field.
    |
    */
    'mailboxes' => [
        'default' => [
            'driver' => env('MAIL_INBOX_DRIVER', 'msgraph'),
            'connection' => env('MAIL_INBOX_CONNECTION', 'default'),
            'address' => env('MAIL_INBOX_MAILBOX'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy flat keys (deprecated — migrate to connections + mailboxes)
    |--------------------------------------------------------------------------
    */
    'graph' => [
        'tenant_id' => env('MAIL_INBOX_TENANT_ID'),
        'client_id' => env('MAIL_INBOX_CLIENT_ID'),
        'client_secret' => env('MAIL_INBOX_CLIENT_SECRET'),
    ],

    'mailbox' => env('MAIL_INBOX_MAILBOX'),

    'processed_folder' => env('MAIL_INBOX_PROCESSED_FOLDER', 'Processed'),

    'failed_folder' => env('MAIL_INBOX_FAILED_FOLDER', 'Failed'),

    'processing_folder' => env('MAIL_INBOX_PROCESSING_FOLDER', 'Processing'),

    'poll_interval' => env('MAIL_INBOX_POLL_INTERVAL', 5),

    'delta_max_pages_per_poll' => (int) env('MAIL_INBOX_DELTA_MAX_PAGES_PER_POLL', 50),

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

];
