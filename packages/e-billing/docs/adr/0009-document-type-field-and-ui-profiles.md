---
status: accepted
date: 2026-09-25
---

# MoSCoW and ViewInvoice denylist profiles are selected by document type via parallel keys

## Context

`InvoiceFieldValidator` and ViewInvoice chrome today read a single pair of maps: `field_validation.invoice_*` (MoSCoW) and `invoice_ui.invoice_*_hidden` (denylist, ADR 0007). Credit notes reuse the same `Invoice` entity and pipeline with `document_type = 381` (host ADR pattern / EN 16931 UNTDID 1001), so they currently inherit invoice severities and denylist even when product rules will diverge.

German vs foreign commercial invoices will need different MoSCoW maps later. That axis is not designed yet (selection is not simply another type code). The immediate need is a second profile for credit notes without painting the future German/foreign keying into the config tree.

## Decision

1. **Selector = `document_type` (BT-3).** `381` loads the credit-note maps; `380` (and any other allowed type until further ADRs) loads the invoice maps. Do not key off Filament resource names (pipeline has no resource) or stamp a separate profile name on the document until one type code must map to multiple profiles.

2. **Parallel sibling keys, not nested profiles and not a delta overlay.**
   - MoSCoW: keep `invoice_fields` / `invoice_line_fields` / contextual lists; add `credit_note_fields` / `credit_note_line_fields` / matching contextual keys under `field_validation`.
   - ViewInvoice denylist: keep `invoice_fields_hidden` / `invoice_line_fields_hidden`; add `credit_note_fields_hidden` / `credit_note_line_fields_hidden` under `invoice_ui`.
   - **`invoice_ui.field_groups` stays shared** across types (collapse chrome is not type-specific in v1).

3. **Ownership.** `moox/e-billing` owns the type→key switch (`InvoiceFieldValidator` and the ViewInvoice presentation helper). Hosts own the priority and denylist *values*.

4. **v1 seed and missing-key behaviour.** Package defaults ship `credit_note_*` as a **clone** of the corresponding `invoice_*` maps (identical severities / denylist). If a host omits `credit_note_*`, fall back to `invoice_*` so existing installs do not break. Business tuning of credit-note severities is a later, config-only change. German/foreign invoice profiles remain deferred.

## Considered options

- **Nested `profiles.{380|381}` tree.** Rejected for v1: forces a rename of the live invoice maps and invents a matrix before German/foreign selection criteria exist.
- **Delta overlay (invoice map + sparse credit-note overrides).** Rejected: hides intentional absences and is harder to review in host diffs.
- **Filament resource key as selector.** Rejected: mail and upload pipelines validate without a resource.
- **Host-only validator wrapper.** Rejected: selection is generic package behaviour; hosts only supply values.
- **Fail closed when `credit_note_*` missing.** Rejected: too sharp for introducing the switch; fallback preserves today’s behaviour.
- **Duplicate `credit_note_field_groups`.** Rejected for v1: no known divergence; shared `field_groups` is enough.

## Consequences

- ADR 0007’s boundary still holds: denylist stays under `invoice_ui`, never inside MoSCoW entries. This ADR only adds type-scoped sibling denylist keys.
- Adding further type codes with the same parallel-key pattern is possible; a second profile for the *same* type code (e.g. German vs foreign `380`) needs a new decision — do not stretch `document_type` alone for that.
- EN 16931 / XRechnung do not require BG-3 / BT-25 (preceding invoice) for type 381; v1 does not add a preceding-invoice MoSCoW key solely because credit notes exist.

## Addendum (2026-09-25): preceding invoice reference

The last consequence above is superseded. Integrators whose credit notes print the credited invoice now have a real reason for a key, and `moox/invoice` models BG-3 (BT-25 number, BT-26 date).

- `credit_note_fields` gets `preceding_invoice_number` and `preceding_invoice_date`, both **`could`** in the package default. Hosts that always print the reference raise them in their own config. The invoice profile does not list them.
- `invoice_ui.invoice_fields_hidden` hides both fields, and the credit-note profile shows them.
- ViewInvoice tries to find the referenced invoice among stored documents. Matching is on the number with separators ignored, so `30641.25` equals `3064125`. A match is shown as a link. No match, or a date that differs from BT-26, is a **warning** and never blocks: invoices older than the system will never be found.

## Addendum 2 (2026-09-25): corrected invoice (384) and the type → profile map

A corrected invoice (384) has different rules from a credit note: it must name the corrected invoice by number and date, so it gets its own profile rather than sharing `credit_note_*` (ADR 0011 for how a document becomes 384).

- The hard-wired `381 → credit_note_*` switch becomes a config map, `field_validation.document_type_profiles` (`'381' => 'credit_note'`, `'384' => 'corrected_invoice'`). A mapped type reads `{prefix}_fields`, `{prefix}_line_fields`, the contextual lists and `invoice_ui.{prefix}_*_hidden`; a missing key still falls back to `invoice_*`, and unmapped types still read `invoice_*`. This is a flat map to sibling keys, not the nested `profiles` tree rejected above.
- Package default `corrected_invoice_fields`: the invoice priorities with `preceding_invoice_number` / `preceding_invoice_date` as `should` (XRechnung recommends BG-3 for 384) and payment terms / due date as `could`: a reducing correction has a negative amount due, and an increasing one without BT-9 or BT-20 is rejected by BR-CO-25 at artifact validation.
- The review-queue query splits by every mapped type whose priorities differ from the invoice priorities.
- The preceding-invoice lookup searches only documents of unmapped types, i.e. the invoices a credit note or correction can refer to.
