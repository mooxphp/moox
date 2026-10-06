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
    | When false: no tracking, no trust middleware, no devices resource —
    | even if UserDevicePlugin is listed on a panel.
    | When true: only panels that register UserDevicePlugin get track/mail/trust.
    |
    */
    'enabled' => env('USER_DEVICE_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Enforce trust
    |--------------------------------------------------------------------------
    |
    | When true (default), untrusted devices are hard-blocked in Filament until
    | confirmed via email trust link or admin Trust action, and the new-device
    | mail includes a trust CTA.
    | When false, devices are tracked only — no login gate; new-device mail is
    | still sent without a trust CTA (notify-only).
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
    | Panels that show the devices resource
    |--------------------------------------------------------------------------
    |
    | Only when enabled: Filament resource (nav + /user-devices) registers on
    | these panel IDs. Tracking, trust middleware and new-device mail only run
    | on panels that register UserDevicePlugin (admin/portal). Empty array =
    | no resource UI anywhere; plugin still controls logic per panel.
    |
    */
    'resource_panels' => ['admin'],

    /*
    |--------------------------------------------------------------------------
    | Extra auth models per panel (device tabs)
    |--------------------------------------------------------------------------
    |
    | Admin device tabs are built from panels that register UserDevicePlugin,
    | filtering by each panel's auth model. Add legacy or additional morph
    | types here when needed.
    |
    */
    'panel_user_types' => [],

    /*
    |--------------------------------------------------------------------------
    | Mail logo URL
    |--------------------------------------------------------------------------
    |
    | Either a full URL (https://...) or a public path (/logo/foo.svg).
    | Used only for the Blade fallback when moox/mail-template is unavailable.
    |
    */
    'mail_logo_url' => '/logo/logo_heco_2021.svg',

    /*
    |--------------------------------------------------------------------------
    | Mail template slug
    |--------------------------------------------------------------------------
    |
    | When moox/mail-template is installed, NewDeviceNotification renders this
    | MailTemplate slug (layout login-link) instead of the package Blade view.
    |
    */
    'mail_template_slug' => env('USER_DEVICE_MAIL_TEMPLATE_SLUG', 'new-device'),

    /*
    |--------------------------------------------------------------------------
    | Outbound mail
    |--------------------------------------------------------------------------
    |
    | enabled: when false, a new device is still recorded but no mail is sent.
    | Package default is on so existing installs keep mailing; a host that
    | must not emit mail defaults this off and opts in with
    | USER_DEVICE_MAIL_ENABLED=true.
    | mailer: Laravel mailer name. Empty uses the application default.
    | outbox sends the new-device mail through moox/mail-outbox. test_mode
    | forces that send into the outbox sandbox. Both default off here so
    | other apps keep the notification mail channel; the host opts in.
    |
    */

    'mail' => [
        'enabled' => (bool) env('USER_DEVICE_MAIL_ENABLED', true),
        'outbox' => (bool) env('USER_DEVICE_MAIL_OUTBOX', false),
        'mailer' => env('USER_DEVICE_MAILER'),
        'test_mode' => (bool) env('USER_DEVICE_MAIL_TEST_MODE', true),
    ],

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
