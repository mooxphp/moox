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
    | Delivery profiles
    |--------------------------------------------------------------------------
    |
    | Each key under profiles is a delivery profile apps can extend or replace.
    | Package ships web / mail / customer_api defaults only — no brand assets.
    |
    | Per profile:
    | - enabled (bool, default true)
    | - mode: responsive | single
    | - formats: e.g. avif, webp, jpg, png, original
    | - upscale (bool)
    | - min_responsive_width (responsive)
    | - widths (responsive, optional) — else global extra_widths
    | - default_width (single)
    | - fallback_to_original (bool)
    | - svg: original | png | skip
    | - skip_static (bool) — skip media with custom property static_path
    | - watermark: { enabled, path, opacity, width_percent, padding_percent, position, fit }
    |   Applied via MediaWatermark (Spatie Image). path = app file only (base_path relative OK).
    |
    | Responsive: originals >= min_responsive_width get a width ladder (no upscale).
    | Smaller rasters get same-size modern formats only (no multi-width srcset).
    | skip_mime_types (default image/gif) skip delivery profiles (original kept).
    | Optimizer / queue / driver: config/media-library.php.
    |
    */

    'delivery' => [
        'sizes' => [
            'thumbnail' => ['width' => 150, 'height' => 150, 'fit' => 'contain'],
            'medium' => ['width' => 300],
            'medium_large' => ['width' => 768],
            'large' => ['width' => 1024],
        ],
        // Fallback breakpoints when a profile does not set its own widths.
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
        // No AVIF/WebP/mail/customer_api pipelines for these (e.g. animated GIF).
        'skip_mime_types' => [
            'image/gif',
        ],
        'profiles' => [
            'web' => [
                'enabled' => true,
                'mode' => 'responsive',
                'formats' => ['avif', 'webp', 'original'],
                'upscale' => false,
                'min_responsive_width' => 480,
                // 'widths' => [480, 960, 1440], // optional; defaults to extra_widths
                'fallback_to_original' => true,
                'svg' => 'original',
            ],
            'mail' => [
                'enabled' => true,
                'mode' => 'single',
                'formats' => ['jpg', 'png'],
                'upscale' => false,
                'default_width' => 600,
                'fallback_to_original' => true,
                'svg' => 'png',
            ],
            'customer_api' => [
                // Publish config/media.php and enable + set watermark.path in the app.
                'enabled' => false,
                'mode' => 'single',
                'formats' => ['jpg', 'png'],
                'upscale' => false,
                'default_width' => 1024,
                'fallback_to_original' => false,
                'svg' => 'skip',
                'skip_static' => true,
                'watermark' => [
                    'enabled' => false,
                    'path' => null, // app file only (absolute or relative to base_path)
                    'opacity' => 40,
                    'width_percent' => 15,
                    'padding_percent' => 3,
                    'position' => 'bottom_right', // Spatie AlignPosition name
                    'fit' => 'contain',
                ],
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
