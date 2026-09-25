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
    | Output Directory
    |--------------------------------------------------------------------------
    |
    | Where generated XML/PDF files are stored.
    |
    */

    'output_path' => storage_path('app/private/zugferd'),

    /*
    |--------------------------------------------------------------------------
    | PDF metadata Title templates
    |--------------------------------------------------------------------------
    |
    | Used by ZugferdConverter::mergePdfWithXml() via horstoeko setTitleTemplate().
    | sprintf placeholders (horstoeko):
    |   %1$s = invoice id
    |   %2$s = document type name (e.g. Invoice)
    |   %3$s = seller name
    |   %4$s = date
    |
    | pdf_title_template — default when no per-code match (space before colon).
    | pdf_title_templates — optional map of UN/CEFACT document type code → template.
    |
    */

    'pdf_title_template' => '%3$s : %2$s %1$s',

    'pdf_title_templates' => [
        // '380' => 'Invoice No.: %1$s',
        // '381' => 'Credit note No.: %1$s',
    ],

];
