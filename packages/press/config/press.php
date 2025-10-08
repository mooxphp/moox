<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Press Mapping
    |--------------------------------------------------------------------------
    |
    | This configuration is used to map Moox Entities to WordPress.
    |
    */
    'mapping' => [
        'post' => [
            'moox/post' => 'post',
            'moox/page' => 'page',
            'moox/media' => 'attachment',
            'moox/product' => 'product',
        ],
        'taxonomy' => [
            'moox/category' => 'category',
            'moox/tag' => 'tag',
        ],
        'user' => [
            'moox/user' => 'user',
            'moox/permission' => 'capability',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Press Roles
    |--------------------------------------------------------------------------
    |
    | Mapping roles for user (BE), webuser (FE) and customer (Shop).
    |
    */
    'roles' => [
        'user' => [
            'super_admin',
            'administrator',
            'editor',
            'author',
            'contributor',
            'shop_manager',
        ],
        'webuser' => [
            'subscriber',
        ],
        'customer' => [
            'customer',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Press Routing
    |--------------------------------------------------------------------------
    |
    | Enable or Disable WordPress Features and redirects.
    |
    */
    'enable_features' => [
        'enable_wpdebug' => env('ENABLE_WPDEBUG', false),
        'enable_wpadmin' => env('ENABLE_WPADMIN', false),
        'enable_wplogin' => env('ENABLE_WPLOGIN', false),
        'enable_website' => env('ENABLE_WEBSITE', false),
        'secure_website' => env('SECURE_WEBSITE', false),
        'redirect_to_wp' => env('REDIRECT_TO_WP', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Press Revisions
    |--------------------------------------------------------------------------
    |
    | How many revisions should be kept in sync between Laravel and WP.
    |
    */
    'revisions' => [
        'enabled' => true,
        'max' => 10,
    ],

];
