# Changelog

## Unreleased

### Changed

- Renamed invoice field `pricing_basis` to `delivery_terms` (model, draft, builder, factory, create-table stub) ([#9](https://github.com/mooxphp/invoice/issues/9)).

### Added

- Payment terms (EN 16931 BT-20) and shipping method on the persisted invoice: nullable `payment_terms` (text) and `shipping_method` (string) flow through `InvoiceDraft` / `InvoiceBuilder` onto the model; create-table stub and host migrations add the columns ([#6](https://github.com/mooxphp/invoice/issues/6)).
