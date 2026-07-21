<?php

use Moox\VeraPdf\Models\VeraPdfValidatable;

/*
| Moox Configuration
|
| This configuration file uses translatable strings. If you want to
| translate the strings, you can do so in the language files
| published from moox_core.
|
*/

return [

    /*
    | Base Path
    |
    | Root directory for the veraPDF installation (launcher + binaries).
    |
    */
    'base_path' => env('VERAPDF_BASE_PATH', storage_path('app/private/verapdf')),

    /*
    | Installer
    |
    | Greenfield veraPDF installer zip version, download URL, and pinned SHA-256.
    | Pin 1.30.1+ — that release splits CLI/GUI packs; auto-install selects CLI only.
    | Update sha256 whenever version or download_url changes (see SECURITY.md).
    |
    */
    'installer' => [
        'version' => env('VERAPDF_VERSION', '1.30.1'),
        'download_url' => env(
            'VERAPDF_DOWNLOAD_URL',
            'https://software.verapdf.org/releases/1.30/verapdf-greenfield-1.30.1-installer.zip'
        ),
        'sha256' => env(
            'VERAPDF_INSTALLER_SHA256',
            '9f03fc5da454348329f4054256351aa6c6a91683329978e8294f21fe8a5d7abc'
        ),
    ],

    /*
    | Paths
    |
    | Relative launcher name under the base path. On Windows the service
    | appends `.bat` when needed.
    |
    */
    'paths' => [
        'launcher' => 'verapdf',
    ],

    /*
    | Java Binary
    |
    | Executable used for headless IzPack install and by the veraPDF launcher.
    |
    */
    'java_binary' => env('VERAPDF_JAVA_BINARY', 'java'),

    /*
    | PDF/A Flavour
    |
    | Built-in veraPDF profile code. Default is PDF/A-3b (3b).
    | Other PDF/A-3 options: 3a, 3u.
    |
    */
    'flavour' => env('VERAPDF_FLAVOUR', '3b'),

    /*
    | Validation report output
    |
    | Directory where VeraPdfService writes `{inputBasename}-report.xml`
    | (and optional HTML). Override in `.env`:
    |
    |   VERAPDF_OUTPUT_PATH=/var/verapdf/reports
    |
    */
    'output' => [
        'path' => env('VERAPDF_OUTPUT_PATH', storage_path('app/private/verapdf-reports')),
    ],

    /*
    | Relations (registry)
    |
    | Declarative list of notable Eloquent relations for this entity.
    | Register owner_types when domain models are wired.
    |
    */
    'relations' => [
        'verapdf_validatables' => [
            'label' => 'trans//verapdf::fields.validatables',
            'relationship' => 'veraPdfValidatables',
            'pivot_model' => VeraPdfValidatable::class,
            'pivot_table' => 'verapdf_validatables',
            'morph_name' => 'validatable',
            'pivot_columns' => [],
            'owner_types' => [
                // Register owner model FQCNs here when wiring morph history.
            ],
        ],
    ],

];
