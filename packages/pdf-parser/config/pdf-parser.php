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
    | pdftotext Binary Path
    |--------------------------------------------------------------------------
    |
    | Path to the pdftotext binary. Defaults to storage/app/private/pdf-parser
    | where you can place a vendored or copied binary.
    |
    */

    'pdftotext_path' => env('PDFTOTEXT_PATH', storage_path('app/private/pdf-parser/pdftotext')),

];
