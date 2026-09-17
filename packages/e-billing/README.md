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
- Manual customer attribution via `SetInvoiceAttributionAction` (Filament header action kept as `InvoiceResource::getSetAttributionAction()` for a later surface) and explicit rematch from the invoice detail and list
- Host-bound invoice parser via `InvoiceParserInterface` (no parser ships with this package)
- Delivery-date carriage into generated artifacts: one unique date → document actual delivery (BT-72); several differing dates → per-line dates only (no document BT-72, no invoicing-period merge); intra-community invoices with multiple dates surface `delivery_date` as `needs_review` (BR-IC-11) instead of aggregating
- Consignee party on invoice and line `delivery` (name + address): persisted even without a country; detail views show the name first (`PartyAddressFormatter`); field label Consignee (hint BG-13); generated artifacts emit ShipTo (BG-13) from `shipToName` / `shipToAddress` without tax registration or contact — address group (BG-15) only when a country is present

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
| `default` | Single default when the preference port returns null: `format` (FormatRegistry key, default `zugferd`) + `profile` (hybrid library profile, default `EN16931`, must be in `allowed_profiles`). XRechnung ignores `profile` and always uses `XRECHNUNG` |
| `allowed_profiles` | Hybrid profiles a recipient preference may request for `zugferd` / `factur-x` (default `['EN16931']`). XRechnung never takes a preference profile |
| `zugferd` | ZUGFeRD filesystem disk (`storage_disk`, `storage_root`). Hybrid profiles come from `default.profile`, not `moox/zugferd` config |
| `default_customer_country` | Transitional fallback buyer country when the parser derives none (default `DE`); removed in a future master-data phase |
| `supplier` | Central supplier master data copied onto invoices as a snapshot at creation time |
| `corroboration` | Post-attribution master-data checks (never clears `customer_id`): `name_min_token_length`, `name_legal_form_stop_words`, `buyer_address_roles` (billing + postal), `delivery_address_roles` (delivery first, then postal/billing fallback) |
| `field_validation` | MoSCoW priority rules for invoice and line fields |
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
```

| Variable | Config key | Default | Required |
| --- | --- | --- | --- |
| `EBILLING_PREFERRED_PIECE_UNIT_CODE` | `preferred_piece_unit_code` | `H87` | No |
| `EBILLING_APPROVAL_REQUIRED` | `approval.required` | `true` | No |
| `EBILLING_APPROVAL_AUTO_APPROVE` | `approval.auto_approve_enabled` | `true` | No |
| `EBILLING_REVIEW_NOTIFICATION_STRATEGY` | `notification.strategy` | `immediate` | No |
| `EBILLING_REVIEW_NOTIFICATION_BATCH_KEY` | `notification.batch_key` | `window` | No |
| `EBILLING_REVIEW_NOTIFICATION_BATCH_WINDOW` | `notification.batch_window_minutes` | `60` | No |
| `EBILLING_ESCALATION_DAY_COUNTING` | `escalation.day_counting` | `working` | No |
| `EBILLING_DUPLICATE_NUMBER_SCOPE` | `duplicate_number.scope` | `global` | No |
| `EBILLING_DELIVERY_ENABLED` | `delivery.enabled` | `false` | No |
| `EBILLING_DELIVERY_MAILER` | `delivery.mailer` | `null` | No |
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

`DocumentDispatchGuard` requires `approval_status = approved` and a non-empty actor id plus `approval_acted_at` when approval is required. Approved-but-missing actor or acted-at blocks with `approval_incomplete`. It never reads Activity. Auto-approve persists with no authenticated user so the Activity causer is the host `audit.system_causer` (when set), not a logged-in operator. Document actor id on the row stays `'system'`.

**Automatic approval** runs after gateway validation when every condition holds separately: gateway validated, no unresolved review findings, no blocking must-field, no duplicate flag (`approval_flags.duplicate`), no anomaly flag (`approval_flags.anomalies`). Failing any one leaves the document pending. Field validation syncs `approval_flags.duplicate` when `invoice_number` has reason `duplicate_invoice_number`; hosts may set `approval_flags.anomalies` on the document for anomaly flags (no dedicated writer API on the model). **Manual approve** requires pending status, a deliverable gateway artifact, no unresolved human-review findings, and no blocking must-field; duplicate and anomaly flags do not block a human sign-off after review is clear.

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
| `inbox_to` | Validated `InboxMessage.to_email` (optional `to_name`); empty or invalid → no recipients |
| `master` | Validated attributed `company.email` (optional company name); empty or invalid → no recipients |
| `none` | Empty list |

Hosts may still bind their own `DeliveryRecipientResolverInterface` to replace this policy.

**Invoice view:** the buyer section shows display-only `buyer_email` = inbox To email (validated `InboxMessage.to_email`). It is not corroborated against master data; the UI shows a soft informational hint (`hint_info_buyer_email`). Labels: en `Recipient email` / de `Empfänger-E-Mail`.

**Records:** table `ebilling_delivery_attempts` — one row per channel × recipient × attempt (append-only; re-dispatch adds rows). No document-level “delivered” flag. Invoice detail shows the attempt history; when `moox/audit` is present, each attempt also writes an Activity entry (`delivery_attempted`); configured `audit.log_events` attributes become Spatie `attribute_changes` for the Änderungen UI (same path as model audits).

**Job:** approving (manual or auto) calls `QueueDocumentDeliveryAction`, which queues `DispatchDocumentJob` when `delivery.enabled` is true. The job uses `JobProgress` and implements `failed()`. Filament **Re-dispatch** re-queues the same job. No work in a listener.

| Config key | Default | Effect |
| --- | --- | --- |
| `delivery.enabled` | `false` | When `false`, approve does not queue dispatch and `DispatchDocumentAction` writes no records (gate still asserted) |
| `delivery.mailer` | `null` | Optional Laravel mailer name (`EBILLING_DELIVERY_MAILER`) for host senders |
| `delivery.recipients.mail_source` | `inbox_to` | Recipient strategy for mail-sourced documents (`inbox_to` \| `master` \| `none`) |
| `delivery.recipients.manual_upload` | `none` | Recipient strategy for manual uploads (`inbox_to` \| `master` \| `none`) |
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
