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
    | ZUGFeRD Profile
    |--------------------------------------------------------------------------
    |
    | The profile to use when generating ZUGFeRD/XRechnung documents.
    | Options: MINIMUM, BASIC, EN16931, EXTENDED, XRECHNUNG
    |
    | Use XRECHNUNG for German e-invoicing compliance.
    |
    */

    'profile' => env('ZUGFERD_PROFILE', 'XRECHNUNG'),

    /*
    |--------------------------------------------------------------------------
    | Output Directory
    |--------------------------------------------------------------------------
    |
    | Where generated XML/PDF files are stored.
    |
    */

    'output_path' => storage_path('app/private/zugferd'),

];
