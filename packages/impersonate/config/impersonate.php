<?php

$targets = array_values(array_filter(array_map(
    static fn (string $target): string => trim($target),
    explode(',', (string) env('MOOX_IMPERSONATE_TARGETS', 'finance,portal')),
)));

return [

    /*
    |--------------------------------------------------------------------------
    | Master switch
    |--------------------------------------------------------------------------
    |
    | When false, impersonation is disabled everywhere (UI and canImpersonate /
    | canBeImpersonated checks that call ImpersonateAction::isEnabled()).
    |
    */

    'enabled' => env('MOOX_IMPERSONATE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Allowed targets
    |--------------------------------------------------------------------------
    |
    | Comma-separated panel/guard keys, e.g. "finance,portal".
    | Host code calls ImpersonateAction::isEnabled('finance').
    | Empty list = no targets may be impersonated.
    |
    */

    'targets' => $targets,

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
