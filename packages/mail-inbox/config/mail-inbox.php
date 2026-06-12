<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Microsoft Graph API Credentials
    |--------------------------------------------------------------------------
    */
    'graph' => [
        'tenant_id' => env('MAIL_INBOX_TENANT_ID'),
        'client_id' => env('MAIL_INBOX_CLIENT_ID'),
        'client_secret' => env('MAIL_INBOX_CLIENT_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mailbox Configuration
    |--------------------------------------------------------------------------
    */
    'mailbox' => env('MAIL_INBOX_MAILBOX'),  // e.g. rechnungen@firma.de

    'processed_folder' => env('MAIL_INBOX_PROCESSED_FOLDER', 'Processed'),

    'failed_folder' => env('MAIL_INBOX_FAILED_FOLDER', 'Failed'),

    /*
    |--------------------------------------------------------------------------
    | Processing folder (optional UX)
    |--------------------------------------------------------------------------
    |
    | When set, FetchMailsJob moves each newly delta-persisted message into this
    | folder on the Graph side. Null or empty skips the move (backwards compatible).
    */
    'processing_folder' => env('MAIL_INBOX_PROCESSING_FOLDER', 'Processing'),

    'poll_interval' => env('MAIL_INBOX_POLL_INTERVAL', 5),  // minutes

    // Max Graph delta pages fetched per single FetchMailsJob run (initial catch-up spans multiple polls).
    'delta_max_pages_per_poll' => (int) env('MAIL_INBOX_DELTA_MAX_PAGES_PER_POLL', 50),

    'memory_limit' => env('MAIL_INBOX_MEMORY_LIMIT', '512M'),

    'retry_staleness_minutes' => env('MAIL_INBOX_RETRY_STALENESS_MINUTES', 30),

    'listener_timeout_minutes' => env('MAIL_INBOX_LISTENER_TIMEOUT_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Attachment Storage
    |--------------------------------------------------------------------------
    */
    'attachments' => [
        'disk' => env('MAIL_INBOX_ATTACHMENT_DISK', 'local'),
        'path' => env('MAIL_INBOX_ATTACHMENT_PATH', 'mail-inbox/attachments'),
    ],

    'zugferd' => [
        'path' => env('MAIL_INBOX_ZUGFERD_PATH', 'zugferd'),
        'pdf_password' => env('MAIL_INBOX_PDF_PASSWORD'),
    ],

];
