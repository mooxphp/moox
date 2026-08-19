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

];
