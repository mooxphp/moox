---
status: accepted
---

# Mixed review workspace and leave-edit pipeline

## Context

Invoice review split **value correction** (restoring parsed fields to what the source document says) from **customer attribution** (binding the document to a customer). Separate admin actions for set-attribution, re-match, and regenerate/revalidate multiplied entry points and made it unclear when the artifact was stale relative to review changes.

Product decisions in [#40](https://github.com/mooxphp/e-billing/issues/40) (amendment), [#47](https://github.com/mooxphp/e-billing/issues/47), and [#48](https://github.com/mooxphp/e-billing/issues/48) supersede the standalone attribution UI from [#27](https://github.com/mooxphp/e-billing/issues/27). Domain rules from #27 remain: `attribution_source` distinguishes auto vs manual, and rematch must not silently overwrite a manual attribution.

## Decision

### Mixed review workspace

One **edit mode** on the existing invoice admin document view combines:

- **Value corrections** on the configured **correctable** field set (per-field save, append-only correction records — [#45](https://github.com/mooxphp/e-billing/issues/45) / [#47](https://github.com/mooxphp/e-billing/issues/47)).
- **Customer attribution** on the same surface (searchable customer control; sets `attribution_source = manual` when the operator chooses a customer).

There is no separate attribution-only screen and no parallel edit flow.

### Master-data pickers (document-only)

For fields in the configured **cross-checked** set (same catalogue as corroboration / divergence — not a second list), the editor offers a searchable dropdown of master-data / bound data-package options **scoped to the currently attributed customer**, plus:

- **Default (permissive):** free text on the **document** when the value is not in that list.
- **Strict (later config):** select-only — free text disabled for that set and for the customer control.

Review **never writes master data**. Free entry and select-only only constrain what is stored on the document.

### Customer change mid-edit

When the attributed customer changes while still editing:

- Field values already on the document are **kept**.
- Master-data dropdown options **re-scope** to the new customer immediately.
- Values absent from the new customer’s master data are **flagged** (not auto-cleared, not written into master data).

### Per-field save vs leave-edit pipeline

- **Per-field saves** persist value corrections only; they do **not** rematch, regenerate, or revalidate.
- **Confirmed leave-edit** is the single reviewer-facing trigger for: rematch (manual attribution preserved) → regenerate via the existing idempotent path when needed → revalidate (existing validation job path). The reviewer is warned before this runs.
- Durable **Validating… / done / failed** status appears on the invoice admin surface; an actor notification on completion is allowed in addition to that status.
- Standalone set-attribution, re-match, and regenerate/revalidate admin actions are **retired** when this ships.

### Stale-artifact approval invariant

Approval remains refused while the artifact predates the most recent correction, while the leave-edit pipeline is in progress, or after a failed re-validation. Approval itself never starts regeneration.

### Semantics that stay

- Value correction still restores what the **source document** says — it does not align the document to master data to hide a divergence.
- Master-data divergence reporting stays separate ([ADR 0002](0002-reports-live-in-the-package-that-owns-their-data.md)).
- Manual `attribution_source` still survives rematch overwrite, including rematch started by leave-edit.

## Considered options

- **(a) Keep separate attribution and re-match buttons.** Rejected: duplicate entry points and unclear staleness relative to field corrections ([#47](https://github.com/mooxphp/e-billing/issues/47)).
- **(b) Auto-rematch on every field save.** Rejected: thrashing and unpredictable approval gates; #27 required an explicit human rematch trigger — leave-edit is that trigger ([#48](https://github.com/mooxphp/e-billing/issues/48)).
- **(c) Free entry writes master data from review.** Rejected: blurs document truth vs CRM stewardship; destroys a clean divergence baseline ([#40](https://github.com/mooxphp/e-billing/issues/40) out of scope).
- **(d) Keep a separate regenerate button alongside leave-edit.** Rejected: two contracts for the same downstream work.

## Consequences

- Operators get one mental model: edit fields and customer together, save fields incrementally, confirm once to refresh match and artifacts.
- Implementers must wire leave-edit to the existing rematch / generate / validate paths rather than inventing a second pipeline.
- Hosts must not expect master-data CRUD from review; CRM gaps stay on the divergence report and owning resources.
- Strict select-only is a config upgrade without changing workspace shape.
- Tests should assert: per-field save does not enqueue the full pipeline; leave-edit does; approval stays blocked under stale / in-progress / failed validation; manual attribution survives rematch.

## Addendum (2026-09-30): implementation decisions

Decisions taken while implementing [#47](https://github.com/mooxphp/e-billing/issues/47).

### Every row resolves to an edit or a reason

Each row of the document view resolves through `ReviewFieldCatalog` (row keys `invoice:{field}`, `notes:{index}`, `line:{lineId}:{field}`) to an edit or a visible, translated reason (`e-billing::fields.review_not_editable.*`: `not_configured`, `not_audited`, `unknown_key`, `unknown_field`, `no_charge_row`, `document_level`, `not_classifiable`). No row silently lacks a control.

- Addresses (buyer, seller, delivery, line delivery) and bank accounts edit in one slide-over but record **one correction per changed sub-field** (`CorrectFieldValueAction::executeMany()`), so the correction data stays per field.
- Header charges and line surcharges edit the **existing** allowance/charge row (`amount`, `percentage`, `reason_text`). A missing row is **not creatable** for now (`no_charge_row` is shown); adding allowance/charge rows is out of scope.
- A line's `vat_category` is document-level: shown with the `document_level` reason.
- `document_type` is a classification, not a value correction: it switches between the configured types through `ClassifyDocumentTypeAction` (ADR 0011); other types show `not_classifiable`.

### Value correction may create a fixed set of keys

This amends [#45](https://github.com/mooxphp/e-billing/issues/45)'s "a correction never adds keys". The parser leaves some JSON sub-keys out when the source has nothing to read (contact details, the consignee, payment means detail), and the reviewer must be able to supply them. `CorrectFieldValueAction` may therefore create the keys in `CREATABLE_KEYS` only: seller/buyer contact name/phone/email, delivery name and address parts, `payment_means` code and the first bank account's `iban` / `bic` / `bank_name`, and the first preceding invoice's number/date. The recorded parsed value is `null`. Any other unknown key is still refused. JSON sub-keys of one column are written together, and a value a JSON value object would drop is refused rather than silently lost.

### One catalogue for cross-checked fields

The "cross-checked set" of this ADR is `e-billing.cross_checked`: `fields` maps a field to the matched record (`customer` or `company`) and either an `attribute` or its `addresses` in the buyer or delivery corroboration roles; `strict` (default `false`) switches from datalist suggestions plus free text to select-only. `CrossCheckedFields` scopes options to the attributed customer; addresses use an "Address from master data" picker that fills the parts. Values not in the attributed customer's master data are flagged in edit mode, never cleared; review never writes master data. The divergence report ([#46](https://github.com/mooxphp/e-billing/issues/46)) is meant to derive from the same mapping so there is one list, not two.

### Delivery recipient is a reviewer-set document value

The delivery recipient is `buyer.contact.email` (BT-58), which the parser never fills, so a value there is always the reviewer's. `EbillingDocument::recipientEmail()` returns it, else the inbox To address.

- Mail-sourced documents: setting it is a value correction (`SetRecipientEmailAction`).
- Other sources (manual upload): there is no mail to have read it from, so it is not a parser mistake. The action writes the value and logs an audited `recipient_set` activity, which keeps it out of parser-feedback data.
- `ConfigurableDeliveryRecipientResolver` gains the strategy `document` (only the document's address); `inbox_to` prefers the document's address when set. `InvoiceFieldValidator` treats a set document recipient as present.

### Interim leave-edit and hidden approval while editing

Until [#48](https://github.com/mooxphp/e-billing/issues/48) lands, confirmed leave-edit runs `RematchAttributionAction` synchronously (manual attribution preserved) and nothing else; #48 extends it to regenerate and revalidate and adds the stale-artifact approval block. Per-field saves never re-match.

Confirm and approve actions are **hidden while editing**; the reviewer leaves edit mode first, so an approval never skips the re-check that leave-edit runs. Edit mode exists only while the approval status is `pending` and moox/audit auditing is available.
