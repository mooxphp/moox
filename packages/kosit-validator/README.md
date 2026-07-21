![Moox KositValidator](https://github.com/mooxphp/moox/raw/main/art/banner/record.jpg)

# Moox KositValidator

KoSIT Validator CLI wrapper for ZUGFeRD / XRechnung XML validation against EN 16931. The package runs the official KoSIT standalone JAR with XRechnung configuration, persists audit rows on `kosit_validations`, optionally links validations to domain owners via `kosit_validatables`, and provides a read-only Filament admin UI.

## Features

<!--features-->

- KoSIT standalone JAR and XRechnung configuration install (`kosit:install`, `--force`)
- Health checks (`kosit:doctor`) and CLI validation (`kosit:validate`)
- Programmatic validation via `KositService` and structured `KositResult` (SVRL / KoSIT message parsing)
- `RecordKositValidation` action for audit persistence
- Morph pivot `kosit_validatables` (UUID owners) for optional multi-owner / history linking
- Read-only Filament resource: list tabs, ternary/filters, report iframe, downloads
- `MorphPivotRelationRegistry` integration so owner packages can attach validations from their UI
- Moox-standard layout: config, two stub migrations, `resources/lang` (EN/DE)

<!--/features-->

## Responsibility Boundaries

- `moox/kosit-validator` owns KoSIT installation, XML validation, report output paths, and validation audit persistence.
- `moox/e-billing` is the typical orchestrator: `ValidateArtifactJob` calls `KositService`, records results via `RecordKositValidation`, and links them to the `EbillingDocument` through `kositValidations()->attach()` on the `kosit_validatables` morph pivot — not via a `kosit_validation_id` column on attachments or documents.
- `moox/zugferd` produces XML that this package validates; validation does not live in `moox/zugferd`.
- Owner packages (`EbillingDocument`, etc.) are external; register allowed types under `kosit-validator.relations.kosit_validatables.owner_types` to enable Filament pivot management.

## Requirements

| Requirement | Purpose |
|-------------|---------|
| `moox/core` | Base model, Filament resource, Moox installer, morph pivot registry |
| Java runtime | Executes the KoSIT validator JAR (`KOSIT_JAVA_BINARY`, default `java`) |

See [Requirements](https://github.com/mooxphp/moox/blob/main/docs/Requirements.md).

## Installation

```bash
composer require moox/kosit-validator
php artisan moox:install
```

Install KoSIT artefacts (JAR + XRechnung configuration):

```bash
php artisan kosit:install
```

Verify Java, JAR, scenarios, and report directory writability:

```bash
php artisan kosit:doctor
```

Curious what the install command does? See [Installation](https://github.com/mooxphp/moox/blob/main/docs/Installation.md).

Optionally publish configuration:

```bash
php artisan vendor:publish --tag=kosit-validator-config
```

## Registering with Filament

```php
use Moox\KositValidator\Plugins\KositValidatorPlugin;

$panel->plugins([
    KositValidatorPlugin::make(),
]);
```

`KositValidatorPlugin` registers `KositValidationResource` (slug `kosit-validations`). Create, edit, and delete are disabled on the resource.

## Screenshot

![Moox KositValidator](https://github.com/mooxphp/moox/raw/main/art/screenshots/record.jpg)

## Usage

### Environment variables

| Variable | Config key | Purpose |
|----------|------------|---------|
| `KOSIT_BASE_PATH` | `base_path` | Root for JAR and XRechnung config (default `storage/app/private/kosit`) |
| `KOSIT_VALIDATOR_VERSION` | `validator.version` | JAR version label for install |
| `KOSIT_VALIDATOR_URL` | `validator.download_url` | Standalone JAR download URL |
| `KOSIT_XRECHNUNG_VERSION` | `xrechnung.version` | Config bundle version |
| `KOSIT_XRECHNUNG_RELEASE_DATE` | `xrechnung.release_date` | Config bundle release date |
| `KOSIT_XRECHNUNG_URL` | `xrechnung.download_url` | XRechnung configuration zip URL |
| `KOSIT_JAVA_BINARY` | `java_binary` | Java executable (default `java`) |
| `KOSIT_OUTPUT_PATH` | `output.path` | Report output directory |
| `KOSIT_REPORT_PATH` | `output.path` (legacy) | Fallback when `KOSIT_OUTPUT_PATH` is unset |
| `KOSIT_ALLOW_UNTRUSTED_BASE_PATH` | `installer.allow_untrusted_base_path` | Skip storage-root check (local/testing only) |
| `KOSIT_ALLOW_UNTRUSTED_DOWNLOAD_HOSTS` | `installer.allow_untrusted_download_hosts` | Skip GitHub/itplr-kosit download allowlist (local/testing only) |

`KositOutputPath::resolve(?string $subdirectory)` reads `output.path`, creates the directory with mode `0775`, and optionally appends a subdirectory (e-billing uses a date segment per run).

### Install safety

`kosit:install` downloads only from pinned `itplr-kosit` GitHub release paths; SHA-256 must match config (mismatch aborts install with *no files installed*). ZIP extraction rejects null-byte entry names alongside zip-slip, absolute paths, and symlink entries. The verified XRechnung ZIP is also stored as `{xrechnung_dir}/.xrechnung-bundle.zip` for runtime re-verification. With `--force`, only `{base_path}/validator` and `{base_path}/xrechnung` are replaced — never the entire configured base path. Default `base_path` must live under `storage/app/private`; when that directory already exists, containment is checked via `realpath()` so symlink escapes are rejected. `paths.validator_dir` and `paths.xrechnung_dir` must be single directory names (no `/`, `\`, or `..`); `KositInstallPaths` enforces this at install time and when `KositService` resolves `jarPath()` / `scenariosPath()` at runtime. Do not set `KOSIT_ALLOW_UNTRUSTED_*` in production.

### CLI validation

```bash
php artisan kosit:validate /absolute/path/to/invoice.xml
```

Requires a prior `kosit:install`. Always persists via `RecordKositValidation` and prints the validation ID. Exit code `0` on pass, `1` on failure or missing install/file.

### Programmatic validation

```php
use Moox\KositValidator\Services\KositService;
use Moox\KositValidator\Actions\RecordKositValidation;
use Moox\KositValidator\Support\KositOutputPath;

$reportDir = KositOutputPath::resolve('2026-06-03');
$result = app(KositService::class)->validate('/path/to/invoice.xml', $reportDir);
$validation = app(RecordKositValidation::class)($result);
```

`KositService::validate()` re-verifies both pinned artefacts at the moment of use:

1. **Validator JAR** — read once, SHA-256 checked in memory, written to a private temp file, then passed to `java -jar` (the JVM executes the hashed bytes, not a second read of the install path).
2. **XRechnung configuration** — `.xrechnung-bundle.zip` under `{xrechnung_dir}` is checksum-verified, extracted to a private temp directory, and `-s` / `-r` point at that extract (tampering the on-disk extracted tree is ignored).

Checksum mismatch or a missing bundle aborts validation before Java runs. Installs created before this bundle was stored must be refreshed with `php artisan kosit:install --force`.

**Residual TOCTOU:** Runtime verification confirms the bytes used for execution match the pinned digest at the moment of use. It does not protect against a local attacker with write access to the storage path during the sub-millisecond window between hashing and the OS handing the temp file to the JVM — restrict filesystem permissions on the KoSIT storage path to the application user.

On a typical developer machine, verifying and extracting the pinned test bundle averages well under 5 ms per run in package tests (see `KositServiceValidateIntegrityTest`); production bundles are larger but validation cost remains dominated by JVM startup (hundreds of ms to seconds).

Then runs `{java_binary} -jar … -s scenarios.xml -r repository -o {reportDir} -h {xmlPath}` and returns `KositResult` with expected `{basename}-report.xml` / `.html` paths when files exist.

### E-billing integration

In `moox/e-billing`, `ValidateArtifactJob`:

1. Resolves `KositOutputPath::resolve($dateSegment)`
2. Extracts XML from the hybrid PDF when validating ZUGFeRD (otherwise validates the loose XML file)
3. Calls `KositService::validate($xmlPath, $reportDir)`
4. Persists via `RecordKositValidation`
5. Links the validation to the `EbillingDocument` via `kositValidations()->attach()` on the `kosit_validatables` morph pivot (no `kosit_validation_id` column on inbox attachments or documents)
6. Stores `artifact_content_hash` (SHA-256 of the deliverable) and marks `gateway_status = validated` on success

`RecordKositValidation` does **not** create `kosit_validatables` rows. For morph history, configure `owner_types` and link from the owner model (`morphPivotRelation('kosit_validatables')`) or e-billing's `morph_relations.kosit_validatables`.

## Database schema

Two migrations are registered by `KositValidatorServiceProvider`.

### `kosit_validations`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `input_path` | string(1024) nullable | Validated XML path |
| `passed` | boolean | `true` when KoSIT exit code is `0` |
| `errors` | json nullable | Normalized validation messages |
| `report_xml_path` | string(1024) nullable | KoSIT XML report |
| `report_html_path` | string(1024) nullable | KoSIT HTML report |
| `validated_at` | timestamp | Validation time |
| `created_at`, `updated_at` | timestamps | |
| `scope` | string nullable, indexed | Reserved; not set by package code yet |

Indexes: `passed`, `validated_at`, `scope`.

### `kosit_validatables`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `validatable_type`, `validatable_id` | uuid morph | Owner (UUID primary keys) |
| `kosit_validation_id` | foreignId | Cascade delete with validation |
| `created_at`, `updated_at` | timestamps | |
| `scope` | string nullable, indexed | Reserved |

Unique: `(validatable_type, validatable_id, kosit_validation_id)`.

## The KositValidation Model

Class: `Moox\KositValidator\Models\KositValidation`  
Extends `Moox\Core\Entities\Items\Item\BaseItemModel`.

**Fillable:** `input_path`, `report_xml_path`, `report_html_path`, `passed`, `errors`, `validated_at`  
**Casts:** `errors` → array, `passed` → boolean, `validated_at` → datetime

| Method | Description |
|--------|-------------|
| `getResourceName()` | Returns `kosit-validation` |
| `kositValidatables()` | `HasMany` pivot rows |
| `scopePassed()` / `scopeFailed()` | Filter by `passed` |
| `filenameLabel()` | `basename(input_path)` or translated empty label |
| `reportHtmlPath()` | Returns `report_html_path` |

`RecordKositValidation` stores `errors` as `KositResult::validationMessages()` (structured `{type, text, location, rule}`), not only error-severity strings.

## The KositValidatable Pivot

Class: `Moox\KositValidator\Models\KositValidatable` extends `MorphPivot`.

| Field | Description |
|-------|-------------|
| `validatable_type` / `validatable_id` | Morph owner (UUID) |
| `kosit_validation_id` | References `kosit_validations.id` |
| `scope` | Reserved; not set by package code yet |

`KositRelationConfig` reads `config('kosit-validator.relations.kosit_validatables')` for Filament and integrators.

## Public API

| Kind | FQCN |
|------|------|
| Service | `Moox\KositValidator\Services\KositService` |
| Result DTO | `Moox\KositValidator\DTOs\KositResult` |
| Record action | `Moox\KositValidator\Actions\RecordKositValidation` |
| Output path | `Moox\KositValidator\Support\KositOutputPath` |
| Message helpers | `Moox\KositValidator\Support\KositValidationMessages` |
| Relation config | `Moox\KositValidator\Support\KositRelationConfig` |
| HTTP controller | `Moox\KositValidator\Http\Controllers\KositReportController` |
| Filament plugin | `Moox\KositValidator\Plugins\KositValidatorPlugin` |

### `KositService`

| Method | Description |
|--------|-------------|
| `validate(string $xmlPath, ?string $reportDirectory = null): KositResult` | Re-verifies JAR checksum, runs KoSIT; default report dir from `KositOutputPath::resolve()` |
| `jarPath()` / `scenariosPath()` / `repositoryPath()` | Resolve installed artefacts |
| `isInstalled()` / `javaAvailable()` | Preconditions for CLI and jobs |

### `KositResult`

| Method | Description |
|--------|-------------|
| `passed()` / `failed()` | Exit code `=== 0` |
| `validationMessages()` | Parsed report XML (KoSIT + SVRL) or stderr fallback |
| `errors()` | Flat error texts where `type === 'error'` |

### Artisan commands

| Command | Options | Description |
|---------|---------|-------------|
| `kosit:install` | `--force` | Download and extract JAR + XRechnung config |
| `kosit:validate` | `{path}` required | Validate XML, persist audit row |
| `kosit:doctor` | — | Check Java, JAR, scenarios, writable output path |

## Filament admin

Resource: `Moox\KositValidator\Resources\KositValidationResource`

| Page | Route | Description |
|------|-------|-------------|
| List | `/` | Table with tabs and filters |
| View | `/{record}` | Summary, messages partial, report iframe |

**List columns (order):** result badge, filename, error/warning/info count badges, `validated_at` (default sort desc).

**Tabs:** All, Passed, Failed, With Warnings, With Infos (`__has_message_type` synthetic filter via `KositValidationMessages`).

**Filters:** Ternary `passed`; `validated_at` date range.

**View header actions:** Download input XML, report HTML, report XML (hidden when path missing).

**Relation manager:** `KositValidatablesRelationManager` on the view page. Create is visible only when `owner_types` is non-empty; supports `MorphToSelect` for configured owners.

**Navigation:** `Heroicon::OutlinedShieldCheck`, sort `21`, group from `navigation_group`.

### Web routes

Middleware: `web`, Filament `Authenticate`. Prefix: `admin/kosit-validations`. Name prefix: `kosit-validator.`

| Route name | Path | Action |
|------------|------|--------|
| `kosit-validator.report.html` | `{validation}/report-html` | Inline HTML iframe |
| `kosit-validator.download.input-file` | `{validation}/download/input` | Source XML attachment |
| `kosit-validator.download.report-html` | `{validation}/download/report-html` | HTML report |
| `kosit-validator.download.report-xml` | `{validation}/download/report` | XML report |

## Configuration

File: `config/kosit-validator.php`

| Key | Description |
|-----|-------------|
| `navigation_group` | Filament navigation group |
| `base_path` | KoSIT artefacts root |
| `paths.validator_dir` / `paths.xrechnung_dir` | Single-segment subdirectory names under `base_path` (validated by `KositInstallPaths` at install and runtime) |
| `validator.*` / `xrechnung.*` | Download versions and URLs for `kosit:install` |
| `java_binary` | Java executable |
| `output.path` | KoSIT `-o` report directory |
| `resources.kosit-validation` | Filament `single`, `plural`, `tabs` |
| `relations.kosit_validatables` | Pivot registry: `pivot_model`, `owner_types`, etc. |

## Running tests

From the package directory:

```bash
composer test
composer test:arch
```

Or from the monorepo root:

```bash
php vendor/bin/pest --configuration=packages/kosit-validator/phpunit.xml packages/kosit-validator/tests
```

## Translations

- Entity titles: `resources/lang/{locale}/kosit-validator.php`
- Field labels: `resources/lang/{locale}/fields.php` (DE and EN)

## See also

- [Moox EBilling](../e-billing/README.md) — pipeline orchestrator (`ValidateArtifactJob`)
- [Moox Zugferd](../zugferd/README.md) — XML generation validated here
- [Moox documentation](https://moox.org/docs/kosit-validator)
- [Architecture](docs/ARCHITECTURE.md)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

Thanks to so many [people for their contributions](https://github.com/mooxphp/moox#contributors) to this package.

## License

The MIT License (MIT). Please see [our license and copyright information](https://github.com/mooxphp/moox/blob/main/LICENSE.md) for more information.
