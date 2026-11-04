---
status: accepted
date: 2026-10-29
---

# Recipient preferences port and contact attribution

## Context

ADR 0020 made buyer matching swappable but left the rest of the master-data coupling in place (mooxphp/e-billing#120): the recipient format preference and the visual-copy flag were read from `Customer` columns by `CustomerFormatPreferenceResolver` and `CustomerMatcher`, `ebilling_documents.company_id` pointed at a Company model, the `master` delivery strategy read the attributed company's e-mail, and manual attribution and the review cross-checks still queried `Company` and `Customer`. A host whose master data has no Company model, or whose `customers` table has a different shape, could not use these parts.

## Decision

- **Recipient preferences are a port.** `Contracts\RecipientPreferenceResolver::resolve(EbillingDocument): RecipientPreferences`. The DTO `Data\RecipientPreferences` carries `?string $format`, `?string $profile` and `?bool $sendVisualCopy`.
  - `null` means no preference: `e-billing.default.format` / `e-billing.send_visual_copy` apply. A profile without a format is ignored.
  - Default binding is `Support\NullRecipientPreferenceResolver` (always empty preferences), bound only when the host has not bound the interface.
  - Format failure semantics are unchanged from ADR 0020: a throwing port becomes `FormatPreferenceUnavailableException`, the job releases with backoff, and the last attempt generates the default and holds the document (`format_preference_unavailable`). `EBillingFormatResolver::resolveSendVisualCopy()` reads `sendVisualCopy` from the port; a throwing port is logged and the config default is used.
  - Removed: `RecipientFormatPreferenceResolverInterface`, `Data\FormatPreference`, `Support\CustomerFormatPreferenceResolver`, `Support\CustomerMatcher`.
- **Attribution is a morph pivot with history.** `ebilling_documents.company_id`, `customer_id`, `contact_id` and `attribution_source` are removed. The new table `ebilling_attributions` (stub `create_ebilling_attributions_table`, registered in `EBillingServiceProvider::hasMigrations`) holds one row per attribution: `id`, `ebilling_document_id` (uuid FK, cascade delete), `role` (`buyer` or `buyer_contact`, enum `Enums\AttributionRole`), `party_type` + `party_id` (strings, a morph to any model), `attribution_source` (`auto` or `manual`, nullable), `ended_at` (nullable), timestamps. Indexes: (`ebilling_document_id`, `role`, `ended_at`) and (`party_type`, `party_id`, `role`). `Models\EbillingAttribution` has `document()`, `party()` (morphTo), `partyReference()`, the scope `active()` and the read-only labels `role_label`, `party_label`, `attribution_source_label`. `EbillingDocument::company()`, `EbillingDocument::customer()` and the `moox/company` requirement are removed.
- **History: at most one active row per document and role.** An active row has no `ended_at`. `Actions\RecordAttributionAction::execute(document, role, ?PartyReference, ?AttributionSource): bool` is the only writer. The same party with the same source is a no-op; otherwise it ends the active row and inserts a new one; a null party only ends the active row. It returns whether anything changed.
- **Reading attributions.** `EbillingDocument` gets `attributions()` (HasMany, ended rows included), `activeAttribution(AttributionRole)`, `activeParty(AttributionRole): ?PartyReference` and the static `whereAttributedTo(Builder, role, partyType, list partyIds)` to filter document queries by active attribution. The DTO `Data\PartyReference` (`string $type`, `string $id`, `of(Model)`, `equals()`) names a party.
- **Sharper resolver contract.** `BuyerMasterData` carries `party: PartyReference` (replaces `reference`) and an optional `contact: ?PartyReference`. `BuyerLookup::$reference` is a `?PartyReference`, and `BuyerLookup::byReference(PartyReference)` looks up a known party. `matched_id` in field validations is the party id. `BuyerMasterDataLookup` treats a match whose party or contact does not exist as `unavailable` and logs an error, so a bad resolver never fails generation. The type is resolved through the morph map or as a class name and must be an Eloquent model; a soft-deleted row counts as existing.
- **Field validation records attributions.** On `matched` (non-manual) it records `buyer` = `party` and `buyer_contact` = `contact` (a null contact ends the active one) with source `auto`. A manual buyer attribution (active `buyer` row with source `manual`) is resolved by its party and survives rematch.
- **Manual attribution works again.** `SetInvoiceAttributionAction` records the chosen customer as `buyer` and the customer's `contact` relation as `buyer_contact`, both `manual`; clearing ends both. It sets `review_changed_at` only when an attribution changed.
- **No `master` delivery strategy.** `ConfigurableDeliveryRecipientResolver` keeps `inbox_to`, `document` and `none`; an unknown or `master` value yields no recipients. Hosts that need master-data recipients bind their own `DeliveryRecipientResolverInterface`.
- **Cross-checks read the buyer.** `e-billing.cross_checked.fields` reads only what `BuyerMasterDataResolver` returns for the attributed customer (looked up by the active `buyer` party through `CrossCheckedFields::buyerFor()` and `BuyerLookup::byReference`), the same data the attribution was corroborated against; the `record` key (`customer` / `company`) is dropped. `CrossCheckedFields::attributedCustomer(?EbillingDocument)` returns the active buyer when it is a Customer (used for the review customer label and the change-customer select). Attributes: `customer_number`, `name` (plus alternative names), `vat_id`. Addresses: `buyer` is the billing addresses, `delivery` is the delivery then billing addresses. The `customer_tax_number` mapping is dropped (no buyer source). The review workspace looks the buyer up once and passes it to `CrossCheckedFields`, so a render costs one resolver call.
- **Admin: read-only attributions tab.** The invoice view shows a tab "Attributions" ("Zuordnungen") through `e-billing.invoice_relations.attributions` (has_many tab, columns `role_label`, `party_label`, `attribution_source_label`, `created_at`, `ended_at`; no actions) and the invoice relation `attributions` (hasManyThrough the document).
- **Removed config:** `e-billing.corroboration.buyer_address_roles` and `delivery_address_roles`. Which host address roles count as billing or delivery is the resolver's job.

## Considered options

- **Put the preferences into `BuyerMasterData`.** Rejected: the format would then depend on a buyer match, and ADR 0020 keeps generation independent of it.
- **Keep the `master` delivery strategy and read the e-mail from `BuyerMasterData`.** Rejected: delivery would depend on a buyer match; hosts that want it have the recipient port.
- **`customer_id` as unsigned big integer with a foreign key.** Rejected: it ties the seam to one table.
- **Keep `company_id`.** Rejected: a Company no longer exists in the host's master-data model.
- **Overwrite the attribution instead of keeping history.** Rejected: with history a re-attribution stays reconstructable (audit and GoBD traceability).
- **Other table names** (`ebilling_document_parties`, `ebilling_document_assignments`, `ebilling_partyables`). Rejected: the package already calls this "attribution", and "parties" would be confused with the invoice parties (buyer, seller, ship-to) printed on the invoice.

The morph pivot is the Moox pattern (`kosit_validatables`, `product_assignments`) and keeps e-billing independent of a fixed customers table.

## Consequences

- **Breaking for hosts:** the interface and DTO rename, the removed classes, the removed config keys, the removed `master` strategy, the removed columns, the removed `EbillingDocument::customer()`, the `BuyerMasterData` / `BuyerLookup` signature change and the dropped `moox/company` requirement. The host runs `create_ebilling_attributions_table`, moves existing customer, contact and source values into `ebilling_attributions` rows, and only then drops the old columns.
- The audit attribute list no longer contains the removed columns.
- With the null resolver no preferences are read: every document uses the configured defaults.
- Part B (where recipient preferences are stored, and the portal settings section) is decided in [ADR 0022](0022-recipient-settings-table.md). It replaces the null default binding stated above with `SettingsRecipientPreferenceResolver`, which reads the settings of the active `buyer_contact` party.
- Glossary in this package's `CONTEXT.md`: *Format choice*, *Format binding*, *Attribution*, *Document identity*.
