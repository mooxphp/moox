![Moox PdfParser](https://github.com/mooxphp/moox/raw/main/art/banner/record.jpg)

# Moox PdfParser

Thin Laravel wrapper around `pdftotext` (via `spatie/pdf-to-text`) that extracts plain or layout-preserving text from PDF files and returns a `ParsedDocument` value object. The package is intentionally minimal: no database, Filament UI, routes, or translations — downstream packages own semantic invoice parsing.

## Features

<!--features-->

- `PdfParser::parse()` — plain text extraction
- `PdfParser::parseWithLayout()` — column/layout-preserving extraction (`pdftotext -layout`)
- Immutable `ParsedDocument` DTO (`filePath`, `text`, `parser`, `layout`)
- Configurable `pdftotext` binary path (`PDFTOTEXT_PATH` / `config/pdf-parser.php`)
- Container singleton via `PdfParserServiceProvider`

<!--/features-->

## Responsibility Boundaries

- `moox/pdf-parser` owns PDF-to-text extraction only.
- `moox/e-billing` depends on this package for PDF text extraction; host applications bind `Moox\EBilling\Contracts\InvoiceParserInterface` implementations that typically call `parseWithLayout()` before semantic invoice parsing.
- This package does not implement semantic parsing, persistence, XML generation, or validation.

## Requirements

| Requirement | Purpose |
|-------------|---------|
| `moox/core` | Moox service provider and installer |
| `spatie/pdf-to-text` ^1.5 | PHP binding to `pdftotext` |
| `pdftotext` binary | Must exist at configured path or be discoverable by Spatie when path is empty |

See [Requirements](https://github.com/mooxphp/moox/blob/main/docs/Requirements.md).

## Installation

```bash
composer require moox/pdf-parser
php artisan moox:install
```

Curious what the install command does? See [Installation](https://github.com/mooxphp/moox/blob/main/docs/Installation.md).

Place or copy a `pdftotext` binary, or set an explicit path:

```env
PDFTOTEXT_PATH=/usr/local/bin/pdftotext
```

Default when unset: `storage/app/private/pdf-parser/pdftotext` (see `config/pdf-parser.php`).

Optionally publish configuration:

```bash
php artisan vendor:publish --tag=pdf-parser-config
```

## Screenshot

![Moox PdfParser](https://github.com/mooxphp/moox/raw/main/art/screenshots/record.jpg)

## Usage

Resolve `Moox\PdfParser\PdfParser` from the container:

```php
use Moox\PdfParser\PdfParser;

$document = app(PdfParser::class)->parse('/path/to/file.pdf');

$document->text;      // extracted string
$document->isEmpty(); // trim-aware empty check
$document->lines();   // explode by newline
```

For column-faithful extraction (used by layout-sensitive invoice parsers such as a supplier-specific implementation):

```php
$document = app(PdfParser::class)->parseWithLayout('/path/to/file.pdf');

$document->layout; // true
```

Missing files throw `InvalidArgumentException` (`PDF file not found: {path}`).

### E-billing integration

`moox/e-billing` lists `moox/pdf-parser` as a dependency and orchestrates the invoice pipeline after mail-inbox ingestion. PDF-to-text extraction is not wired inside e-billing itself: the host application binds `InvoiceParserInterface` and typically resolves `PdfParser` from the container, calls `parseWithLayout()` on the attachment path, then passes `$document->text` to `InvoiceParserInterface::parse()`. No Filament or queue code lives in this package.

## The ParsedDocument DTO

Class: `Moox\PdfParser\Data\ParsedDocument`

| Property | Type | Description |
|----------|------|-------------|
| `filePath` | `string` | Source PDF path |
| `text` | `string` | Extracted content |
| `parser` | `string` | Always `spatie/pdf-to-text` from `PdfParser` |
| `layout` | `bool` | `true` when extracted via `parseWithLayout()` (default `false`) |

| Method | Description |
|--------|-------------|
| `isEmpty()` | `trim($this->text) === ''` |
| `lines()` | `explode("\n", $this->text)` |

## The PdfParser Service

Class: `Moox\PdfParser\PdfParser`

Constructor: `(?string $pdftotextPath = null)`. The service provider binds the singleton with `config('pdf-parser.pdftotext_path')`.

| Method | Description |
|--------|-------------|
| `parse(string $pdfPath): ParsedDocument` | Plain text via Spatie `Pdf::text()` |
| `parseWithLayout(string $pdfPath): ParsedDocument` | Same with `setOptions(['-layout'])` |

When `$pdftotextPath` is truthy, Spatie uses that binary; when falsy, Spatie auto-discovers `pdftotext` on common OS paths.

## Configuration

File: `config/pdf-parser.php`

| Key | Env | Default |
|-----|-----|---------|
| `pdftotext_path` | `PDFTOTEXT_PATH` | `storage_path('app/private/pdf-parser/pdftotext')` |

No other config keys. No migrations, routes, views, or Artisan commands.

## Service provider

`Moox\PdfParser\PdfParserServiceProvider` extends `Moox\Core\MooxServiceProvider`:

- Publishes `pdf-parser` config only
- Registers `PdfParser` singleton in `packageRegistered()`
- Moox metadata: category `billing`, stability `dev`, `released(false)`

## Running tests

This package currently ships architecture tests only (`tests/ArchTest.php`). From the monorepo root:

```bash
php vendor/bin/pest packages/pdf-parser/tests
```

There is no `composer test` script in this package’s `composer.json`.

## See also

- [Moox EBilling](../e-billing/README.md) — primary consumer (pipeline orchestrator; host-app `InvoiceParserInterface`)
- [Moox documentation](https://moox.org/docs/pdf-parser)
- [Architecture](docs/ARCHITECTURE.md)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

Thanks to so many [people for their contributions](https://github.com/mooxphp/moox#contributors) to this package.

## License

The MIT License (MIT). Please see [our license and copyright information](https://github.com/mooxphp/moox/blob/main/LICENSE.md) for more information.
