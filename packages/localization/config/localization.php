<?php

use Moox\Localization\Filament\Resources\LocalizationResource;
use Moox\Localization\Models\Localization;

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
    | Title
    |--------------------------------------------------------------------------
    |
    | The translatable title of the Resource in singular and plural.
    |
    */
    'single' => 'trans//localization::localization.localization',
    'plural' => 'trans//localization::localization.localizations',

    /*
    |--------------------------------------------------------------------------
    | Tabs
    |--------------------------------------------------------------------------
    |
    | The translatable title, icon and query of the Tabs.
    |
    */
    'tabs' => [

    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    |
    | The navigation group of the Resource,
    | and if the panel is enabled.
    |
    */
    'navigation_group' => 'trans//core::core.system',
    'enable-panel' => false,

    /*
    |--------------------------------------------------------------------------
    | Audit defaults
    |--------------------------------------------------------------------------
    |
    | Registered with moox/audit when installed. Override in config/audit.php.
    | Disable this package: set enabled => false (or AUDIT_ENABLED=false globally).
    |
    */

    'audit' => [
        'enabled' => true,
        'models' => [
            Localization::class => [
                'log_name' => 'localization',
                'attributes' => [
                    'language_id',
                    'title',
                    'slug',
                    'locale_variant',
                    'fallback_language_id',
                    'is_active_admin',
                    'is_active_frontend',
                    'is_default',
                    'fallback_behaviour',
                    'language_routing',
                    'routing_path',
                    'routing_subdomain',
                    'routing_domain',
                    'translation_status',
                    'use_native_names',
                    'show_regional_variants',
                    'use_country_translations',
                    'use_country_icon',
                ],
            ],
        ],
        'filament' => [
            LocalizationResource::class => [
                'owner_model' => Localization::class,
            ],
        ],
    ],

];
