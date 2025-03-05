<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Moox Custom
    |--------------------------------------------------------------------------
    |
    | This configures custom views and custom routes.
    | See /_custom/README.md
    |
    */

    'custom_views' => explode(',', (string) env('CUSTOM_VIEWS', 'example')),
    'custom_routes' => env('CUSTOM_ROUTES'),

];
