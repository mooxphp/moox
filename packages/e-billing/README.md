![Moox EBilling](https://github.com/mooxphp/moox/raw/main/art/banner/record.jpg)

# Moox EBilling

Moox e-billing orchestrates the Moox e-invoice pipeline: PDF ingestion through artifact generation, KoSIT validation of the produced deliverable, with a Filament review UI for operators.

## Features

<!--features-->

- PDF-to-invoice pipeline orchestration (mail-inbox handoff through validated artifact)
- EN 16931 / ZUGFeRD artifact generation via `moox/zugferd` (hybrid PDF built before validation)
- KoSIT validation integration via `moox/kosit-validator` (XML from loose file or embedded in hybrid PDF)
- PDF/A-3 validation for hybrid formats via `moox/verapdf` when installed (skipped gracefully when not configured)
- Foreign-invoice filtering (non-domestic invoices settled as `Ignored` on the inbox driver and marked `IgnoredForeign`)
- MoSCoW severity gating: must/should/could priorities, severity release with auditable actor identity, and review queue aligned with the findings gate
- Filament `InvoiceResource` for list, filter, and manual review workflows
- Manual customer attribution via `SetInvoiceAttributionAction` (Filament header action kept as `InvoiceResource::getSetAttributionAction()` for a later surface) and explicit rematch from the invoice detail and list
- Host-bound invoice parser via `InvoiceParserInterface` (no parser ships with this package)
- Delivery-date carriage into generated artifacts: one unique date → document actual delivery (BT-72); several differing dates → per-line dates only (no document BT-72, no invoicing-period merge); intra-community invoices with multiple dates surface `delivery_date` as `needs_review` (BR-IC-11) instead of aggregating
- Consignee party on invoice and line `delivery` (name + address): persisted even without a country; detail views show the name first (`PartyAddressFormatter`); field label Consignee / Warenempfänger (hint BG-13); generated artifacts emit ShipTo (BG-13) from `shipToName` / `shipToAddress` without tax registration or contact — address group (BG-15) only when a country is present

<!--/features-->

## How it works

Upstream, `moox/mail-inbox` dispatches `ParsePdfJob` on each PDF attachment; early in that job it fires `InboxAttachmentProcessed` so host listeners can parse the PDF and persist invoice data, which hands control to this package.

The pipeline then runs in order:

| Step | Class | What it does |
| --- | --- | --- |
| 1 | `ProcessInboxAttachmentListener` | Creates or finds an `EbillingDocument` for the attachment and dispatches `StoreBillDataJob`. |
| 2 | `StoreBillDataJob` | Reads parsed `bill_data` on the document (populated upstream by the host parser) and dispatches `FilterForeignInvoiceJob`. |
| 3 | `FilterForeignInvoiceJob` | Classifies domestic vs. foreign invoices; foreign invoices are settled as `Ignored` on the inbox driver and marked `IgnoredForeign`; domestic invoices advance to artifact generation. |
| 4 | `GenerateArtifactJob` | Maps `bill_data` to a persisted `Invoice`, generates the format-specific artifact (XML only or hybrid PDF with embedded XML), runs field validation, and dispatches `ValidateArtifactJob`. |
| 5 | `ValidateArtifactJob` | Runs KoSIT validation on the XML that will be delivered (loose XML or XML extracted from the hybrid PDF). For hybrid formats, also runs veraPDF PDF/A-3 validation. A hybrid passes only when both succeed; if veraPDF is missing the document is retained and flagged, never `Validated`. On pass, stores a SHA-256 hash of the deliverable. |

There is no `HandleFailedJob`. Failure handling uses each job's `failed()` method plus `InboxMessagePipelineFinalizer` to update attachment and message status.

The host application must bind `InvoiceParserInterface` to parse PDF text into the `Moox\EBilling\Data\Invoice` DTO before `bill_data` is available on the document. See [Parser integration](#parser-integration).

## Requirements

**PHP ≥ 8.4** — Zugferd adapters implement `moox/zugferd` contracts that use PHP 8.4 property hooks; the package declares `"php": "^8.4"` so Composer rejects 8.2/8.3 runtimes that would fatal-parse those files.

This package composes the other Moox e-billing packages. Composer requires:

| Package | Role |
| --- | --- |
| `moox/address` | Address fingerprints / company billing addresses for attribution corroboration |
| `moox/company` | Company FK on `EbillingDocument` (reporting-only; derived from customer) |
| `moox/core` | Base model, Filament resource, Moox installer |
| `moox/customer` | Customer FK on `EbillingDocument` (document identity / visibility gate) |
| `moox/invoice` | Invoice domain models (`Invoice`, lines, parties) |
| `moox/jobs` | Job progress traits |
| `moox/kosit-validator` | KoSIT XML validation and audit persistence |
| `moox/verapdf` | PDF/A-3 validation for hybrid artifacts (optional; KOSIT-only degraded mode when not installed) |
| `moox/mail-inbox` | Inbox driver contract, attachment storage, `ParsePdfJob` |
| `moox/pdf-parser` | PDF text extraction (used by the host parser) |
| `moox/zugferd` | EN 16931 / ZUGFeRD XML generation and PDF merge |

This package does not require the Microsoft Graph SDK. When mailboxes use the `msgraph` driver, the host application must also require `moox/msgraph` (folder names and Graph credentials live there).

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
| `default_format` | FormatRegistry key frozen onto `ebilling_documents.format` at generation (default `zugferd`). Allowed: `xrechnung`, `zugferd`, `factur-x` |
| `zugferd` | ZUGFeRD filesystem disk (`storage_disk`, `storage_root`); profile lives in `moox/zugferd` (`config('zugferd.profile')`) |
| `default_customer_country` | Transitional fallback buyer country when the parser derives none (default `DE`); removed in a future master-data phase |
| `supplier` | Central supplier master data copied onto invoices as a snapshot at creation time |
| `corroboration` | Post-attribution master-data checks (never clears `customer_id`): `name_min_token_length`, `name_legal_form_stop_words`, `buyer_address_roles` (billing + postal), `delivery_address_roles` (delivery first, then postal/billing fallback) |
| `field_validation` | MoSCoW priority rules for invoice and line fields |
| `approval` | Dispatch approval gate: `required`, `auto_approve_enabled` |
| `morph_relations` | Morph pivot config for KoSIT and veraPDF validations (`kosit_validatables`, `verapdf_validatables`) |

### Environment variables

Mailbox credentials, driver registration, and folder names belong to `moox/mail-inbox` and your inbox driver package (for example `moox/msgraph` — foreign invoices settle as `SettlementOutcome::Ignored`, folder `msgraph.mail.folders.ignored` / `MSGRAPH_MAIL_IGNORED_FOLDER`).

```env
# Optional — preferred UN/ECE piece unit code for line unit normalization (default: H87)
EBILLING_PREFERRED_PIECE_UNIT_CODE=H87
```

| Variable | Config key | Default | Required |
| --- | --- | --- | --- |
| `EBILLING_PREFERRED_PIECE_UNIT_CODE` | `preferred_piece_unit_code` | `H87` | No |
| `EBILLING_APPROVAL_REQUIRED` | `approval.required` | `true` | No |
| `EBILLING_APPROVAL_AUTO_APPROVE` | `approval.auto_approve_enabled` | `true` | No |

### Supplier block

Override `supplier` in your published `config/e-billing.php` with your company details (name, VAT ID, address, bank accounts). Values are snapshotted onto each `Invoice` when `GenerateArtifactJob` creates the record.

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

### Severity gating (MoSCoW)

Per-field priority under `field_validation` drives three distinct behaviours:

| Priority | Missing field | Wrong content (`needs_review`) |
| --- | --- | --- |
| **must** | Blocks review; cannot be severity-released | Blocks review |
| **should** | Blocks review until `ReleaseSeverityFieldAction` records actor id, timestamp, and reason | Blocks review; not releasable |
| **could** | Recorded as `not_applicable` by the validator when absent; does not block | Does not block |

Severity release applies to **absent** fields only (`status: missing`). It is not a correction path for divergent content.

`ReleaseSeverityFieldAction` writes `released_at`, `released_by_id` (acting identity), `released_by` (display copy), and `reason` to the document's `severity_releases` JSON (invoice-level keys, or `lines.{lineId}.{field}` for line fields). Releases without a reason, without an authenticated actor, or with an entry that fails gate validation are refused. The column is cast but **not** in `$fillable` — only the action writes it; bulk `update([...])` silently drops releases.

`ConfirmInvoiceAction` returns `false` while `needsHumanReview()` is true.

Two related checks answer different questions:

| Check | Question | `review_status` filter |
| --- | --- | --- |
| `needsHumanReview()` | Does this document have unresolved findings? | None — used for gating |
| `scopeNeedsHumanReview()` | Is this document waiting in the review queue? | `parser_created` or `db_validated` only |

Within awaiting-review statuses, both use the same field predicate (including valid severity releases on **should** fields). When several severities apply, the most severe finding wins.

Changing a field's configured priority changes its behaviour with no code change.

### Dispatch approval gate

Distinct from `review_status` (field-review clearance) and `gateway_status` (KOSIT/veraPDF validation), `approval_status` controls whether a validated document may enter the dispatch path.

| State | Meaning |
| --- | --- |
| `pending` | Awaiting human or automatic approval |
| `approved` | Cleared for dispatch |
| `rejected` | Rejected with a recorded reason |

Transitions are append-only in `approval_transitions` (actor, timestamp, reason; severity-release reasons are forwarded on approve). Only `RecordApprovalTransitionAction` writes approval state.

**Automatic approval** runs after gateway validation when every condition holds separately: gateway validated, no unresolved review findings, no blocking must-field, no duplicate flag (`approval_flags.duplicate`), no anomaly flag (`approval_flags.anomalies`). Failing any one leaves the document pending.

| Config key | Default | Effect |
| --- | --- | --- |
| `approval.required` | `true` | When `false`, `DispatchDocumentAction` skips approval and allows dispatch on validation alone |
| `approval.auto_approve_enabled` | `true` | When `false`, clean documents stay pending until a reviewer approves |

`DispatchDocumentAction` refuses unapproved documents at the dispatch seam (not only in the Filament UI). A blocked must-field cannot reach `approved` by any route.

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
| `artifact_content_hash` | `string` | nullable | SHA-256 of validated deliverable (set on KOSIT pass) |
| `ignored_reason` | `json` | nullable | Foreign-invoice classification details |
| `gateway_status` | `string` | nullable | Format-agnostic pipeline stage: `generating`, `generation_failed`, `validating`, `validated`, `validation_failed`, `validator_error`, `ignored_foreign` (indexed) |
| `review_status` | `string` | NOT NULL | Review stage; default `parser_created` (indexed) |
| `validation_score` | `unsignedTinyInteger` | nullable | Aggregated field-validation score |
| `field_validations` | `json` | nullable | Per-field validation results |
| `severity_releases` | `json` | nullable | Severity releases for missing **should** fields (`released_at`, `released_by_id`, `released_by`, `reason`); written only via `ReleaseSeverityFieldAction`; not in `$fillable` |
| `processed_at` | `timestamp` | nullable | Set when validation passes |
| `error_message` | `text` | nullable | Last pipeline error |
| `created_at` | `timestamp` | NOT NULL | |
| `updated_at` | `timestamp` | NOT NULL | |
| `company_id` | `uuid` FK | nullable | References `companies.id` (`nullOnDelete`). Reporting only — derived from the matched customer; never an access boundary |
| `customer_id` | `uuid` FK | nullable | References `customers.id` (`nullOnDelete`). Document identity (matched customer); gate visibility on this |
| `attribution_source` | `string` | nullable | `auto` (matcher) or `manual` (operator). Indexed. Manual attributions survive rematch |
| `invoice_id` | `uuid` FK | nullable | References `invoices.id` (`nullOnDelete`) |
| `scope` | `string` | nullable | Tenant / mailbox scope (indexed) |

### Relationships

- `source()` — `MorphTo` (typically `InboxAttachment`)
- `invoice()` — `BelongsTo` `Moox\Invoice\Models\Invoice`
- `customer()` — `BelongsTo` `Moox\Customer\Models\Customer`
- `company()` — `BelongsTo` `Moox\Company\Models\Company` (reporting only)
- `kositValidations()` — `MorphToMany` via `kosit_validatables`
- `veraPdfValidations()` — `MorphToMany` via `verapdf_validatables` (hybrid formats when veraPDF is configured)

## Filament

Register the plugin on your panel:

```php
use Moox\EBilling\Plugins\EBillingPlugin;

$panel->plugins([
    EBillingPlugin::make(),
]);
```

`EBillingPlugin` registers `InvoiceResource` (slug `invoices`), which manages `Moox\Invoice\Models\Invoice`. Create and edit are disabled; operators use the list and view pages to review parsed invoices, validation scores, KoSIT status, and confirm or reject records. The list search matches invoice number, supplier name, and recipient name (JSON `seller` / `buyer` party columns).

## Relation to moox/invoice

Invoice domain models (`Invoice`, line items, parties, and related tables) live in **`moox/invoice`**, not in this package.

This package owns:

- **`EbillingDocument`** — gateway state, validation scores, and artefact paths
- **The processing pipeline** — listener and jobs from inbox handoff through ZUGFeRD merge
- **The Filament review UI** — read-only `InvoiceResource`
- **`Invoice::ebillingDocument()`** — registered via `resolveRelationUsing` in `EBillingServiceProvider`

`GenerateArtifactJob` creates and updates `Invoice` records through `moox/invoice`; this package orchestrates that step but does not define the invoice schema.

## Delivery dates in the artifact

Persisted `delivery_date` on the invoice and on line items (from the parser through `GenerateArtifactJob`) is mapped by `ZugferdInvoiceAdapter` via `DeliveryDateTransmission` before `moox/zugferd` builds the XML.

The invoice `delivery` party (name + address) is mapped onto `shipToName` / `shipToAddress`. `moox/zugferd` emits ShipTo (BG-13) when a name or an address with a country is present. A consignee without a country is still stored and shown; the converter then emits the name only (no postal address group). Ship-to tax registration and contact are never written.

| Situation | What reaches the artifact |
|-----------|---------------------------|
| One unique date across the invoice header and all lines | Document actual delivery only (BT-72) |
| Several differing dates | Per-line dates only; no document BT-72 |
| Any case | No header invoicing period (BG-14) is synthesized from delivery dates |

When several dates differ, each line with a `delivery_date` is emitted on that line. EN 16931 / XRechnung (and other non-EXTENDED profiles) carry the date as a line billing period with start and end equal to that day; EXTENDED uses the line-level actual delivery date. The active ZUGFeRD profile selects which line carrier `ZugferdConverter` uses.

`InvoiceFieldValidator` flags `delivery_date` as `needs_review` when the invoice is an intra-community supply (seller and buyer EU VAT country prefixes differ) and several differing dates would require aggregating them into a single actual delivery date for BR-IC-11. Operators see a review hint in the Filament UI; the adapter does not merge dates silently.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

Thanks to so many [people for their contributions](https://github.com/mooxphp/moox#contributors) to this package.

## License

The MIT License (MIT). Please see [our license and copyright information](https://github.com/mooxphp/moox/blob/main/LICENSE.md) for more information.
