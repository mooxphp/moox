<?php

return [

    /*
    |--------------------------------------------------------------------------
    | TallUI Custom
    |--------------------------------------------------------------------------
    |
    | This configures custom views and custom routes.
    | See /_custom/README.md
    |
    */

    'custom_views' => explode(',', env('CUSTOM_VIEWS', 'example')),
    'custom_routes' => env('CUSTOM_ROUTES'),

];
