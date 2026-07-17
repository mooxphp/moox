![Moox EBilling](https://github.com/mooxphp/moox/raw/main/art/banner/record.jpg)

# Moox EBilling

Moox e-billing orchestrates the Moox e-invoice pipeline: PDF ingestion through XML generation, KoSIT validation, and ZUGFeRD PDF merge, with a Filament review UI for operators.

## Features

<!--features-->

- PDF-to-invoice pipeline orchestration (mail-inbox handoff through ZUGFeRD merge)
- EN 16931 / ZUGFeRD XML preparation via `moox/zugferd`
- KoSIT validation integration via `moox/kosit-validator`
- Foreign-invoice filtering (non-domestic invoices moved to an ignored mailbox folder)
- MoSCoW field validation and validation scoring on `EbillingDocument`
- Filament `InvoiceResource` for list, filter, and manual review workflows
- Host-bound invoice parser via `InvoiceParserInterface` (no parser ships with this package)

<!--/features-->

## How it works

Upstream, `moox/mail-inbox` dispatches `ParsePdfJob` on each PDF attachment; early in that job it fires `InboxAttachmentProcessed` so host listeners can parse the PDF and persist invoice data, which hands control to this package.

The pipeline then runs in order:

| Step | Class | What it does |
| --- | --- | --- |
| 1 | `ProcessInboxAttachmentListener` | Creates or finds an `EbillingDocument` for the attachment and dispatches `StoreBillDataJob`. |
| 2 | `StoreBillDataJob` | Reads parsed `bill_data` on the document (populated upstream by the host parser) and dispatches `FilterForeignInvoiceJob`. |
| 3 | `FilterForeignInvoiceJob` | Classifies domestic vs. foreign invoices; foreign invoices are moved to the ignored Graph folder and marked `IgnoredForeign`; domestic invoices advance to XML generation. |
| 4 | `GenerateXmlJob` | Maps `bill_data` to a persisted `Invoice`, generates EN 16931 XML, runs field validation, and dispatches `ValidateXmlJob`. |
| 5 | `ValidateXmlJob` | Runs KoSIT validation on the XML; on pass, dispatches `MergeZugferdPdfJob`. |
| 6 | `MergeZugferdPdfJob` | Embeds validated XML into the source PDF and stores the ZUGFeRD artefact. |

There is no `HandleFailedJob`. Failure handling uses each job's `failed()` method plus `InboxMessagePipelineFinalizer` to update attachment and message status.

The host application must bind `InvoiceParserInterface` to parse PDF text into the `Moox\EBilling\Data\Invoice` DTO before `bill_data` is available on the document. See [Parser integration](#parser-integration).

## Requirements

This package composes the other Moox e-billing packages. Composer requires:

| Package | Role |
| --- | --- |
| `moox/company` | Company FK on `EbillingDocument` |
| `moox/core` | Base model, Filament resource, Moox installer |
| `moox/invoice` | Invoice domain models (`Invoice`, lines, parties) |
| `moox/jobs` | Job progress traits |
| `moox/kosit-validator` | KoSIT XML validation and audit persistence |
| `moox/mail-inbox` | Graph inbox, attachment storage, `ParsePdfJob` |
| `moox/pdf-parser` | PDF text extraction (used by the host parser) |
| `moox/zugferd` | EN 16931 / ZUGFeRD XML generation and PDF merge |

See [Requirements](https://github.com/mooxphp/moox/blob/main/docs/Requirements.md).

## Installation

```bash
composer require moox/e-billing
php artisan moox:install
```

Curious what the install command does? See [Installation](https://github.com/mooxphp/moox/blob/main/docs/Installation.md).

Register the Filament plugin on your panel (see [Filament](#filament)) and bind `InvoiceParserInterface` in your host `ServiceProvider` (see [Parser integration](#parser-integration)).

## Configuration

Published as `config/e-billing.php`.

### Config keys

| Key | Controls |
| --- | --- |
| `resources` | Filament resource registration (`invoices` → `InvoiceResource`) |
| `tabs` | List-page tab filters (`all`, `needs_review`, `confirmed`, `deleted`) |
| `default_format` | FormatRegistry key frozen onto `ebilling_documents.format` at generation (default `zugferd`) |
| `zugferd` | ZUGFeRD filesystem disk (`storage_disk`, `storage_root`); profile lives in `moox/zugferd` (`config('zugferd.profile')`) |
| `foreign_invoice` | Foreign-invoice handling (`ignored_folder_name`) |
| `default_customer_country` | Transitional fallback buyer country when the parser derives none (default `DE`); removed in a future master-data phase |
| `supplier` | Central supplier master data copied onto invoices as a snapshot at creation time |
| `field_validation` | MoSCoW priority rules for invoice and line fields |
| `morph_relations` | Morph pivot config for KoSIT validations (`kosit_validatables`) |

### Environment variables

This package exposes one environment variable. Microsoft Graph credentials and mailbox settings belong to `moox/mail-inbox`.

```env
# Optional — Graph folder display name for ignored foreign invoices (default: Ignored)
EBILLING_IGNORED_FOLDER=Ignored
```

| Variable | Config key | Default | Required |
| --- | --- | --- | --- |
| `EBILLING_IGNORED_FOLDER` | `foreign_invoice.ignored_folder_name` | `Ignored` | No |

### Supplier block

Override `supplier` in your published `config/e-billing.php` with your company details (name, VAT ID, address, bank accounts). Values are snapshotted onto each `Invoice` when `GenerateXmlJob` creates the record.

### `default_customer_country`

When the parser cannot derive a buyer country from the PDF, this ISO code is used as a fallback for domestic classification. It is transitional and will be replaced by Company / Address master-data lookup.

## Parser integration

No invoice parser ships with this package. Implement `Moox\EBilling\Contracts\InvoiceParserInterface` in your host application and bind it in a `ServiceProvider`:

```php
use Moox\EBilling\Contracts\InvoiceParserInterface;
use Moox\EBilling\Data\Invoice;

// YourParser must implement:
// public function parse(string $rawText): Invoice

$this->app->bind(InvoiceParserInterface::class, YourParser::class);
```

The parser receives extracted PDF text (from `moox/pdf-parser`) and returns a `Moox\EBilling\Data\Invoice` DTO. The host is responsible for persisting `bill_data` on the `EbillingDocument` before the pipeline jobs run.

## Commands

Backfill `validation_score` on documents that have `field_validations` but no stored score (for example after a schema or scoring change):

```bash
php artisan ebilling:backfill-scores
```

Queries `EbillingDocument` rows where `field_validations` is not null and `validation_score` is null, computes each score via `calculateValidationScore()`, and saves quietly.

## The EbillingDocument Model

`EbillingDocument` (`Moox\EBilling\Models\EbillingDocument`) is the gateway state record for one inbox attachment. It links the source attachment (morph) to a persisted `Invoice` and tracks pipeline status, validation results, and artefact paths.

### Attributes

| Column | Type | Nullability | Notes |
| --- | --- | --- | --- |
| `id` | `uuid` | NOT NULL | Primary key |
| `source_type` | `string` | nullable | Morph type (typically `InboxAttachment`) |
| `source_id` | `unsignedBigInteger` | nullable | Morph key (`InboxAttachment` uses a bigInteger PK) |
| `bill_data` | `json` | nullable | Parsed invoice DTO as JSON |
| `xml_storage_path` | `string` | nullable | Relative path to generated XML on the storage disk |
| `storage_disk` | `string` | nullable | Filesystem disk name for e-billing artefacts |
| `pdf_storage_path` | `string` | nullable | Relative path to merged hybrid PDF (ZUGFeRD/Factur-X) |
| `format` | `string` | NOT NULL | Frozen format id at generation; default `zugferd` |
| `artifact_content_hash` | `string` | nullable | SHA-256 of validated artifact (populated later) |
| `ignored_reason` | `json` | nullable | Foreign-invoice classification details |
| `gateway_status` | `string` | nullable | Pipeline stage (indexed) |
| `review_status` | `string` | NOT NULL | Review stage; default `parser_created` (indexed) |
| `validation_score` | `unsignedTinyInteger` | nullable | Aggregated field-validation score |
| `field_validations` | `json` | nullable | Per-field validation results |
| `processed_at` | `timestamp` | nullable | Set when ZUGFeRD PDF merge completes |
| `error_message` | `text` | nullable | Last pipeline error |
| `created_at` | `timestamp` | NOT NULL | |
| `updated_at` | `timestamp` | NOT NULL | |
| `company_id` | `uuid` FK | nullable | References `companies.id` (`nullOnDelete`) |
| `invoice_id` | `uuid` FK | nullable | References `invoices.id` (`nullOnDelete`) |
| `scope` | `string` | nullable | Tenant / mailbox scope (indexed) |

### Relationships

- `source()` — `MorphTo` (typically `InboxAttachment`)
- `invoice()` — `BelongsTo` `Moox\Invoice\Models\Invoice`
- `company()` — `BelongsTo` `Moox\Company\Models\Company`
- `kositValidations()` — `MorphToMany` via `kosit_validatables`

## Filament

Register the plugin on your panel:

```php
use Moox\EBilling\Plugins\EBillingPlugin;

$panel->plugins([
    EBillingPlugin::make(),
]);
```

`EBillingPlugin` registers `InvoiceResource` (slug `invoices`), which manages `Moox\Invoice\Models\Invoice`. Create and edit are disabled; operators use the list and view pages to review parsed invoices, validation scores, KoSIT status, and confirm or reject records.

## Relation to moox/invoice

Invoice domain models (`Invoice`, line items, parties, and related tables) live in **`moox/invoice`**, not in this package.

This package owns:

- **`EbillingDocument`** — gateway state, validation scores, and artefact paths
- **The processing pipeline** — listener and jobs from inbox handoff through ZUGFeRD merge
- **The Filament review UI** — read-only `InvoiceResource`
- **`Invoice::ebillingDocument()`** — registered via `resolveRelationUsing` in `EBillingServiceProvider`

`GenerateXmlJob` creates and updates `Invoice` records through `moox/invoice`; this package orchestrates that step but does not define the invoice schema.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

Thanks to so many [people for their contributions](https://github.com/mooxphp/moox#contributors) to this package.

## License

The MIT License (MIT). Please see [our license and copyright information](https://github.com/mooxphp/moox/blob/main/LICENSE.md) for more information.
