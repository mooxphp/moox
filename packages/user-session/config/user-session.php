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
    | Resources
    |--------------------------------------------------------------------------
    |
    | The following configuration is done per Filament resource.
    |
    */

    'resources' => [
        'session' => [

            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */

            'single' => 'trans//core::session.session',
            'plural' => 'trans//core::session.sessions',

            /*
            |--------------------------------------------------------------------------
            | Tabs
            |--------------------------------------------------------------------------
            |
            | Define the tabs for the Expiry table. They are optional, but
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
    | Audit - Navigation Sort
    |--------------------------------------------------------------------------
    |
    | This value is the sort order of the navigation item in the
    | Filament Admin Panel. If you use a bunch of Moox
    | plugins, everything should be in order.
    |
    */

    'navigation_sort' => 6400,

    /*
    | You can provide multiple user models for session management.
    | And you can use your own device model to store the device information.
    */
    'user_models' => [
        'App Users' => \App\Models\User::class,
        'Moox Users' => \Moox\User\Models\User::class,
    ],
    'device_model' => \Moox\UserDevice\Models\UserDevice::class,

    /*
    | Session expiry for different scopes, currently not implemented!
    */
    'session-expiry' => [
        'Default' => 1, // day
        'Whitelisted' => 365, // days
    ],

    /*
    | Whitelisted IPs or IP ranges can be used to extend the session expiry.
    | They are also used to distinguish between internal and external IPs
    | in the user session list.
    */
    'whitelisted-ips' => [
        'heco Network' => '62.26.138.101',
    ],

    /*
    | Known IPs can be associated with a name. This is useful to identify
    | services like monitoring platforms in the user session list.
    */
    'known-ips' => [
        'Oh Dear' => ['104.236.192.248',
            '13.245.37.23',
            '134.209.122.208',
            '134.209.243.63',
            '138.68.138.12',
            '138.68.138.180',
            '139.59.61.47',
            '15.185.161.92',
            '158.247.212.166',
            '159.203.38.39',
            '159.203.60.48',
            '164.90.176.102',
            '165.22.36.80',
            '165.22.95.234',
            '165.227.31.113',
            '167.172.97.55',
            '167.71.54.244',
            '178.62.45.205',
            '198.13.48.175',
            '199.247.12.43',
            '199.247.9.185',
            '217.19.225.103',
            '217.69.10.40',
            '23.88.67.24',
            '45.32.107.252',
            '45.32.146.84',
            '45.32.147.8',
            '45.32.189.194',
            '45.63.54.118',
            '45.76.236.54',
            '45.76.46.251',
            '45.76.59.200',
            '46.101.122.201',
            '54.94.4.151',
            '95.179.211.184',
            '2001:19f0:4400:737b:5400:3ff:fe1a:5634',
            '2001:19f0:5801:1e90:5400:1ff:fecb:f1f2',
            '2001:19f0:6001:5ef0:5400:3ff:fe1a:5666',
            '2001:19f0:6401:19d5:5400:2ff:feb3:6d28',
            '2001:19f0:6401:f5a:5400:1ff:fe57:57e9',
            '2001:19f0:6801:157b:5400:2ff:feda:fb72',
            '2001:19f0:6801:15d7:5400:1ff:fef9:8e1d',
            '2001:19f0:6801:5f3:5400:1ff:fe38:3572',
            '2001:19f0:6801:9d3:5400:2ff:feda:fb6e',
            '2001:19f0:7002:3d5:5400:02ff:fedb:8dc0',
            '2400:6180:100:d0::12:e001',
            '2401:c080:1c02:b4:5400:3ff:fe1a:56a2',
            '2604:a880:2:d0::15d9:c001',
            '2604:a880:400:d0::1a7d:4001',
            '2604:a880:cad:d0::7aa:6001',
            '2a01:4f8:272:426a::2',
            '2a03:b0c0:1:d0::81:e001',
            '2a03:b0c0:3:d0::10b8:8001',
            '2a03:b0c0:3:d0::4f5:f001',
            '2a03:b0c0:3:e0::220:2001',
            '2a03:b0c0:3:e0::2a8:6001',
            '2a03:b0c0:3:e0::386:1',
            '2a03:b0c0:3:f0::173:0',
            '2a05:f480:1c00:5f0:5400:3ff:fe50:e0c5',
            '2a05:f480:1c00:7dd:5400:2ff:fe68:af7',
            '2a05:f480:1c00:c80:5400:2ff:feda:ca89',
        ],
    ],
];
