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
    | Title
    |--------------------------------------------------------------------------
    |
    | The translatable title of the Resource in singular and plural.
    |
    */

    'model_label' => 'trans//media::media.media',
    'plural_model_label' => 'trans//media::media.medias',

    /*
    |--------------------------------------------------------------------------
    | Navigation Group
    |--------------------------------------------------------------------------
    |
    | The translatable title of the navigation group in the
    | Filament Admin Panel. Instead of a translatable
    | string, you may also use a simple string.
    |
    */

    'navigation_group' => 'trans//core::core.cms',

    /*
    |--------------------------------------------------------------------------
    | Upload
    |--------------------------------------------------------------------------
    |
    | The configuration for the upload feature.
    |
    */

    'upload' => [
        'resource' => [
            'disk' => config('filament.default_filesystem_disk', 'public'),
            'directory' => 'media',
            'visibility' => 'public',
            'multiple' => true,
            'max_file_size' => 10240,
            'min_file_size' => null,
            'max_files' => null,
            'min_files' => null,
            'accepted_file_types' => ['image/*', 'video/*', 'application/pdf'],
            'image_resize_mode' => 'cover',
            'image_crop_aspect_ratio' => null,
            'image_resize_target_width' => null,
            'image_resize_target_height' => null,
            'image_editor' => [
                'enabled' => true,
                'aspect_ratios' => [
                    null,
                    '16:9',
                    '4:3',
                    '1:1',
                ],
                'viewport_width' => '1920',
                'viewport_height' => '1080',
                'mode' => 1,
                'empty_fill_color' => 'transparent',
            ],
            'panel_layout' => 'grid',
            'orientation_from_exif' => true,
            'show_download_button' => true,
            'show_open_button' => true,
            'show_preview' => true,
            'reorderable' => true,
            'append_files' => true,
        ],
    ],
];
