# Changelog

## Unreleased

### Added
- Nullable `deliveries` (json) on invoice lines for partial deliveries: list of `{date (Y-m-d), delivery_note, quantity}`. `InvoiceLineDraft::$deliveries` (default `[]`), persisted by `InvoiceBuilder` (`null` when empty), cast as array on the model. Migration stub `add_deliveries_to_invoice_lines_table`; hosts must publish/run it.
- Audit registration with moox/audit: new `invoice.audit` config block (`enabled`, `models`; log name `invoice`), registered in `InvoiceServiceProvider::packageBooted()` when moox/audit is installed. Invoice lines and allowances/charges are now audited too; configured model subclasses (`models.*`) are audited in place of the package models. moox/audit is a composer `suggest`.
- Nullable indexed `supplier_number` on invoices (EN 16931 BT-29 seller identifier). Flows through `InvoiceDraft` / `InvoiceBuilder` / model / create-table stub; migration stub `add_supplier_number_to_invoices_table` (hosts must publish/run).
- Nullable `preceding_invoices` (json) on invoices for the EN 16931 preceding invoice reference (BG-3: list of `{number, date}`, BT-25/BT-26). `InvoiceDraft::$preceding_invoices` (default `[]`), persisted by `InvoiceBuilder`, cast as array on the model. Migration stub `add_preceding_invoices_to_invoices_table`; hosts must publish/run it.
- Nullable `vat_category` on invoices (`InvoiceDraft` / `InvoiceBuilder` / model / create-table stub) for the EN 16931 VAT category stamp (BT-118).

### Changed
- Default `invoice.audit` attributes for `Invoice` now also include `supplier_number`, `notes`, `preceding_invoices` and `vat_category`, so consumers such as moox/e-billing can correct them. Invoices created before this change have no parsed value recorded for these attributes.
- The Invoice audit registration moved here from moox/e-billing; its entries use log name `invoice` instead of `e-billing`.

- Invoice and line `delivery` is now a consignee **party** (name + address) via `DeliveryPartyCast`, matching `buyer` / `seller`. Stored JSON is `{name, address}`; VAT identifier, tax number, and contact are never persisted on delivery. A data migration wraps existing address-shaped JSON and strips leftover VAT / tax / contact from already party-shaped rows ([#8](https://github.com/mooxphp/invoice/issues/8)).
- A stored consignee may lack `country_code`. BR-57 is an emission rule (enforced when BG-15 is written), not a storage rule: `DeliveryPartyCast` keeps an address as read via `Address::fromDocumentArray()` ([#8](https://github.com/mooxphp/invoice/issues/8)).
- Renamed invoice field `pricing_basis` to `delivery_terms` (model, draft, builder, factory, create-table stub) ([#9](https://github.com/mooxphp/invoice/issues/9)).

### Added

- Document versioning on invoices: `document_version` (int) and `is_current` (bool), with auto-assignment on create for the same `invoice_number` + `document_type` family. `makeCurrentVersion()` flips current without deleting older rows.
- `Address::fromDocumentArray()`, `Address::hasCountry()`, and `Address::isEmpty()` for an address as read. `fromArray()` still requires a country (ready-to-emit / BR-57) and delegates field mapping to `fromDocumentArray()` ([#8](https://github.com/mooxphp/invoice/issues/8)).
- Payment terms (EN 16931 BT-20) and shipping method on the persisted invoice: nullable `payment_terms` (text) and `shipping_method` (string) flow through `InvoiceDraft` / `InvoiceBuilder` onto the model; create-table stub and host migrations add the columns ([#6](https://github.com/mooxphp/invoice/issues/6)).

