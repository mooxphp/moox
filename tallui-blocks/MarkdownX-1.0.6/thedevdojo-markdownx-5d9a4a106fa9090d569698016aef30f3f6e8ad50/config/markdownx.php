<?php

/*
 * This file is the config file for MarkdownX https://devdojo.com/markdownx.
 *
 * (c) Tony Lea <tony@devdojo.com>
 *
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Image functionality
    |--------------------------------------------------------------------------
    |
    | This option specifies image functionality in the MarkdownX editor
    |
    |       allowed_file_type - the allowed file types
    |       max_file_size - max file size in KB
    |
    */

    'image' => [
        'allowed_file_types' => ['png', 'jpg', 'jpeg', 'gif'],
        'max_file_size' => 5000
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Config
    |--------------------------------------------------------------------------
    |
    | Specify the storage disk for file uploads in the MarkdownX Editor
    |
    */

    'storage' => [
        'disk' => 'public',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dropdown Items Config
    |--------------------------------------------------------------------------
    |
    | Below you can specify the items you want to be shown in your MarkdownX
    | dropdown menu.
    |
    */

    'dropdown_items' => [
        "text",
        "heading",
        "heading_2",
        "heading_3",
        "image",
        "code",
        "link",
        "divider",
        "bulleted_list",
        "numbered_list",
        "quote",
        //"giphy",
        // "codepen",
        // "codesandbox",
        // "youtube",
        // "buy_me_a_coffee"
    ],

    'integrations' => [
        'giphy' => [
            'api_key' => env('MARKDOWNX_GIPHY_API_KEY', '')
        ]
    ]

];
