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
    | PHP Renderer
    |--------------------------------------------------------------------------
    |
    | true (default): shyim/mjml-php, no Node required.
    | false: Spatie mjml-php, which needs Node and the mjml npm package.
    |
    | There is no silent fallback between the two modes. A failure of the
    | selected engine is raised as an exception.
    |
    */

    'use_php_renderer' => env('MJML_USE_PHP_RENDERER', true),

];
