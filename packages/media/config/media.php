<?php

use Moox\Media\Models\Media;
use Moox\Media\Models\MediaCollection;
use Moox\Media\Models\MediaCollectionTranslation;
use Moox\Media\Models\MediaTranslation;
use Moox\Media\Resources\MediaCollectionResource;
use Moox\Media\Resources\MediaResource;

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
    | API
    |--------------------------------------------------------------------------
    |
    | The configuration for the API.
    |
    */

    'api' => [
        'middleware' => ['web', 'auth', 'throttle:60,1'],
        'prefix' => 'api/media',
        'version' => '',
    ],

    /*
    |--------------------------------------------------------------------------
    | Image driver preference
    |--------------------------------------------------------------------------
    |
    | Applied to media-library.image_driver at boot. Use "auto" to pick
    | vips → imagick → gd. Or set IMAGE_DRIVER / this value explicitly.
    |
    */

    'image_driver' => env('IMAGE_DRIVER', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Delivery (web / mail / customer_api)
    |--------------------------------------------------------------------------
    |
    | WordPress-compatible size names are canonical. Responsive srcsets use
    | those widths (and optional extra_widths) only when <= original width and
    | >= profiles.web.min_responsive_width (default 480).
    | SVG: web = original; mail = PNG.
    | customer_api is opt-in (off by default) for apps that expose media to
    | external consumers; enable/watermark in the app config/media.php.
    | Optimizer / queue / image driver: config/media-library.php.
    |
    */

    'delivery' => [
        'sizes' => [
            'thumbnail' => ['width' => 150, 'height' => 150, 'fit' => 'contain'],
            'medium' => ['width' => 300],
            'medium_large' => ['width' => 768],
            'large' => ['width' => 1024],
        ],
        // Optional extra responsive breakpoints beyond WP sizes (still no upscale).
        'extra_widths' => [480, 960, 1440],
        'quality' => [
            'jpg' => 82,
            'webp' => 80,
            'avif' => 55,
            'png' => 90,
        ],
        'raster_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/avif',
            'image/gif',
        ],
        'profiles' => [
            'web' => [
                'formats' => ['avif', 'webp', 'original'],
                'responsive' => true,
                'upscale' => false,
                // Admin sizes 150/300 stay available; web srcsets start at 480.
                'min_responsive_width' => 480,
            ],
            'mail' => [
                'formats' => ['jpg', 'png'],
                'responsive' => false,
                'upscale' => false,
                'default_width' => 600,
                'svg_to_png' => true,
            ],
            'customer_api' => [
                // Opt-in: most apps only need web + mail. Enable in the app config.
                'enabled' => false,
                'formats' => ['jpg', 'png'],
                'responsive' => false,
                'upscale' => false,
                'default_width' => 1024,
                // Skip assets tagged with custom property static_path.
                'skip_static' => true,
                // Opt-in watermark; applied only when watermark_path resolves to a file.
                'watermark' => false,
                'watermark_opacity' => 40,
                'watermark_width_percent' => 15,
                'watermark_padding_percent' => 3,
                // Absolute path, or path relative to base_path().
                'watermark_path' => null,
            ],
        ],
    ],

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
            'icon' => 'heroicon-m-arrow-up-tray',
            'disk' => config('filament.default_filesystem_disk', 'public'),
            'directory' => 'media',
            'visibility' => 'public',
            'multiple' => true,
            'max_file_size' => 10240,
            'min_file_size' => null,
            'max_files' => null,
            'min_files' => null,
            'accepted_file_types' => [
                'image/*',
                'video/*',
                'application/pdf',
                'audio/*',
                'text/*',
                'application/*',
                'application/xml',
                'text/xml',
                '.bpmn',
                'model/step',
                'model/stp',
                'application/step',
                'model/gltf+json',
                'model/gltf-binary',
                '.step',
                '.stp',
                '.gltf',
                '.glb',
            ],
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
        'api' => [
            /*
            | null = use upload.resource.accepted_file_types
            | array = API-only allowlist (can differ from the Mediathek)
            */
            'accepted_file_types' => null,
        ],
    ],

    'modal' => [
        'resource' => [
            'show_download_button' => false,
            'show_pdf_preview' => true,
        ],
    ],

    'collections' => [
        'resource' => [
            'icon' => 'heroicon-m-folder',
            'navigation_group' => 'trans//core::core.cms',
            'model_label' => 'trans//media::media.collection',
            'plural_model_label' => 'trans//media::media.collections',
        ],
    ],

    'resources' => [
        'media' => [
            'scopes' => [
                'registry' => [
                    'origins' => [
                        'media' => Media::class,
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit defaults
    |--------------------------------------------------------------------------
    |
    | Registered with moox/audit when installed. Override in config/audit.php.
    | Disable this package: set enabled => false (or AUDIT_ENABLED=false globally).
    |
    */

    'audit' => [
        'enabled' => true,
        'models' => [
            Media::class => [
                'log_name' => 'media',
                'attributes' => [
                    'file_name',
                    'disk',
                    'mime_type',
                    'size',
                    'collection_name',
                    'media_collection_id',
                    'write_protected',
                    'uploader_id',
                    'uploader_type',
                    'scope',
                ],
            ],
            MediaTranslation::class => [
                'log_name' => 'media',
                'attributes' => [
                    'name',
                    'title',
                    'alt',
                    'description',
                    'internal_note',
                ],
            ],
            MediaCollection::class => [
                'log_name' => 'media',
                'attributes' => [
                    // parent table has only id/timestamps; track create/delete lifecycle
                ],
            ],
            MediaCollectionTranslation::class => [
                'log_name' => 'media',
                'attributes' => [
                    'name',
                    'description',
                ],
            ],
        ],
        'hooks' => [
            Media::class => [
                'deleting' => [
                    'log_name' => 'media',
                    'entry_type' => 'log',
                    'event' => 'media_usables_cleared',
                    'description' => 'media_usables_cleared',
                ],
            ],
        ],
        'filament' => [
            MediaResource::class => [
                'owner_model' => Media::class,
                'aggregate_subjects' => [
                    MediaTranslation::class => 'translations',
                ],
            ],
            MediaCollectionResource::class => [
                'owner_model' => MediaCollection::class,
                'aggregate_subjects' => [
                    MediaCollectionTranslation::class => 'translations',
                ],
            ],
        ],
    ],
];
