<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Connections
    |--------------------------------------------------------------------------
    |
    | Named Graph API connections. Each entry holds Azure AD tenant credentials.
    | Reference a connection by name when building a client.
    |
    */
    'connections' => [
        'default' => [
            'tenant_id' => env('MSGRAPH_TENANT_ID'),
            'client_id' => env('MSGRAPH_CLIENT_ID'),
            'client_secret' => env('MSGRAPH_CLIENT_SECRET'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Connection
    |--------------------------------------------------------------------------
    |
    | The connection name used when none is specified explicitly.
    |
    */
    'default' => env('MSGRAPH_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Mail
    |--------------------------------------------------------------------------
    |
    | Inbox driver settings. Folder display names live only in this package —
    | consumers pass a settlement outcome, never a folder name.
    |
    */
    'mail' => [
        'folders' => [
            'processing' => env('MSGRAPH_MAIL_PROCESSING_FOLDER', 'Processing'),
            'processed' => env('MSGRAPH_MAIL_PROCESSED_FOLDER', 'Processed'),
            'failed' => env('MSGRAPH_MAIL_FAILED_FOLDER', 'Failed'),
            'ignored' => env('MSGRAPH_MAIL_IGNORED_FOLDER', 'Ignored'),
        ],
        'page_size' => (int) env('MSGRAPH_MAIL_PAGE_SIZE', 50),
        'delta_max_pages_per_poll' => (int) env('MSGRAPH_MAIL_DELTA_MAX_PAGES_PER_POLL', 50),
        'allowed_delta_hosts' => [
            'graph.microsoft.com',
            'graph.microsoft.us',
            'dod-graph.microsoft.us',
            'graph.microsoft.de',
            'microsoftgraph.chinacloudapi.cn',
        ],
    ],

];
