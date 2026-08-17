# Changelog

## Unreleased

### Changed

- Invoice and line `delivery` is now a consignee **party** (name + address) via `DeliveryPartyCast`, matching `buyer` / `seller`. Stored JSON is `{name, address}`; VAT identifier, tax number, and contact are never persisted on delivery. A data migration wraps existing address-shaped JSON and strips leftover VAT / tax / contact from already party-shaped rows ([#8](https://github.com/mooxphp/invoice/issues/8)).
- A stored consignee may lack `country_code`. BR-57 is an emission rule (enforced when BG-15 is written), not a storage rule: `DeliveryPartyCast` keeps an address as read via `Address::fromDocumentArray()` ([#8](https://github.com/mooxphp/invoice/issues/8)).
- Renamed invoice field `pricing_basis` to `delivery_terms` (model, draft, builder, factory, create-table stub) ([#9](https://github.com/mooxphp/invoice/issues/9)).

### Added

- `Address::fromDocumentArray()`, `Address::hasCountry()`, and `Address::isEmpty()` for an address as read. `fromArray()` still requires a country (ready-to-emit / BR-57) and delegates field mapping to `fromDocumentArray()` ([#8](https://github.com/mooxphp/invoice/issues/8)).
- Payment terms (EN 16931 BT-20) and shipping method on the persisted invoice: nullable `payment_terms` (text) and `shipping_method` (string) flow through `InvoiceDraft` / `InvoiceBuilder` onto the model; create-table stub and host migrations add the columns ([#6](https://github.com/mooxphp/invoice/issues/6)).

