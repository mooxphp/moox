<div class="filament-hidden">

![Moox BPMN](banner.jpg)

</div>

# Moox BPMN

<!-- Description -->

Moox BPMN consists of a Blade component, a Filament field and a WordPress Plugin to upload, view and edit BPMN 2.0 models made with [BMPN.io](https://bpmn.io/) or [Camunda](https://camunda.com).

<!-- /Description -->

The package is part of the **Moox ecosystem** — a suite of Filament packages that form a solid foundation for Laravel apps, websites, CMS, and eCommerce projects.

Learn more about [Moox](https://moox.org).

## Installation

To install this package, require it via Composer and run the Moox Installer:

```bash
composer require moox/skeleton
php artisan moox:install
```

Learn more about the [Moox Installer or common requirements](https://moox.org/installer).

## Screenshot

![Moox BPMN screenshot](screenshot/main.jpg)

## Features

<!-- Features -->

**Laravel Package: Moox BPMN (moox/bpmn)**

-   Self-contained Laravel package, optionally compatible with the WordPress Plugin.
-   Provides BPMN Viewer and Editor using bpmn-js.
-   No-Code UX using Filament with BPMN editing fields.
-   Blade component to render the BPMN viewer in the frontend.
-   Filament field for uploading & editing .bpmn files directly in the backend.
-   Works standalone in Laravel or integrated with Moox Press Media (configurable).

**WordPress Plugin: Moox BPMN (moox-bpmn)**

-   Self-contained WordPress plugin, optionally compatible with the Laravel Package.
-   Provides BPMN Viewer and Editor using bpmn-js.
-   No-Code UX by registering Gutenberg block moox/bpmn-viewer.
-   MediaPicker for .bpmn files.
-   Inline preview with bpmn-js Modeler (edit mode).
-   Save updates back into the Media Library file.
-   WP Frontend Renders with bpmn-js Viewer (read-only).

<!-- /Features -->

## Usage

<!-- Usage -->

### Output the BPMN viewer

```php
<x-bpmn-viewer media-id="123" mode="edit" />
<x-bpmn-viewer wp-media-id="456" mode="view" />
<x-bpmn-viewer file-path="/path/to/file.bpmn" mode="both" />
```

-   `media_id` works with Moox Media as well as Spatie Media Library
-   `wp_media_id` works with Moox Press, means WordPress Media Library
-   `file_path` works with all other files

The `mode` attribute can be

-   `edit` what means the BPMN file can be viewed and edited
-   `view` what means the BPMN file can be viewed

### Use the Filament field

```php
BpmnViewer::make('bpmn')
    ->label(__('BPMN Process'))
    ->mediaIntegration('moox')
    ->mode('edit')
    ->required(),
```

The `mediaIntegration` can be

-   `moox` works with Moox Media as well as Spatie Media Library
-   `press` works with Moox Press, means WordPress Media Library
-   `file` is the default setting, means it saves it directly to the default disk

and `filePath`can set a specific path only used for the `file`-integration.

The `mode` can be

-   `edit` what means the BPMN file can be edited in Filament
-   `upload` what means the BPMN file can just be uploaded and previewed
-   `full` is the default setting, what means upload or create and edit

<!-- /Usage -->

## Development

npm build ...

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

Thanks to so many [people for their contributions](https://github.com/mooxphp/moox#contributors) to Moox, special thanks to our sponsors.

## Help Moox

Want to help us to develop and grow Moox. Fortunately there are so many ways to do this, learn more about [helping Moox](https://moox.org/help-moox).

## License

The MIT License (MIT). Please see [our license and copyright information](https://github.com/mooxphp/moox/blob/main/LICENSE.md) for more information.
