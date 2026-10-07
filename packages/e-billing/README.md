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
- Duplicate document-number detection (`invoice_number` + same `document_type`, optionally scoped by seller VAT via `duplicate_number.scope`): differing source PDFs flag `needs_review` and block auto-Validated / auto-approve; identical source PDFs are discarded without a new version
- Filament `InvoiceResource` for list, filter, and manual review workflows
- Review workspace on the invoice view: per-row value corrections and customer attribution in one edit mode, with leave-edit as the single trigger for re-match, regeneration and re-validation (see [Review workspace](#review-workspace); ADR `docs/adr/0004-mixed-review-workspace-and-leave-edit-pipeline.md`, [#47](https://github.com/mooxphp/e-billing/issues/47), [#48](https://github.com/mooxphp/e-billing/issues/48))
- Host-bound invoice parser via `InvoiceParserInterface` (no parser ships with this package)
- Delivery-date carriage into generated artifacts: one unique date → document actual delivery (BT-72); several differing dates → per-line dates only (no document BT-72, no invoicing-period merge); intra-community invoices with multiple dates surface `delivery_date` as `needs_review` (BR-IC-11) instead of aggregating
- Consignee party on invoice and line `delivery` (name + address): persisted even without a country; detail views show the name first (`PartyAddressFormatter`); field label Consignee (hint BG-13); adapters expose `shipTo*` / trade refs / `itemAttributes` / `itemClassifications`; `moox/zugferd` omits BG-15 when ship-to equals buyer (keep BT-72; VAT **K** still emits), may promote a shared line ship-to, else BT-127 — no tax registration or contact; BG-15 only when a country is present and the party is emitted
- `LineItemAttributeMapper`: material, net/gross weight as kg text, unpriced certificate → BG-32 (BT-160 names from `e-billing::emission` + `document_locale`, package default `en`); customs tariff → BT-158 `HS`; certificate charges default UNCL 7161 `CAE`
- Customer-readable XML text (BT-22 and BT-127 note labels, BG-32 names, reason texts) follows `e-billing.document_locale`, not the app locale; BT-127 labels come from `DocumentNoteLabels`, bound to `Moox\Zugferd\Contracts\ZugferdNoteLabels`
- `InvoiceDocumentNotes` includes `order_date` as BT-22 free text (no OrderReference IssueDate / UBL-CR-018)

<!--/features-->

## How it works

Upstream, `moox/mail-inbox` dispatches `ParsePdfJob` on each PDF attachment; early in that job it fires `InboxAttachmentProcessed` so host listeners can parse the PDF and persist invoice data, which hands control to this package.

The pipeline then runs in order:

| Step | Class | What it does |
| --- | --- | --- |
| 1 | `ProcessInboxAttachmentListener` | Creates or finds an `EbillingDocument` for the attachment and dispatches `StoreBillDataJob`. Honours optional `e-billing.intake.scopes` allowlist (non-listed Scopes → attachment Skipped). |
| 2 | `StoreBillDataJob` | Reads parsed `bill_data` on the document (populated upstream by the host parser) and dispatches `FilterForeignInvoiceJob`. |
| 3 | `FilterForeignInvoiceJob` | Classifies domestic vs. foreign invoices. Foreign disposition (`e-billing.foreign.disposition`): `ignore` (default) settles as `Ignored` / `IgnoredForeign`; `forward` runs Source-PDF relay to inbox To then settles the same way (ADR 0006). Domestic invoices advance to artifact generation. |
| 4 | `GenerateArtifactJob` | Maps `bill_data` to a persisted `Invoice` once (only when the document has none yet; later runs use the existing Invoice rows), generates the format-specific artifact (XML only or hybrid PDF with embedded XML), runs field validation, and dispatches `ValidateArtifactJob`. |
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
| `tabs` | List-page tab filters (`all`, `needs_review`, `confirmed`, `awaiting_approval`, `sent`, `deleted`); the pseudo fields `approval_status` and `delivered` filter on the e-billing document |
| `default` | Single default when the preference port returns null: `format` (FormatRegistry key, default `zugferd`) + `profile` (hybrid library profile, default `EN16931`, must be in `allowed_profiles`). XRechnung ignores `profile` and always uses `XRECHNUNG` |
| `allowed_profiles` | Hybrid profiles a recipient preference may request for `zugferd` / `factur-x` (default `['EN16931']`). XRechnung never takes a preference profile |
| `zugferd` | ZUGFeRD filesystem disk (`storage_disk`, `storage_root`). Hybrid profiles come from `default.profile`, not `moox/zugferd` config |
| `default_customer_country` | Transitional fallback buyer country when the parser derives none (default `DE`); removed in a future master-data phase |
| `supplier` | Central supplier master data copied onto invoices as a snapshot at creation time |
| `corroboration` | Post-attribution master-data checks (never clears `customer_id`): `name_min_token_length`, `name_legal_form_stop_words`, `buyer_address_roles` (billing + postal), `delivery_address_roles` (delivery first, then postal/billing fallback) |
| `cross_checked` | Fields checked against master data, shared by the review workspace pickers and the divergence report: `strict` (`EBILLING_CROSS_CHECKED_STRICT`, default `false`) and `fields` (see [Cross-checked fields](#cross-checked-fields)) |
| `field_validation` | MoSCoW priority rules for invoice and line fields. `credit_note_*` siblings (fields, line fields, contextual-should lists) apply to BT-3 `381` documents; a host that omits a `credit_note_*` key falls back to the `invoice_*` key (ADR 0009) |
| `field_validation.invoice_fields_satisfied_by_lines` | Header fields (default `[]`) that count as present when every invoice line carries its own value, e.g. `order_number` / `order_date` on a collective invoice. Profile-scoped like the other keys: `credit_note_fields_satisfied_by_lines`, `corrected_invoice_fields_satisfied_by_lines` (a profile falls back to the invoice list unless it defines its own, which may be empty). See [Header fields satisfied by lines](#header-fields-satisfied-by-lines) |
| `approval` | Dispatch approval gate: `required`, `auto_approve_enabled` |
| `notification` | Review announce strategy: `immediate` / `batched`, batch key, window minutes, optional `recorder` class |
| `escalation` | Overdue-approval scan: `day_counting`, `working_weekdays`, `exclude_dates`, ordered `levels` (`key` / `after` / `unit`); empty `levels` disables the feature |
| `identical_duplicate` | Notification recipients when an identical source PDF is discarded (`notify_emails`, `panel_id`) |
| `duplicate_number.scope` | Document-number collision scope: `global` (default) or `issuer` (also seller VAT id / BT-31) |
| `delivery` | Dispatch: `enabled`, `mailer` (optional mailer name), `recipients` (`mail_source` / `manual_upload` strategies), `channels` (FQCN list) |
| `morph_relations` | Morph pivot config for KoSIT and veraPDF validations (`kosit_validatables`, `verapdf_validatables`) |

### Environment variables

Mailbox credentials, driver registration, and folder names belong to `moox/mail-inbox` and your inbox driver package (for example `moox/msgraph` — foreign invoices settle as `SettlementOutcome::Ignored`, folder `msgraph.mail.folders.ignored` / `MSGRAPH_MAIL_IGNORED_FOLDER`).

```env
# Optional — preferred UN/ECE piece unit code for line unit normalization (default: H87)
EBILLING_PREFERRED_PIECE_UNIT_CODE=H87

# Optional — language of customer-readable XML text: BG-32 BT-160 names, CAE reason_text fallbacks,
# BT-22 / BT-127 note labels (package default: en; not the app/UI locale)
EBILLING_DOCUMENT_LOCALE=en
```

| Variable | Config key | Default | Required |
| --- | --- | --- | --- |
| `EBILLING_PREFERRED_PIECE_UNIT_CODE` | `preferred_piece_unit_code` | `H87` | No |
| `EBILLING_DOCUMENT_LOCALE` | `document_locale` | `en` | No |
| `EBILLING_APPROVAL_REQUIRED` | `approval.required` | `true` | No |
| `EBILLING_APPROVAL_AUTO_APPROVE` | `approval.auto_approve_enabled` | `true` | No |
| `EBILLING_REVIEW_NOTIFICATION_STRATEGY` | `notification.strategy` | `immediate` | No |
| `EBILLING_REVIEW_NOTIFICATION_BATCH_KEY` | `notification.batch_key` | `window` | No |
| `EBILLING_REVIEW_NOTIFICATION_BATCH_WINDOW` | `notification.batch_window_minutes` | `60` | No |
| `EBILLING_ESCALATION_DAY_COUNTING` | `escalation.day_counting` | `working` | No |
| `EBILLING_DUPLICATE_NUMBER_SCOPE` | `duplicate_number.scope` | `global` | No |
| `EBILLING_DELIVERY_ENABLED` | `delivery.enabled` | `false` | No |
| `EBILLING_DELIVERY_MAILER` | `delivery.mailer` | `null` | No |
| `EBILLING_FOREIGN_DISPOSITION` | `foreign.disposition` | `ignore` | No |
| `EBILLING_DELIVERY_RECIPIENTS_MAIL_SOURCE` | `delivery.recipients.mail_source` | `inbox_to` | No |
| `EBILLING_DELIVERY_RECIPIENTS_MANUAL_UPLOAD` | `delivery.recipients.manual_upload` | `none` | No |

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

## Recipient format preference

Before generation, `EBillingFormatResolver` asks a host-bound port for the recipient's preference, then returns an `EffectiveFormat` (`format` + `profile`) that `GenerateArtifactJob` uses for `generateXml`.

```php
use Moox\EBilling\Contracts\RecipientFormatPreferenceResolverInterface;

// Optional — package auto-binds CustomerFormatPreferenceResolver when unbound
$this->app->bind(RecipientFormatPreferenceResolverInterface::class, YourResolver::class);
```

- **Default binding:** `CustomerFormatPreferenceResolver` reads `Customer.preferred_ebilling_format` (`customer_id` with trashed, else `CustomerMatcher` on the invoice customer number). Always `profile: null`. Empty/missing → `null`.
- **Null preference:** `config('e-billing.default')` (`format` + `profile`) via the registry bake-in.
- **Unknown format or disallowed profile:** throws (`UnknownFormatException` / `InvalidFormatPreferenceException`) — no silent fallback.
- **Why one `FormatPreference`:** the ZUGFeRD library derives attachment filename, guideline id, and XMP name from the profile; separate knobs would fight the library. Preference is `{ format, profile? }` only — profile applies to hybrids and must be in `allowed_profiles` when set; `xrechnung` must keep `profile` null.
- **Freeze:** once `xml_storage_path` is set, retries use frozen `document.format` and frozen `document.profile` (port not re-consulted; both columns required — empty format or profile throws). Preference changes apply to future documents only.

See ADR `docs/adr/0003-recipient-format-preference-four-layer-model.md`.

## Commands

Backfill `validation_score` on documents that have `field_validations` but no stored score (for example after a schema or scoring change):

```bash
php artisan ebilling:backfill-scores
```

Queries `EbillingDocument` rows where `field_validations` is not null and `validation_score` is null, computes each score via `calculateValidationScore()`, and saves quietly.

Scan pending documents past configured escalation thresholds (dispatches `ScanOverdueApprovalEscalationJob`):

```bash
php artisan e-billing:scan-overdue-approval-escalation
```

Schedule this in the host app — the package does not register a schedule. See [Approval escalation scan](#approval-escalation-scan).

### Re-validate documents

After a validation or emission change (field rules, labels, charge handling), re-run field validation and regenerate the artifact from the **stored** invoice. Review corrections are kept:

```bash
php artisan e-billing:revalidate {ids*} {--in-review} {--hold} {--dry-run}
```

| Argument / option | Effect |
| --- | --- |
| `ids` | One or more e-billing document ids |
| `--in-review` | Selects every document in the needs-human-review scope |
| `--hold` | Holds the revalidated documents for approval (see [Hold for approval](#hold-for-approval)) |
| `--dry-run` | Lists the selection and whether each document would be revalidated or refused, without changing anything |

Each document takes the same path as leaving the review workspace: `review_status` is reset to `parser_created`, field validation runs again, `gateway_status` becomes `generating` and `GenerateArtifactJob` is queued. Each revalidation is logged as activity `document_revalidated` on the document (previous `review_status`, `held`). A document is refused with a reason key:

| Reason key | Document state |
| --- | --- |
| `no_invoice` | No stored invoice to revalidate |
| `human_confirmed` | `review_status` is `human_confirmed` |
| `approval_decided` | Approved or rejected |
| `pipeline_running` | Generating or validating |
| `delivered` | Delivery attempts exist |

Revalidate keeps what the invoice says. Use [re-parse](#re-parse-documents) when the parser changed and the invoice must be rebuilt from the source PDF.

### Hold for approval

`EbillingDocument::holdForApproval()` marks a document as held (`approval_flags.held`; it survives re-validation) and `isHeldForApproval()` reads the mark. `AutoApproveEvaluator` then fails with `AutoApproveFailureReason::HeldForApproval` (`held_for_approval`), so a person must approve it. `--hold` on `e-billing:revalidate` and `e-billing:reparse` sets it, which keeps regenerated documents from being auto-approved and dispatched when the queue drains.

Recommended operator workflow: run with `--dry-run`, pick the ids, then run with `--hold`.

### Dispatch status

The invoice list shows a **Dispatch status** column (`DispatchStatus`, derived, never stored): `sent` (at least one successful delivery attempt), `delivery_failed` (attempts, none successful), `held` (pending approval and held), `awaiting_approval` (pending, not held), `rejected`, or `—` while the document is not ready to send. A select filter offers the same states (`DispatchStatus::constrain()`). Two list tabs use it: **Awaiting approval** (`approval_status` in `pending`, held documents included) and **Sent** (`delivered`). The detail page's status banner says when a pending document is held.

### Re-parse documents

After a parser fix, re-run the configured parser on documents that nobody has touched yet:

```bash
php artisan e-billing:reparse {ids?*} {--kosit-failed} {--order-missing} {--hold} {--dry-run}
```

| Argument / option | Effect |
| --- | --- |
| `ids` | One or more e-billing document ids |
| `--kosit-failed` | Selects documents whose latest KoSIT validation failed (combines with `ids`) |
| `--order-missing` | Selects documents whose `order_number` or `order_date` validated as missing (combines with `ids`) |
| `--hold` | Holds the reparsed documents for approval (see [Hold for approval](#hold-for-approval)) |
| `--dry-run` | Lists the selection (id, review status, gateway status, and whether it would be reparsed or refused with which reason) without changing anything |

Without ids and without `--kosit-failed` or `--order-missing` the command does nothing. Start with `--dry-run`, then run it again without it.

For each selected document the command re-parses the source PDF, soft-deletes and unlinks the draft invoice, resets `bill_data`, `field_validations` and `validation_score`, sets `review_status` to `parser_created` and `gateway_status` to `generating`, logs the activity `document_reparsed` (old and new net and line totals) and dispatches `GenerateArtifactJob`, which keeps the existing artifact paths. Artifact regeneration and KoSIT validation run on the queue; the new validation attaches to the same document and the earlier one stays as history.

Mind the queue: a regenerated document that now validates cleanly is auto-approved like any other, and its dispatch job follows on the same queue. Draining the queue after a bulk reparse therefore also delivers those documents.

A document is refused, never silently skipped, and the command prints the reason key:

| Reason key | Document state |
| --- | --- |
| `human_confirmed` | `review_status` is `human_confirmed` |
| `approval_decided` | Approved or rejected, or a person acted on the approval (pending set automatically after validation does not count) |
| `review_edited` | `review_changed_at` is set (a reviewer edited it) |
| `delivered` | Delivery attempts exist |

### Header fields satisfied by lines

A collective invoice may carry its order references per line instead of in the header. List such header fields in `field_validation.invoice_fields_satisfied_by_lines` (profile-scoped, see [Config keys](#config-keys)). An empty listed field then validates as `parsed` with `source: lines` when **every** invoice line carries its own value; each field is judged on its own. Otherwise the normal empty-field handling applies (a `must` field is `missing`). The review screen shows such a field as parsed, not missing.

When the header names no order (BT-13) but lines carry orders, a BT-22 note lists the distinct line orders (for example `Purchase orders: 4711 (2026-09-01), 4712` in `en`, `Bestellungen: 4711 (01.09.2026), 4712` in `de`; the date format is `emission.date_format`). No BT-13 is emitted, because EN 16931 allows only one; line orders ride in BT-127.

### Itemized document charges

`Invoice::$documentCharges` (bill data key `document_charges`) lists charges as `{field, amount, reason}` for the itemizable charge fields `shipping_cost`, `packaging_cost`, `minimum_quantity_surcharge`, `freight_flat_rate`, `certificate_cost` and `customs_cost` (positive amounts). When a field has rows, they replace that field's summed scalar on emission: one BG-21 charge per row, with the row's reason as text (or the default label when empty) and the field's reason code (for example `CAE` for `certificate_cost`). A host parser can use this to name the delivery note a charge belongs to.

`HeaderChargeResolver` matches a charge to its field when the reason text equals the label or starts with the label followed by a space (`Shipping delivery note 56793.26`), and adds up all matching charges for a charge field.

In the review workspace, a charge field with several charges shows their sum and is not editable (reason `several_charges`), followed by one row per charge (edit key `charge:<id>`) that edits that charge's amount.

### Severity gating (MoSCoW)

Per-field priority under `field_validation` drives three distinct behaviours:

| Priority | Missing field | Wrong content (`needs_review`) |
| --- | --- | --- |
| **must** | Blocks review; cannot be severity-released | Blocks review |
| **should** | Blocks review until `ReleaseSeverityFieldAction` records actor id, timestamp, and reason | Blocks review; not releasable |
| **could** | Recorded as `not_applicable` by the validator when absent; does not block | Does not block |

Severity release applies to **absent** fields only (`status: missing`). It is not a correction path for divergent content.

`ReleaseSeverityFieldAction` writes `released_at`, `released_by_id` (acting identity), `released_by` (display copy), and `reason` to the document's `severity_releases` JSON (invoice-level keys, or `lines.{lineId}.{field}` for line fields). Releases without a reason, without an authenticated actor, or with an entry that fails gate validation are refused. The column is cast but **not** in `$fillable` — only the action writes it; bulk `update([...])` silently drops releases.

`ConfirmInvoiceAction` refuses only while a configured must field is missing (ADR 0005); once confirmed, open `needs_review` and missing-should findings no longer block manual approval and dispatch (`hasUnacceptedReviewFindings()`).

Two related checks answer different questions:

| Check | Question | `review_status` filter |
| --- | --- | --- |
| `needsHumanReview()` | Does this document have unresolved findings? | None — used for gating |
| `scopeNeedsHumanReview()` | Is this document waiting in the review queue? | `parser_created` or `db_validated` only |

Within awaiting-review statuses, both use the same field predicate (including valid severity releases on **should** fields). When several severities apply, the most severe finding wins.

Changing a field's configured priority changes its behaviour with no code change.

**Totals reconciliation:** the invoice-level field `totals_reconciliation` (priority `must` in all three field profiles) checks that the sum of (line total + line charges - line allowances) + document charges - document allowances equals `net_total` (BT-109) within 0.01 (BR-CO-13). A mismatch sets `needs_review` with reason `totals_mismatch` and a `difference` (EUR, two decimals). It is never releasable, so the document cannot reach `validated`, and confirming does not accept it: `EbillingDocument::missingMustFields()` lists it, so confirm, manual approval and dispatch stay blocked until the amounts are corrected. The review amounts group shows it as "Totals reconciliation" with the difference and a hint.

**Document-level certificate and customs charges:** `certificate_cost` (reason code `CAE`, reason text "Attestkosten") and `customs_cost` (reason text "Zollkosten", no reason code) are emitted as BG-21 charges; priority `could` by default. `customs_cost` has no code because UNCL 7161 `ABW` is not in the EN 16931 code-list subset and fails BR-CL-20; a reason text alone satisfies BR-38 / BR-CO-22.

### Document-type profiles (credit notes, corrected invoices)

`FieldValidationProfile` selects which MoSCoW and ViewInvoice-denylist maps apply, keyed on the invoice's `document_type` (BT-3), ADR 0009. `field_validation.document_type_profiles` maps a type code to a key prefix — package default `381 => credit_note`, `384 => corrected_invoice`. A mapped type reads `field_validation.{prefix}_fields` / `{prefix}_line_fields` / `{prefix}_contextual_should` / `{prefix}_line_contextual_should` and `invoice_ui.{prefix}_fields_hidden` / `{prefix}_line_fields_hidden`; unmapped types read the `invoice_*` keys, and a host config that omits a `{prefix}_*` key falls back to the matching `invoice_*` key. The package default config spells out both profiles: the credit-note profile adds `preceding_invoice_number` / `preceding_invoice_date` as `could`, the corrected-invoice profile as `should`; invoices hide both fields.

**Document classification (ADR 0011):** `ClassifyDocumentTypeAction` lets a reviewer switch a document between the types in `e-billing.document_classification.types` (default `381 => positive`, `384 => negative`) before approval. It negates all document, line and allowance/charge amounts when the signs differ. The field changes are audited by moox/audit like any invoice update, and the act is logged as its own `document_classified` activity (from, to, amounts negated, origin) — never a value correction. `384 => negative` describes a correction issued as a credit (the delta); a host that issues full, positive restatements sets `positive`. Dropdown labels and "when to choose" hints: `DocumentClassificationLabels` (`e-billing::fields.document_classification.{code}.label|hint`). The credit-note list shows both types (`resources.credit_notes.document_types`) with per-type tabs and a type badge.

**Value correction (mooxphp/e-billing#45):** `CorrectFieldValueAction::execute($document, $target, $attribute, $value, $note = null)` lets an authenticated reviewer correct one field of the invoice, a line or an allowance/charge to what the source document says. It works on pending documents only (approved and rejected are refused) and needs no permission beyond an authenticated actor, like approval. The field must match `e-billing.value_correction.fields` (default `['*']`; `Str::is` patterns on the normalised field, e.g. `buyer.vat_id`, `lines.quantity`, `allowance_charges.amount`, `lines.allowance_charges.amount`) and be an attribute audited by moox/audit. JSON columns (`seller`, `buyer`, `delivery`, `payment_means`) are corrected per sub-key; a sub-key the column's structure does not have is refused, except the fixed `CREATABLE_KEYS` set the parser may leave out (see [Review workspace](#review-workspace)). The parsed value is read from the row's `created` activity (`ParsedValueHistory`); a row without one is refused. The document and the corrected row are re-read under a row lock inside the transaction, so an approval that lands after the models were loaded still refuses the correction, and `previous_value` is the stored value. A value equal after normalisation (numeric columns by value, other text trimmed so `0123` differs from `123`, empty string = null, dates by day) is a no-op: the action returns `false` and records nothing. If the save is cancelled (e.g. by a `saving` listener), nothing is recorded. Otherwise it writes the value and logs a `value_corrected` activity (`CorrectFieldValueAction::ACTIVITY_EVENT`, entry type `audit`, log name `e-billing`) on the Invoice with `action_type`, `field`, `target_type`, `target_id`, `position`, `parsed_value`, `previous_value`, `corrected_value` and `note`; actor and time are the activity's causer and `created_at`. **moox/audit is required for this feature** (a composer `suggest`, not a requirement): without it, or with `audit.enabled` / `e-billing.audit.enabled` off, the action throws a `RuntimeException`; when the activity cannot be written (e.g. logging disabled at runtime) the value change is rolled back (this covers the activity only when the activity log uses the default database connection). Corrections are append-only by convention: no package code updates or deletes activities, but moox/audit does not enforce immutability yet (mooxphp/audit#2), so keep `audit` entries out of retention pruning. `ReviewerActionType` names the reviewer acts (`value_correction`, `approval`, `rejection`, `severity_release`); only value corrections mean the parser read a value wrong. There is no UI yet (review workspace: mooxphp/e-billing#47), and regenerating or revalidating after a correction is not part of this action.

**Declared document type at upload:** `resources.{key}.manual_upload.document_types` (credit notes: `['381', '384']`) adds a required type choice without preselection to the upload dialog, with a collapsible instruction built from `document_classification.{code}.rule|examples` and `document_classification_help.*`. Codes must be in the resource's `document_types`, `document_classification.types` and `allowed_document_type_codes`. One code is applied without a choice; an empty list leaves the parser in charge. The choice is stored on the uploaded source, logged as `document_classified` (origin `upload`) and applied after parsing: a parsed 381/384 is replaced (with the sign flip), any other parsed type is kept and flagged for review. Credit notes and corrected invoices count as one type for duplicate detection. Hosts override the examples in `lang/vendor/e-billing/{locale}/fields.php`; example lists merge by index, so keep them the same length. Run the migration `add_document_type_to_ebilling_uploaded_pdf_sources_table`.

**Credit note payment terms:** `e-billing.credit_note_payment_terms` (default `null`) is emitted as BT-20 for a 381 without due date and payment terms, which BR-CO-25 otherwise rejects for a positive amount due.

**Preceding invoice reference (BG-3):** `preceding_invoice_number` / `preceding_invoice_date` map to `Invoice::$preceding_invoices[0]` (BT-25/BT-26). When present, `InvoiceFieldValidator` looks the referenced invoice up among stored invoices — number comparison ignores separators (`30641.25` matches `3064125`) — and never blocks: not found or a differing date is `status: parsed` with a `reason` (`preceding_invoice_not_found` / `preceding_invoice_date_mismatch`); a match sets `matched_id` and ViewInvoice renders `preceding_invoice_number` as a link to that invoice.

### Duplicate document-number rule

`InvoiceNumberDuplicateChecker` (used by `InvoiceFieldValidator`, and for identical-content discard in `GenerateArtifactJob` / `DiscardIdenticalContentDuplicateAction`) runs during field validation — before review clearance and the dispatch approval gate.

**Comparison scope** (`e-billing.duplicate_number.scope`, env `EBILLING_DUPLICATE_NUMBER_SCOPE`):

| Value | Behaviour |
| --- | --- |
| `global` (default) | Same `invoice_number` **and** same `document_type` (UNTDID 1001) anywhere. A commercial invoice (`380`) and a credit note (`381`) with the same number are **not** collisions. |
| `issuer` | Same as `global`, plus the same seller VAT id (EN 16931 BT-31). Use when several suppliers can issue overlapping number ranges. Blank / missing seller VAT ids only collide with other blank / missing VAT ids. |

Soft-deleted invoices are ignored (default SoftDeletes). Empty / blank document numbers are never treated as duplicates of each other; a missing number is a normal must-field finding.

**Outcomes:**

1. **Differing source PDF bytes**, same number + type (and issuer when scoped) → a new document version is created. Field validation flags `invoice_number` as `needs_review` with reason `duplicate_invoice_number` and `matched_id` set to the colliding invoice’s id. That blocks auto-`Validated` and syncs `approval_flags.duplicate` (blocks auto-approve; a human may still approve after review is clear).
2. **Identical source PDF** (same `invoice_number` + `document_type` + `source_content_hash`, and issuer when scoped) → discarded without creating a new invoice version (`gateway_status = ignored_identical_duplicate`). Operators are notified via Filament toast / database notifications (`e-billing.identical_duplicate`). This is an intentional short-circuit for re-processing or duplicate delivery, not a review case.

### Dispatch approval gate

Distinct from `review_status` (field-review clearance) and `gateway_status` (KOSIT/veraPDF validation), `approval_status` controls whether a validated document may enter the dispatch path.

| State | Meaning |
| --- | --- |
| `pending` | Awaiting human or automatic approval |
| `approved` | Cleared for dispatch |
| `rejected` | Rejected with a recorded reason |

Transitions write latest-only `approval_reason`, `approval_actor_id` (string; `'system'` for auto-approve), and `approval_acted_at` on the document. Approving a document that carries valid severity releases forwards those release reasons into `approval_reason` when no other reason is supplied. History is the `moox/audit` Activity trail on `EbillingDocument` (`approval_status`, `approval_reason` in the body; actor and time come from the Activity causer/timestamp, not extra attribute rows); the invoice detail Activity table aggregates the document via `aggregate_subjects`. Only `RecordApprovalTransitionAction` writes approval state for approve/reject/restore. Initialize and invalidate set `pending` and clear actor, time, and reason so a prior sign-off cannot dispatch. Rematch and manual attribution both invalidate prior approval. `DocumentApprovalTransitioned` is emitted for host listeners (the package does not send mail).

A credit note (BT-3 `381`) with a negative gross total (BT-112) blocks approval and dispatch (ADR 0010): `CreditNoteSign::hasNegativeTotal()` is checked by `DocumentApprovalGuard` (manual approve refused), `AutoApproveEvaluator` (`AutoApproveFailureReason::CreditNoteNegativeTotal`), and `DocumentDispatchGuard` (block reason `credit_note_negative_total`). Flipping the sign is the parser's job, not this package's.

`DocumentDispatchGuard` requires `approval_status = approved` and a non-empty actor id plus `approval_acted_at` when approval is required. Approved-but-missing actor or acted-at blocks with `approval_incomplete`. It never reads Activity. Auto-approve persists with no authenticated user so the Activity causer is the host `audit.system_causer` (when set), not a logged-in operator. Document actor id on the row stays `'system'`.

**Automatic approval** runs after gateway validation when every condition holds separately: gateway validated, no unresolved review findings, no blocking must-field, no duplicate flag (`approval_flags.duplicate`), no anomaly flag (`approval_flags.anomalies`). Failing any one leaves the document pending. Field validation syncs `approval_flags.duplicate` when `invoice_number` has reason `duplicate_invoice_number`; hosts may set `approval_flags.anomalies` on the document for anomaly flags (no dedicated writer API on the model). **Manual approve** requires pending status, a deliverable gateway artifact, no missing must field, and no unaccepted review findings (`hasUnacceptedReviewFindings()`: on a `human_confirmed` document only missing must fields count, otherwise same as `needsHumanReview()`), so a confirmed document with open `needs_review` or missing-should findings can be approved and dispatched (ADR 0005 amendment); duplicate and anomaly flags do not block a human sign-off after review is clear.

| Config key | Default | Effect |
| --- | --- | --- |
| `approval.required` | `true` | When `false`, `DispatchDocumentAction` skips approval and allows dispatch on validation alone |
| `approval.auto_approve_enabled` | `true` | When `false`, clean documents stay pending until a reviewer approves |

`DispatchDocumentAction` refuses unapproved documents at the dispatch seam (not only in the Filament UI). A blocked must-field cannot reach `approved` by any route.

### Delivery dispatch

Final pipeline stage after approval: get an approved document to its recipients and record each attempt.

**Port:** `DeliveryChannelInterface` — `key()` plus `deliver(document): list<DeliveryOutcome>` (recipient, success, failure reason, correlation id). Hosts list implementing FQCNs in `e-billing.delivery.channels`. This package ships **no transport** (no SMTP/Peppol/portal). Tests use a recording fake.

**Optional orchestrator:** `MailDeliveryChannel` (key `mail`) resolves recipients via `DeliveryRecipientResolverInterface`, sends via host-bound `InvoiceMailSenderInterface`, and maps each result to a `DeliveryOutcome`. Register it only through `delivery.channels`. It does **not** depend on mail-outbox; the host binds any sender. When the resolver returns an empty list, the channel returns one failed `DeliveryOutcome` with `failureReason` `no_recipient` (`MailDeliveryChannel::FAILURE_NO_RECIPIENT`) and does **not** call the sender (no silent no-op).

**Default recipient resolver:** when the host does not bind `DeliveryRecipientResolverInterface`, the package binds `ConfigurableDeliveryRecipientResolver`. Strategy is chosen per source:

| Source | Config key | Default |
| --- | --- | --- |
| Mail-sourced (`InboxAttachment`) | `delivery.recipients.mail_source` | `inbox_to` |
| Manual upload (`UploadedPdfSource`) | `delivery.recipients.manual_upload` | `none` |
| Other / unknown source | — | treated as `none` |

| Strategy value | Recipients |
| --- | --- |
| `inbox_to` | The address a reviewer set on the document (`buyer.contact.email`) when there is one, else the validated `InboxMessage.to_email` (optional `to_name`); empty or invalid → no recipients |
| `document` | Only the address a reviewer set on the document (`buyer.contact.email`, BT-58); none set → no recipients |
| `master` | Validated attributed `company.email` (optional company name); empty or invalid → no recipients |
| `none` | Empty list |

Hosts may still bind their own `DeliveryRecipientResolverInterface` to replace this policy.

**Manual upload:** the upload dialog collects `recipient_email` when enabled (required when MoSCoW `buyer_email` is `must` for the declared document type). It is stored on `UploadedPdfSource` (`uploader_user_id`, `recipient_email_applied_at`) and applied once to `buyer.contact.email` during `GenerateArtifactJob` via `ApplyUploadedRecipientEmailAction` (audited `recipient_set`, origin `upload`).

**Invoice view:** the buyer section shows `buyer_email` = `EbillingDocument::recipientEmail()`: the address a reviewer set on the document (`buyer.contact.email`, BT-58; `documentRecipientEmail()`), else the validated inbox To email (`InboxMessage.to_email`). It is not corroborated against master data; the UI shows a soft informational hint (`hint_info_buyer_email`). A set document address or inbox To counts as present for validation; when both are empty, MoSCoW applies (`must` → `missing`, which blocks confirm/approval — including manual uploads). Reviewers set it in the [review workspace](#delivery-recipient-in-review). Labels: en `Recipient email` / de `Empfänger-E-Mail`.

**Records:** table `ebilling_delivery_attempts` — one row per channel × recipient × attempt (append-only; re-dispatch adds rows). No document-level “delivered” flag. Invoice detail shows the attempt history; when `moox/audit` is present, each attempt also writes an Activity entry (`delivery_attempted`); configured `audit.log_events` attributes become Spatie `attribute_changes` for the Änderungen UI (same path as model audits).

**Job:** approving (manual or auto) calls `QueueDocumentDeliveryAction`, which queues `DispatchDocumentJob` when `delivery.enabled` is true (all configured channels). The job uses `JobProgress` and implements `failed()`. Filament **Re-dispatch** (*Erneut zustellen*) is **selective redispatch** (ADR 0008): the operator picks a non-empty subset of configured channels (defaults to failed / never-run; warns when re-selecting successes); optional `$channelKeys` on Queue → Job → `DispatchDocumentAction`. No work in a listener.

| Config key | Default | Effect |
| --- | --- | --- |
| `delivery.enabled` | `false` | When `false`, approve does not queue dispatch and `DispatchDocumentAction` writes no records (gate still asserted) |
| `delivery.mailer` | `null` | Optional Laravel mailer name (`EBILLING_DELIVERY_MAILER`) for host senders |
| `delivery.recipients.mail_source` | `inbox_to` | Recipient strategy for mail-sourced documents (`inbox_to` \| `document` \| `master` \| `none`) |
| `delivery.recipients.manual_upload` | `none` | Recipient strategy for manual uploads (`inbox_to` \| `document` \| `master` \| `none`) |
| `delivery.channels` | `[]` | FQCNs implementing `DeliveryChannelInterface` |

### Review notification announce

When a document enters dispatch-approval review (auto-approve fails while `pending`, or approval is invalidated back to `pending`), the package announces without sending mail and without knowing recipients:

1. `AnnounceDocumentNeedsReviewAction` deduplicates with cache key `e-billing.review-notified.{id}` (unique while still `pending`; cleared when leaving `pending` and before re-entering from non-pending).
2. Emits `DocumentEnteredReview` (`document` + `reasons` from `AutoApproveFailureReason` values, or `awaiting_approval` when empty).
3. Delegates to `ReviewNotificationStrategyInterface`:
   - **`immediate`** (default) — dispatches one `NotifyDocumentsNeedReviewJob` per document via `ReviewNotificationDispatcher`.
   - **`batched`** — only collects `{reasons, collected_at}` under a cache batch key; does **not** dispatch the notify job yet.

**Job payload** (`NotifyDocumentsNeedReviewJob`): list of `{ document_id, reasons: string[], waited_seconds: int, escalation_level?: string }` — no recipients, no subject/body/wording. Hosts listen to the event and/or handle the job. The package never sends mail. Optional `escalation_level` is set only by the overdue-approval scan (see [Approval escalation scan](#approval-escalation-scan)).

**Activity trail (opt-in, host config):** every notify dispatch goes through `ReviewNotificationDispatcher`, which calls `ReviewNotificationRecorderInterface`. Package default is `NullReviewNotificationRecorder` when `notification.recorder` is `null`. Set `e-billing.notification.recorder` to a class implementing the interface (same pattern as `e-billing.parser`) — a host recorder may write `moox/audit` log entries (`review_notification_dispatched` / `approval_escalation_notified`) on the `EbillingDocument` for InvoiceResource via `aggregate_subjects`.

**Batch keys** (`notification.batch_key`):

| Value | Cache key |
| --- | --- |
| `window` | `e-billing.review-batch.window.{floor(timestamp / (minutes*60))}` (`notification.batch_window_minutes`, default 60) |
| `day` | `e-billing.review-batch.day.{Y-m-d}` |

**Flush:** `e-billing:flush-review-notification-batch` dispatches `FlushReviewNotificationBatchJob`, which drains the current batch store, builds payloads with `waited_seconds = now - collected_at`, dispatches **one** `NotifyDocumentsNeedReviewJob` via `ReviewNotificationDispatcher` (and invokes the configured recorder), and clears the store. Schedule the command (or job) in the host app — the package does not register a schedule.

| Config key | Default | Effect |
| --- | --- | --- |
| `notification.strategy` | `immediate` | `immediate` or `batched` |
| `notification.batch_key` | `window` | `window` or `day` |
| `notification.batch_window_minutes` | `60` | Window size when `batch_key=window` |
| `notification.recorder` | `null` | Class implementing `ReviewNotificationRecorderInterface`, or null for no-op |

### Approval escalation scan

Scheduled scan for documents still `approval_status = pending` past configured thresholds. The package announces again without sending mail and without knowing recipients:

1. `e-billing:scan-overdue-approval-escalation` dispatches `ScanOverdueApprovalEscalationJob` (`ShouldBeUnique`, JobProgress, `failed` logging). Schedule the command (or job) in the host app — the package does not register a schedule (same pattern as `e-billing:flush-review-notification-batch`).
2. The job scans pending documents; the wait clock is `created_at`. For each document it considers only the **next unmet** level in the configured order, then dispatches **one** `NotifyDocumentsNeedReviewJob` via `ReviewNotificationDispatcher` **directly** (not via `ReviewNotificationStrategyInterface`). Host-bound recorders may log `approval_escalation_notified` when opted in.
3. Payload items add optional `escalation_level` (the level `key`). `reasons` come from `AnnounceDocumentNeedsReviewAction::reasonsFor()` (auto-approve failures, or `awaiting_approval` when empty); `waited_seconds` is `now - created_at`.

**Levels** (`e-billing.escalation.levels`): ordered list of `{ key, after, unit }` where `unit` is `hours` or `days`. Empty list disables the feature. Hours use wall-clock time. Days use `day_counting`:

| Value | Behaviour |
| --- | --- |
| `working` (default) | Count working dates strictly after `created_at`'s date through today, using `working_weekdays` (ISO weekdays, default Mon–Fri) and skipping `exclude_dates` (`Y-m-d` holidays) |
| `calendar` | Calendar day difference from `created_at` start-of-day to now start-of-day |

`day_counting` / weekdays / exclude dates apply **only** to levels with `unit=days`.

**Dedup cache:** `e-billing.review-escalated.{documentId}.{levelKey}` via `Cache::add` (one notify per document per level while still pending). Cleared when leaving `pending` (`RecordApprovalTransitionAction`) and on `InvalidateDocumentApprovalAction`. Use Redis or another shared cache store for multi-worker correctness (same requirement as `e-billing.review-notified.{id}`).

| Config key | Default | Effect |
| --- | --- | --- |
| `escalation.day_counting` | `working` | `working` or `calendar` (days levels only) |
| `escalation.working_weekdays` | `[1,2,3,4,5]` | ISO weekdays counted as working days |
| `escalation.exclude_dates` | `[]` | Holiday dates (`Y-m-d`) excluded from working-day counts |
| `escalation.levels` | `[]` | Ordered thresholds; empty = feature off |



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
| `profile` | `string` | nullable | Frozen effective profile at first generation (GoBD); retries do not re-resolve |
| `artifact_content_hash` | `string` | nullable | SHA-256 of validated deliverable (set on KOSIT pass) |
| `ignored_reason` | `json` | nullable | Foreign-invoice classification details |
| `gateway_status` | `string` | nullable | Format-agnostic pipeline stage: `generating`, `generation_failed`, `validating`, `validated`, `validation_failed`, `validator_error`, `ignored_foreign`, `ignored_identical_duplicate` (indexed) |
| `review_status` | `string` | NOT NULL | Review stage; default `parser_created` (indexed) |
| `approval_status` | `string` | nullable | Dispatch approval (`pending`, `approved`, `rejected`); indexed; not in `$fillable` |
| `approval_reason` | `text` | nullable | Latest reject/restore (or optional approve) reason; not in `$fillable` |
| `approval_actor_id` | `string` | nullable | Latest actor id (`'system'` for auto-approve); not in `$fillable` |
| `approval_acted_at` | `timestamp` | nullable | Latest approval action time; not in `$fillable` |
| `approval_flags` | `json` | nullable | Duplicate/anomaly flags for auto-approve; not in `$fillable` |
| `validation_score` | `unsignedTinyInteger` | nullable | Aggregated field-validation score |
| `field_validations` | `json` | nullable | Per-field validation results |
| `severity_releases` | `json` | nullable | Severity releases for missing **should** fields (`released_at`, `released_by_id`, `released_by`, `reason`); written only via `ReleaseSeverityFieldAction`; not in `$fillable` |
| `processed_at` | `timestamp` | nullable | Set when validation passes |
| `review_changed_at` | `timestamp` | nullable | Last reviewer change (`markReviewChanged()`); newer than `processed_at` means the artifact needs regeneration; not in `$fillable` |
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
`DeliveryDateTransmission` also reads DTO `deliveryDate`; `ZugferdInvoiceDtoAdapter` promotes a single shared line date to document BT-72 the same way as the model adapter.

The invoice `delivery` party (name + address) is mapped onto `shipToName` / `shipToAddress`. `moox/zugferd` emits ShipTo (BG-13) when a name or an address with a country is present **and** the party is not equal to the buyer (name + postal fingerprint; city excluded). Equal-to-buyer consignees are omitted from BG-15 while BT-72 may still emit; VAT category **K** still forces a full BG-15. A consignee without a country is still stored and shown; when emitted, the converter sends the name only (no postal address group). Ship-to tax registration and contact are never written. See root ADR 0020 and feature test `ZugferdOmitDuplicateConsigneeXmlTest`.

| Situation | What reaches the artifact |
|-----------|---------------------------|
| One unique date across the invoice header and all lines | Document actual delivery only (BT-72) |
| Several differing dates | Per-line dates only; no document BT-72 |
| Any case | No header invoicing period (BG-14) is synthesized from delivery dates |

When several dates differ, each line with a `delivery_date` is emitted on that line. EN 16931 / XRechnung (and other non-EXTENDED profiles) carry the date as a line billing period with start and end equal to that day; EXTENDED uses the line-level actual delivery date. The active ZUGFeRD profile selects which line carrier `ZugferdConverter` uses.

**Partial deliveries.** A line delivered in several parts (`invoice_lines.deliveries`, list of `{date, delivery_note, quantity}`, normalized by `Support\PartialDeliveries::normalize()`) counts its partial dates as line dates: differing dates leave out the document BT-72, emit line dates, and are visible to the intra-community check. On the line, `moox/zugferd` writes a period (BG-26) from the earliest to the latest date and lists the parts in BT-127. Quantities in that note follow `document_locale` (`emission.decimal_separator`: `,` in `de`, `.` in `en`). Run the `moox/invoice` migration `add_deliveries_to_invoice_lines_table`; parsers pass the parts through `Data\InvoiceLine::$partialDeliveries` (array key `deliveries`).

`InvoiceFieldValidator` flags `delivery_date` as `needs_review` when the invoice is an intra-community supply (seller and buyer EU VAT country prefixes differ) and several differing dates would require aggregating them into a single actual delivery date for BR-IC-11. Operators see a review hint in the Filament UI; the adapter does not merge dates silently.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

Thanks to so many [people for their contributions](https://github.com/mooxphp/moox#contributors) to this package.

## License

The MIT License (MIT). Please see [our license and copyright information](https://github.com/mooxphp/moox/blob/main/LICENSE.md) for more information.

## Payment means and VAT category defaults

| Config key | Default | Codelist |
|---|---|---|
| `e-billing.payment_means_code` | `58` (SEPA) | UNTDID 4461 via `moox/data` |
| `e-billing.vat_category_code` | `S` (standard) | UNTDID 5305 via `moox/data` |

`ConfiguredEn16931CodeResolver` validates these at stamp/resolve time (fail fast if the code is blank, unknown, or the static table is empty — run `php artisan moox:data:import-codelists`). The values are stamped onto each mapped invoice (`payment_means.payment_means_code`, `vat_category`); lines inherit the header VAT category for MoSCoW BT-151.

## Invoice ViewInvoice UI

Presentation-only config under `e-billing.invoice_ui` (does **not** change MoSCoW validation):

| Key | Purpose |
|---|---|
| `invoice_fields_hidden` / `invoice_line_fields_hidden` | Field keys never shown on ViewInvoice (even when filled) |
| `field_groups.*.default_open` | Whether `document` / `supplier` / `buyer` / `delivery` / `totals` / `notes` start expanded |

Groups with **visible** blocking `must` findings force-open and show a text+colour issue count on the summary. Denylisted fields do not count toward that marker. Empty groups after the denylist are omitted. See ADR `docs/adr/0007-invoice-view-field-denylist-and-collapsible-groups.md`.


## Review workspace

Edit mode on the invoice and credit-note view pages (`ViewInvoice` / `ViewCreditNote`) combines value corrections and customer attribution in one place. Design rationale: ADR `docs/adr/0004-mixed-review-workspace-and-leave-edit-pipeline.md` ([#47](https://github.com/mooxphp/e-billing/issues/47), [#48](https://github.com/mooxphp/e-billing/issues/48)).

### Requirements

Edit mode is available only while the document's approval status is `pending`, the pipeline is not running (`gateway_status` is neither `generating` nor `validating`; `DocumentEditGuard::canEdit()`) and `moox/audit` auditing is available (a correction keeps the parsed value in the audit trail). Otherwise the header actions do not appear. All reviewer acts (value correction, classification, recipient, attribution) also refuse changes while the pipeline runs.

### Edit and leave

- **Edit** (`start_review_edit`) turns on edit mode. **Confirm** and **approve** are hidden while you edit.
- Each row gets a pencil action (`editField`) that opens a slide-over on the start side, so the PDF preview stays visible. It saves exactly that row as a value correction. The note is optional.
- Customer attribution changes through `editAttribution` inside the edit banner (`SetInvoiceAttributionAction`, `attribution_source = manual`).
- **Finish editing** (`finish_review_edit`, with confirmation) leaves edit mode and runs `LeaveEditAction`. It always re-runs matching and field checks synchronously (manual attribution preserved), so fixed master data is picked up. If a reviewer changed anything since the last successful validation, it also sets `gateway_status = generating` and queues `GenerateArtifactJob`, which regenerates the e-invoice from the corrected rows and then validates it. Per-field saves never start this pipeline.
- **Approve** is hidden while you edit. Outside edit mode it shows for every pending document (when approval is required) and is disabled with the reason as tooltip; the status banner repeats it. Reason codes (`DocumentApprovalGuard::blockReason()`): `not_pending`, `pipeline_running`, `artifact_failed`, `artifact_not_validated`, `must_field_missing`, `human_review_required`, `credit_note_negative_total` (checked in this order). Confirming the document accepts open review findings; a leave-edit rematch or an attribution change resets the confirmation. A document a reviewer changed is never auto-approved (`review_changed`).
- The view shows `generating` / `validating` in the status banner and polls (`wire:poll.5s`) only while the pipeline runs. Hosts that override `filament/partials/invoice-status-banner.blade.php` must port the gateway and approval-block rows.

Run the migration `add_review_changed_at_to_ebilling_documents_table` (publish or copy it): `review_changed_at` is stamped by the reviewer acts and compared with `processed_at` (`EbillingDocument::hasReviewChangesSinceArtifact()`). The stale-artifact approval block against the record files follows with [#86](https://github.com/mooxphp/e-billing/issues/86).

The standalone `set_attribution` and `rematch` actions (view header and invoice list row action) and `InvoiceResource::getSetAttributionAction()` are removed, together with their translation keys.

### Which rows can be edited

Every row resolves through `ReviewFieldCatalog` (row keys `invoice:{field}`, `notes:{index}`, `line:{lineId}:{field}`) to either an edit or a visible, translated reason (`e-billing::fields.review_not_editable.*`):

| Reason key | Meaning |
|---|---|
| `not_configured` | Field is not in `value_correction.fields` |
| `not_audited` | Attribute is not audited, so its parsed value could not be kept |
| `unknown_key` | The document has no such value |
| `unknown_field` | Row is not mapped to an editable field |
| `no_charge_row` | The allowance/charge row does not exist; adding one is not supported |
| `document_level` | Line `vat_category` is a document-level value; correct it under Amounts |
| `not_classifiable` | The document type is not one of `document_classification.types` |

- **Addresses** (buyer, seller, delivery, line delivery) and **bank accounts** edit in one slide-over but record one correction per changed sub-field (`CorrectFieldValueAction::executeMany()`).
- **Header charges and line surcharges** edit the existing allowance/charge row (`amount`, `percentage`, `reason_text`).
- **`document_type`** switches between the configured classification types through `ClassifyDocumentTypeAction`; it is not a value correction.
- Corrected rows show "Corrected. Parsed value: …" (from `ParsedValueHistory::correctedFields()`), in and outside edit mode.

An attribute must also be audited by its model to be correctable: the `invoice.audit` attributes of `moox/invoice` (and any host override) decide which fields get a pencil rather than `not_audited`.

`CorrectFieldValueAction` can create a fixed set of JSON sub-keys the parser left out (`CREATABLE_KEYS`): seller/buyer contact name, phone and email; delivery name and address parts; the `payment_means` code and the first bank account's `iban` / `bic` / `bank_name`; the first preceding invoice's number and date. The parsed value of a created key is `null`. JSON sub-keys of one column are written together, and a value a JSON value object would drop is refused. `refusal()` returns the `REFUSAL_*` code for a field that cannot be corrected; `field()` returns its normalised field name.

### Cross-checked fields

`e-billing.cross_checked` is the single catalogue of fields checked against master data. The review workspace uses it for pickers; the divergence report ([#46](https://github.com/mooxphp/e-billing/issues/46)) is intended to use the same mapping.

```php
'cross_checked' => [
    'strict' => (bool) env('EBILLING_CROSS_CHECKED_STRICT', false),
    'fields' => [
        'customer_number' => ['record' => 'customer', 'attribute' => 'customer_number'],
        'customer_name' => ['record' => 'company', 'attribute' => 'name'],
        'customer_vat_id' => ['record' => 'company', 'attribute' => 'vat_number'],
        'customer_address' => ['record' => 'company', 'addresses' => 'buyer'],
        'delivery_address' => ['record' => 'company', 'addresses' => 'delivery'],
    ],
],
```

Each field names the matched `record` (`customer` or `company`) and either an `attribute` of it or its `addresses` filtered by the `corroboration` role config (`buyer` or `delivery`). `CrossCheckedFields` scopes the options to the attributed customer:

| Mode | Behaviour |
|---|---|
| `strict = false` (default) | Datalist suggestions plus free text. Addresses add an "Address from master data" picker that fills the parts |
| `strict = true` | Select-only. For addresses, leaving the picker empty clears the address parts |

Values that are not in the attributed customer's master data are flagged in edit mode, never cleared; a field whose master data has no value is not flagged. Review never writes master data.

A column that does not accept `null` (e.g. `invoice_number`) cannot be cleared; the save is refused with "The field … cannot be empty."

### Delivery recipient in review

The `buyer_email` row edits the document's delivery recipient through `SetRecipientEmailAction`:

- **Mail-sourced documents:** a value correction of `buyer.contact.email`.
- **Other sources (manual upload):** writes `buyer.contact.email` and logs an audited `recipient_set` activity, so it stays out of parser-feedback data.

Use the `document` recipient strategy (see [Delivery dispatch](#delivery-dispatch)) to deliver only to an address set this way.
