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
            'accepted_file_types' => ['image/*', 'video/*', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'audio/*', 'video/*', 'application/illustrator', 'application/eps', 'application/acad', 'application/dwg', 'application/dxf', 'message/rfc822', 'application/vnd.ms-outlook', 'application/onenote', 'application/zip', 'application/x-zip-compressed', 'application/x-rar-compressed'],
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
