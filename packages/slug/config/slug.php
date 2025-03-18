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
    | Model Fields
    |--------------------------------------------------------------------------
    |
    | The names of the fields in your model where the title and slug are stored.
    |
    | Overridable like: TitleWithSlugInput::make(fieldTitle: 'title')
    |
    */

    'field_title' => 'title', // Overridable with parameter (fieldTitle: 'title')
    'field_slug' => 'slug', // Overridable with parameter (fieldSlug: 'title')

    /*
    |--------------------------------------------------------------------------
    | Url
    |--------------------------------------------------------------------------
    |
    | URL related config values.
    |
    */

    'url_host' => env('APP_URL'), // Overridable with parameter (urlHost: 'https://www.moox.org/')
];
