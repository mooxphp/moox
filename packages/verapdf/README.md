![Moox VeraPdf](https://github.com/mooxphp/moox/raw/main/art/banner/record.jpg)

# Moox VeraPdf

veraPDF CLI wrapper for PDF/A-3 validation. The package headless-installs the official greenfield veraPDF distribution, runs the launcher against a PDF, persists audit rows on `verapdf_validations`, and optionally links validations to domain owners via `verapdf_validatables`.

## Features

- veraPDF greenfield install (`verapdf:install`, `--force`) via IzPack headless auto-install
- Health checks (`verapdf:doctor`) and CLI validation (`verapdf:validate`)
- Programmatic validation via `VeraPdfService` and structured `VeraPdfResult` (MRR XML parsing)
- `RecordVeraPdfValidation` action for audit persistence
- Morph pivot `verapdf_validatables` (UUID owners) for optional multi-owner linking
- Moox-standard layout: config, two stub migrations, `resources/lang` (EN/DE)

## Responsibility Boundaries

- `moox/verapdf` owns veraPDF installation, PDF/A-3 validation, report output paths, and validation audit persistence.
- Owner packages are external; register allowed types under `verapdf.relations.verapdf_validatables.owner_types` when wiring morph history.
- This package stays generic — no e-billing or host-app knowledge.

## Requirements

| Requirement | Purpose |
|-------------|---------|
| `moox/core` | Base model, Moox installer, morph pivot registry |
| Java runtime | Headless IzPack install and veraPDF launcher (`VERAPDF_JAVA_BINARY`, default `java`) |

## Installation

```bash
composer require moox/verapdf
php artisan moox:install
```

Install veraPDF artefacts:

```bash
php artisan verapdf:install
```

Verify Java, launcher, and report directory writability:

```bash
php artisan verapdf:doctor
```

Optionally publish configuration and migrations:

```bash
php artisan vendor:publish --tag=verapdf-config --force
php artisan vendor:publish --tag=verapdf-migrations --force
php artisan config:clear
```

## Usage

### Environment variables

| Variable | Config key | Purpose |
|----------|------------|---------|
| `VERAPDF_BASE_PATH` | `base_path` | Root for veraPDF install (default `storage/app/private/verapdf`) |
| `VERAPDF_VERSION` | `installer.version` | Installer version label |
| `VERAPDF_DOWNLOAD_URL` | `installer.download_url` | Greenfield installer zip URL |
| `VERAPDF_JAVA_BINARY` | `java_binary` | Java executable (default `java`) |
| `VERAPDF_FLAVOUR` | `flavour` | PDF/A flavour code (default `3b`) |
| `VERAPDF_OUTPUT_PATH` | `output.path` | Report output directory |

### CLI validation

```bash
php artisan verapdf:validate /absolute/path/to/file.pdf
```

Requires a prior `verapdf:install`. Always persists via `RecordVeraPdfValidation` and prints the validation ID.

### Programmatic validation

```php
use Moox\VeraPdf\Services\VeraPdfService;
use Moox\VeraPdf\Actions\RecordVeraPdfValidation;
use Moox\VeraPdf\Support\VeraPdfOutputPath;

$reportDir = VeraPdfOutputPath::resolve('2026-07-17');
$result = app(VeraPdfService::class)->validate('/path/to/file.pdf', $reportDir);
$validation = app(RecordVeraPdfValidation::class)($result);

if (! app(VeraPdfService::class)->isInstalled()) {
    // degrade gracefully — run verapdf:install
}
```

## Database schema

### `verapdf_validations`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `input_path` | string(1024) nullable | Validated PDF path |
| `passed` | boolean | From report `isCompliant` / exit code |
| `errors` | json nullable | Normalized validation messages |
| `report_xml_path` | string(1024) nullable | |
| `report_html_path` | string(1024) nullable | |
| `validated_at` | timestamp | |
| `created_at`, `updated_at` | timestamps | |
| `scope` | string nullable, indexed | Reserved |

### `verapdf_validatables`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `validatable_type`, `validatable_id` | uuid morph | Owner |
| `verapdf_validation_id` | foreignId | Cascade delete |
| `created_at`, `updated_at` | timestamps | |
| `scope` | string nullable, indexed | Reserved |

## Public API

| Kind | FQCN |
|------|------|
| Service | `Moox\VeraPdf\Services\VeraPdfService` |
| Result DTO | `Moox\VeraPdf\DTOs\VeraPdfResult` |
| Record action | `Moox\VeraPdf\Actions\RecordVeraPdfValidation` |
| Output path | `Moox\VeraPdf\Support\VeraPdfOutputPath` |
| Relation config | `Moox\VeraPdf\Support\VeraPdfRelationConfig` |

### Artisan commands

| Command | Options | Description |
|---------|---------|-------------|
| `verapdf:install` | `--force` | Download and headless-install veraPDF |
| `verapdf:validate` | `{path}` | Validate PDF, persist audit row |
| `verapdf:doctor` | — | Check Java, launcher, writable output path |

## Running tests

From the package directory:

```bash
composer test
```

Or from the monorepo root:

```bash
php vendor/bin/pest --configuration=packages/verapdf/phpunit.xml packages/verapdf/tests/Unit packages/verapdf/tests/Feature
```

Parsing tests use XML fixtures and do not require a live JVM.

## License

The MIT License (MIT). Please see [our license and copyright information](https://github.com/mooxphp/moox/blob/main/LICENSE.md) for more information.
