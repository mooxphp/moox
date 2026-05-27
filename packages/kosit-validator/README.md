# Moox KositValidator

<!-- Description -->

KoSIT Validator CLI wrapper for ZUGFeRD / XRechnung XML validation against EN 16931, with Filament admin UI and persistence on **`kosit_validations`**.

<!-- /Description -->

The package is part of the **Moox ecosystem** — a suite of Filament packages that form a solid foundation for Laravel apps, websites, CMS, and eCommerce projects.

Learn more about [Moox](https://moox.org).

## Features

<!-- Features -->

- KoSIT standalone JAR + XRechnung configuration install (`kosit:install`)
- CLI and programmatic validation (`kosit:validate`, `KositService`)
- Audit log model and `RecordKositValidation` action
- Read-only Filament resource with tabs, filters, report iframe, and downloads
- Moox-standard package layout (single config, stub migration, `resources/lang` translations)

<!-- /Features -->

## Installation

To install this package, require it via Composer and run the Moox Installer:

```bash
composer require moox/kosit-validator
php artisan moox:install
```

Learn more about the [Moox Installer or common requirements](https://moox.org/docs/getting-started/installation).

## Screenshot

![Moox KositValidator screenshot](screenshot/main.jpg)

## Usage

<!-- Usage -->

### Standalone usage

Install this package:

```bash
composer require moox/kosit-validator
php artisan migrate
```

Set the KoSIT report output directory in `.env` (optional):

```env
KOSIT_OUTPUT_PATH=/absolute/path/to/kosit-reports
```

Or publish and edit `config/kosit-validator.php` → `output.path`.

`KositOutputPath::resolve()` creates the configured directory (and any date subdirectory) automatically with mode `0775` — you do not need to create report folders by hand before running validation.

Validate an invoice from the CLI:

```bash
php artisan kosit:validate /path/to/invoice.xml
```

The command records an audit row and prints the validation ID.

Or programmatically:

```php
use Moox\KositValidator\Services\KositService;
use Moox\KositValidator\Actions\RecordKositValidation;

$result = app(KositService::class)->validate('/path/to/invoice.xml');
$validation = app(RecordKositValidation::class)($result);
// Persists input_path, report_xml_path, report_html_path, passed, errors — no morph subject columns.
```

The validation appears in the Filament resource at `/admin/kosit-validations`.

### Filament admin (`KositValidationResource`)

Read-only list and detail UI for **`kosit_validations`** (register **`KositValidatorPlugin`** on your Filament panel).

**List:** filename (search includes error message text), translated pass/fail badge, error/warning/info count badges, validated-at; tabs **All / Passed / Failed / With Warnings / With Info** (from `config('kosit-validator.resources.kosit-validation.tabs')`); filters for result and date range.

**Detail:** header downloads for source XML and KoSIT report HTML/XML; embedded validation messages and HTML report iframe.

**Navigation:** sidebar group **KoSIT Validator** (`navigation_group` in config).

**Translations:** `resources/lang/{en,de}/kosit-validator.php` (entity titles) and `fields.php` (UI strings). Tab labels in config use `trans//` prefixes.

**Routes** (authenticated, prefix `admin/kosit-validations`): `kosit-validator.report.html` (iframe), `kosit-validator.download.input-file`, `kosit-validator.download.report-html`, `kosit-validator.download.report-xml`.

<!-- /Usage -->

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
