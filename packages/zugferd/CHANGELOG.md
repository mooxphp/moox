# Changelog

## Unreleased

### Added

- Optional `deliveryDate` on `ZugferdInvoice` and `ZugferdInvoiceLine`. When set on the invoice, `ZugferdConverter` emits BT-72 (actual delivery date) via `setDocumentSupplyChainEvent`. When set on a line, non-EXTENDED profiles emit a line billing period with start and end equal to that date (`setDocumentPositionBillingPeriod`); EXTENDED emits line actual delivery (`setDocumentPositionSupplyChainEvent`). The `convert($invoice, $profileKey)` profile key selects the line-date carrier. The converter never derives a header invoicing period (BG-14) from delivery dates.
