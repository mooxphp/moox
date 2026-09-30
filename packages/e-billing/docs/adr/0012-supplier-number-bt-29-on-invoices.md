---
status: accepted
date: 2026-09-28
---

# Persist and emit supplier number as EN 16931 BT-29 on `invoices.supplier_number`

## Context

Parsers already put the printed Lieferanten-Nr. on the e-billing DTO as `supplierNumber` / `bill_data.supplier_number` (letterhead also keys off it). That value never reached the persisted Invoice, ViewInvoice, or CII emission — `ParsedInvoiceMapper` / `InvoiceFactory` dropped it, and `ZugferdConverter` never called `addDocumentSellerId`.

We needed one clear meaning and one persistence shape so review and XRechnung/ZUGFeRD/Factur-X stay aligned.

## Decision

1. **Meaning.** **Supplier number** is EN 16931 **BT-29** (seller identifier). Distinct from customer number (BT-46), customer reference (BT-10), and purchasing `Supplier.supplier_number` (Kreditorennummer).
2. **Persist.** Nullable indexed `invoices.supplier_number`, filled from the DTO through `InvoiceDraft` / `InvoiceBuilder` (same path as `customer_number`: empty string → null, otherwise **verbatim**, including interior whitespace). `bill_data` remains the parse snapshot via `Data\Invoice::toArray()`. Emission trims only to decide whether to omit BT-29.
3. **Emit.** When non-empty (after trim), `ZugferdConverter` calls `addDocumentSellerId` (no scheme). Empty/null omits BT-29. Shared converter → XRechnung, ZUGFeRD, and Factur-X.
4. **MoSCoW.** Priority **`could`** on invoice, credit-note, and corrected-invoice field maps — missing never blocks.
5. **UI.** Show in the ViewInvoice supplier (BG-4) group for invoices and credit notes; not denylisted.
6. **No corroboration** against master data. **No SQL backfill.** Amended by [ADR 0013](0013-invoice-is-the-aggregate-root-e-billing-owns-the-sealed-record.md) (mapped once, no re-parse): no re-map either, so existing invoices keep an empty column; only cases mapped after this change get it. ViewInvoice reads the column only.

## Considered options

- **bill_data only** — rejected: model adapter / ViewInvoice would stay blind; dual source of truth.
- **Seller `Party` identifier list** — rejected for v1: one optional string; YAGNI for multi-ID cardinality.
- **MoSCoW must/should** — rejected for package default: BT-29 is optional in EN 16931.
- **ISO 6523 scheme on BT-29** — rejected: the PDF has no scheme; buyer and seller already share the meaning.

## Consequences

- `ZugferdInvoice` gains `?string $supplierNumber` (**breaking** for custom contract implementors).
- Hosts that override `field_validation` maps must add `'supplier_number' => 'could'` themselves.
- Hosts that redeclare Invoice `$fillable` must list `supplier_number` for mass-assign/factory paths; `InvoiceBuilder` assigns the property directly either way.
- Value correction may later target this field key; this ADR does not ship correction UI.
