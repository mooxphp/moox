<?php

use Moox\UserDevice\Models\UserDevice;
use Moox\UserDevice\Resources\UserDeviceResource;

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
    | Enabled
    |--------------------------------------------------------------------------
    |
    | Master switch for device tracking on login and session sync.
    | The Filament devices resource stays available even when this is false.
    |
    */
    'enabled' => env('USER_DEVICE_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Enforce trust
    |--------------------------------------------------------------------------
    |
    | When true (default), untrusted devices are hard-blocked in Filament until
    | confirmed via email trust link or admin Trust action.
    | When false, devices are tracked only — no login gate.
    |
    */
    'enforce_trust' => env('USER_DEVICE_ENFORCE_TRUST', true),

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | The following configuration is done per Filament resource.
    |
    */
    'resources' => [
        'devices' => [
            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */
            'single' => 'trans//core::device.device',
            'plural' => 'trans//core::device.devices',

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
                'all' => [
                    'label' => 'trans//core::core.all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
            ],

            'scopes' => [
                'registry' => [
                    'origins' => [
                        'user-device' => UserDevice::class,
                    ],
                ],
            ],
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
    'navigation_group' => 'trans//core::user.users',

    /*
    |--------------------------------------------------------------------------
    | Trust link expiration (minutes)
    |--------------------------------------------------------------------------
    |
    | Signed trust links sent via email will expire after this many minutes.
    | Only used when enforce_trust is enabled (new-device mail with trust CTA).
    |
    */
    'trust_link_expires_minutes' => 60,

    /*
    |--------------------------------------------------------------------------
    | Scope UserDevice Resource to the authenticated user (self-service mode)
    |--------------------------------------------------------------------------
    |
    | When set to true, the Filament resource is always scoped to the currently
    | authenticated user (user_id + user_type), regardless of permissions.
    |
    */
    'scope_to_authenticated_user' => false,

    /*
    |--------------------------------------------------------------------------
    | Allow all devices (no Shield)
    |--------------------------------------------------------------------------
    |
    | If Shield / Spatie Permission is NOT installed, users will only see their
    | own devices by default. Enable this to show all devices in that scenario.
    |
    */
    'allow_all_devices_without_shield' => false,

    /*
    |--------------------------------------------------------------------------
    | Mail logo URL
    |--------------------------------------------------------------------------
    |
    | Either a full URL (https://...) or a public path (/logo/foo.svg).
    |
    */
    'mail_logo_url' => '/logo/logo_heco_2021.svg',

    /*
    |--------------------------------------------------------------------------
    | Audit defaults
    |--------------------------------------------------------------------------
    |
    | Registered with moox/audit when installed. Override in config/audit.php.
    | Disable this package: set enabled => false (or AUDIT_ENABLED=false globally).
    | Independent of user-device.enabled (device tracking on login).
    |
    */

    'audit' => [
        'enabled' => true,
        'models' => [
            UserDevice::class => [
                'log_name' => 'user-device',
                'attributes' => [
                    'title',
                    'slug',
                    'scope',
                    'user_id',
                    'user_type',
                    'user_agent',
                    'platform',
                    'os',
                    'browser',
                    'city',
                    'country',
                    'whitelisted',
                    'active',
                    'ip_address',
                ],
            ],
        ],
        'hooks' => [
            UserDevice::class => [
                'deleting' => [
                    'log_name' => 'user-device',
                    'entry_type' => 'log',
                    'event' => 'sessions_cleared',
                    'description' => 'sessions_cleared',
                ],
            ],
        ],
        'filament' => [
            UserDeviceResource::class => [
                'owner_model' => UserDevice::class,
            ],
        ],
    ],

];
