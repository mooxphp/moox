<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Audit logging
    |--------------------------------------------------------------------------
    |
    | When moox/audit is installed, enter/leave impersonation is written as
    | activity log entries. Disable to keep the STS package behaviour only.
    |
    */

    'audit' => [
        'enabled' => env('MOOX_IMPERSONATE_AUDIT', true),
        'log_name' => env('MOOX_IMPERSONATE_AUDIT_LOG', 'impersonate'),
    ],

];
