<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Connections
    |--------------------------------------------------------------------------
    |
    | Named connection slots referenced by mailboxes. Credential keys depend on
    | the driver package that consumes each connection. Legacy GraphMailService
    | (still registered until removed) reads `connections.default` tenant credentials.
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
    | A mailbox's role follows from which config file it appears in —
    | there is no direction field.
    |
    | `driver` must be set explicitly — register the driver in your adapter
    | package and reference it here (e.g. env('MAIL_INBOX_DRIVER')).
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

    /*
    |--------------------------------------------------------------------------
    | Sync cursor reset bounds
    |--------------------------------------------------------------------------
    |
    | When a driver rejects a stored cursor as expired, FetchMailsJob clears it
    | and starts a fresh sync. cursor_reset_max_per_run caps how many times that
    | may happen in one job run (default 1 — one legitimate expiry needs one reset).
    | cursor_reset_warning_minutes logs a warning when another reset happens within
    | that window across separate runs.
    |
    */
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

];
