# Changelog

## Unreleased

### Added

- Payment terms (EN 16931 BT-20) and shipping method on the persisted invoice: nullable `payment_terms` (text) and `shipping_method` (string) flow through `InvoiceDraft` / `InvoiceBuilder` onto the model; create-table stub and host migrations add the columns ([#6](https://github.com/mooxphp/invoice/issues/6)).
