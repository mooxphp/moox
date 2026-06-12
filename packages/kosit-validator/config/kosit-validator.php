<?php

use Moox\KositValidator\Models\KositValidatable;

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
    | Navigation Group
    |--------------------------------------------------------------------------
    |
    | The translatable title of the navigation group in the
    | Filament Admin Panel. Instead of a translatable
    | string, you may also use a simple string.
    |
    */

    'navigation_group' => 'KoSIT Validator',

    /*
    |--------------------------------------------------------------------------
    | Base Path
    |--------------------------------------------------------------------------
    |
    | Root directory for KoSIT validator artifacts (JAR and XRechnung config).
    |
    */
    'base_path' => env('KOSIT_BASE_PATH', storage_path('app/private/kosit')),

    /*
    |--------------------------------------------------------------------------
    | Validator
    |--------------------------------------------------------------------------
    |
    | KoSIT standalone validator JAR version and download URL.
    |
    */
    'validator' => [
        'version' => env('KOSIT_VALIDATOR_VERSION', '1.6.2'),
        'download_url' => env(
            'KOSIT_VALIDATOR_URL',
            'https://github.com/itplr-kosit/validator/releases/download/v1.6.2/validator-1.6.2-standalone.jar'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | XRechnung
    |--------------------------------------------------------------------------
    |
    | XRechnung validator configuration bundle version, release date, and URL.
    |
    */
    'xrechnung' => [
        'version' => env('KOSIT_XRECHNUNG_VERSION', '3.0.2'),
        'release_date' => env('KOSIT_XRECHNUNG_RELEASE_DATE', '2026-01-31'),
        'download_url' => env(
            'KOSIT_XRECHNUNG_URL',
            'https://github.com/itplr-kosit/validator-configuration-xrechnung/releases/download/v2026-01-31/xrechnung-3.0.2-validator-configuration-2026-01-31.zip'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    |
    | Relative subdirectory names under the base path for validator and config.
    |
    */
    'paths' => [
        'validator_dir' => 'validator',
        'xrechnung_dir' => 'xrechnung',
    ],

    /*
    |--------------------------------------------------------------------------
    | Java Binary
    |--------------------------------------------------------------------------
    |
    | Executable used to run the KoSIT validator JAR.
    |
    */
    'java_binary' => env('KOSIT_JAVA_BINARY', 'java'),

    /*
    |--------------------------------------------------------------------------
    | Validation report output
    |--------------------------------------------------------------------------
    |
    | Absolute directory where KoSIT writes `{inputBasename}-report.xml` and
    | `{inputBasename}-report.html` (validator `-o` flag). Override in `.env`:
    |
    |   KOSIT_OUTPUT_PATH=/var/kosit/reports
    |
    | `KOSIT_REPORT_PATH` is still read when `KOSIT_OUTPUT_PATH` is unset.
    |
    */
    'output' => [
        'path' => env('KOSIT_OUTPUT_PATH', env('KOSIT_REPORT_PATH', storage_path('app/private/kosit-reports'))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | Filament resource settings keyed by entity slug.
    |
    */
    'resources' => [
        'kosit-validation' => [
            /*
            |--------------------------------------------------------------------------
            | Title
            |--------------------------------------------------------------------------
            |
            | The translatable title of the Resource in singular and plural.
            |
            */
            'single' => 'trans//kosit-validator::kosit-validator.kosit-validation',
            'plural' => 'trans//kosit-validator::kosit-validator.kosit-validations',

            /*
            |--------------------------------------------------------------------------
            | <Tabs></Tabs>
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
                'passed' => [
                    'label' => 'trans//kosit-validator::fields.passed',
                    'icon' => 'gmdi-check-circle',
                    'query' => [
                        [
                            'field' => 'passed',
                            'operator' => '=',
                            'value' => true,
                        ],
                    ],
                ],
                'failed' => [
                    'label' => 'trans//core::core.failed',
                    'icon' => 'gmdi-cancel',
                    'query' => [
                        [
                            'field' => 'passed',
                            'operator' => '=',
                            'value' => false,
                        ],
                    ],
                ],
                'with-warnings' => [
                    'label' => 'trans//kosit-validator::fields.with_warnings',
                    'icon' => 'gmdi-warning',
                    'query' => [
                        [
                            'field' => '__has_message_type',
                            'operator' => '=',
                            'value' => 'warning',
                        ],
                    ],
                ],
                'with-infos' => [
                    'label' => 'trans//kosit-validator::fields.with_infos',
                    'icon' => 'gmdi-info',
                    'query' => [
                        [
                            'field' => '__has_message_type',
                            'operator' => '=',
                            'value' => 'info',
                        ],
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Relations (registry)
    |--------------------------------------------------------------------------
    |
    | Declarative list of notable Eloquent relations for this entity.
    | Register owner_types when EbillingDocument (or other owners) are wired.
    |
    */
    'relations' => [
        'kosit_validatables' => [
            'label' => 'trans//kosit-validator::fields.validatables',
            'relationship' => 'kositValidatables',
            'pivot_model' => KositValidatable::class,
            'pivot_table' => 'kosit_validatables',
            'morph_name' => 'validatable',
            'pivot_columns' => [],
            'owner_types' => [
                // \Moox\EBilling\Models\EbillingDocument::class => 'Invoice document',
            ],
        ],
    ],

];
