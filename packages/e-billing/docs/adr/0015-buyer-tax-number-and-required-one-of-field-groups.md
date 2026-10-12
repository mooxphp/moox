---
status: accepted
date: 2026-10-06
---

# Buyer tax number, and "required one of" field groups

## Context

A source document can print the buyer's national tax number (in Germany the Steuernummer) instead of, or beside, the buyer's VAT identification number. The pipeline only knew the VAT id: the buyer party's `tax_number` was always null, so a document printing only a tax number looked as if it carried no buyer tax identifier at all.

EN 16931 has no business term for a buyer tax number. BG-7 (Buyer) has only BT-48, the Buyer VAT identifier. In the CII syntax, however, the XSD allows `SpecifiedTaxRegistration` on any `TradeParty` in every profile, including EN16931. The KoSIT validator 1.6.3 and the CEN EN16931 CII schematron ignore a buyer registration with `schemeID="FC"`; no rule tests it. A receiver may therefore ignore or drop it, while the PDF still shows it.

No German provision requires the buyer's Steuernummer. The buyer's USt-IdNr. is required only in the § 14a UStG cases (intra-EU supply, reverse charge, triangular transaction). So "the buyer needs a VAT id or a tax number" is a business rule an integrator may want, not a legal default, and the existing per-field MoSCoW priorities cannot express "either one will do": each field would be flagged on its own even though the other is present.

## Decision

**Buyer tax number.** `Moox\EBilling\Data\Invoice` gets `customerTaxNumber` (`bill_data.customer_tax_number`). `ParsedInvoiceMapper` maps it to the buyer party's `tax_number`. Review edits it as `buyer.tax_number` (`ReviewFieldCatalog`); the invoice view shows it in the buyer section after the VAT id. Package defaults: priority `could` in the invoice, credit note and corrected invoice field maps, and a cross-check against the company attribute `tax_number`.

**Emission.** The `ZugferdInvoice` contract gets `customerTaxNumber`. `ZugferdConverter` writes the buyer VAT id as `SpecifiedTaxRegistration schemeID="VA"` (BT-48) as before. Only when there is no VAT id does it write the tax number as buyer `schemeID="FC"`. The value is kept as the source prints it.

**Required one of.** New config key `field_validation.{profile}_fields_required_one_of`: a list of groups, e.g. `[['customer_vat_id', 'customer_tax_number']]`. `FieldValidationProfile::invoiceFieldsRequiredOneOf()` reads it; profiles fall back to `invoice_fields_required_one_of` (package default `[]`) unless they define their own, which may be empty. In `InvoiceFieldValidator::fillFieldValidations`, after the per-field validation:

- If at least one member of a group has a value, every empty member becomes `['status' => 'not_applicable', 'source' => 'one_of']` with the hint "Not needed: an equivalent field is present."
- If no member has a value, each member keeps the finding its own priority gives it (for example `missing` for `must`).
- Groups with fewer than two fields are ignored. The mechanism applies to header fields only.

The package default is empty. The rule is configured by the host.

## Considered options

- **Carry the tax number in a BT-22 note.** Rejected: a note is free text for a human; it is no tax identifier and no receiver reads it as one.
- **Do not emit the tax number.** Rejected: the value would reach the PDF but not the XML, and a receiver working from the XML would see a buyer without any tax identifier. Emitting `FC` is schema-valid and costs nothing when ignored.
- **Make "VAT id or tax number" a package default.** Rejected: no law demands it (see Context), so a default would block documents for a reason the package cannot justify. It is configuration.
- **Express either/or through the MoSCoW priorities alone.** Rejected: a priority judges a field in isolation, so a document with one of the two would still be flagged for the other.

## Consequences

- `ZugferdInvoice` gets a new property: custom implementers must add `customerTaxNumber`.
- An installation that sets no group sees no change in validation; an empty tax number stays a `could` finding.
- A receiver may drop the buyer `FC` registration. The PDF is the reliable carrier of the tax number.
- A group member satisfied by a sibling is stored as `not_applicable` with `source: one_of`, so it no longer counts as a missing field.
