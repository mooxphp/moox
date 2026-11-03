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
- **Attribution targets a contact.** `ebilling_documents.company_id` is replaced by `contact_id` (unsigned big integer, nullable, indexed, no foreign key): the attributed customer's contact, used for reporting and later for recipient preferences, never as an access boundary. `customer_id` becomes an unsigned big integer with a foreign key to `customers.id` (`nullOnDelete`); the model casts both to string. `EbillingDocument::company()` and the `moox/company` requirement are removed.
- **Sharper resolver contract.** `BuyerMasterData` gets an optional `?string $contactReference` (last constructor argument). `reference` must be the key of an existing `customers` row (digits); `contactReference` must be digits. On `matched`, field validation writes `customer_id = reference` and `contact_id = contactReference`. `BuyerMasterDataLookup` treats a match whose reference is not an existing customer (a soft-deleted customer counts as existing), or whose `contactReference` is not numeric, as `unavailable` and logs an error, so a bad resolver never fails generation.
- **Manual attribution works again.** `SetInvoiceAttributionAction` sets `customer_id`, `contact_id` (the customer's `contact_id`) and `attribution_source = manual`; clearing nulls all three.
- **No `master` delivery strategy.** `ConfigurableDeliveryRecipientResolver` keeps `inbox_to`, `document` and `none`; an unknown or `master` value yields no recipients. Hosts that need master-data recipients bind their own `DeliveryRecipientResolverInterface`.
- **Cross-checks read the buyer.** `e-billing.cross_checked.fields` reads only what `BuyerMasterDataResolver` returns for the attributed customer (looked up by reference = `customer_id`), the same data the attribution was corroborated against; the `record` key (`customer` / `company`) is dropped. Attributes: `customer_number`, `name` (plus alternative names), `vat_id`. Addresses: `buyer` is the billing addresses, `delivery` is the delivery then billing addresses. The `customer_tax_number` mapping is dropped (no buyer source). The review workspace looks the buyer up once and passes it to `CrossCheckedFields`, so a render costs one resolver call.
- **Removed config:** `e-billing.corroboration.buyer_address_roles` and `delivery_address_roles`. Which host address roles count as billing or delivery is the resolver's job.

## Considered options

- **Put the preferences into `BuyerMasterData`.** Rejected: the format would then depend on a buyer match, and ADR 0020 keeps generation independent of it.
- **Keep the `master` delivery strategy and read the e-mail from `BuyerMasterData`.** Rejected: delivery would depend on a buyer match; hosts that want it have the recipient port.
- **Keep `customer_id` a generic string without a foreign key.** Rejected in favour of integrity and the portal join; the resolver contract is sharpened instead.
- **Keep `company_id`.** Rejected: a Company no longer exists in the host's master-data model.

## Consequences

- **Breaking for hosts:** the interface and DTO rename, the removed classes, the removed config keys, the removed `master` strategy, the column change and the dropped `moox/company` requirement. The host migrates existing rows itself: numeric `company_id` values move to `contact_id`, non-numeric or orphaned `customer_id` values become `null`, then the column is converted and the foreign key added.
- With the null resolver no preferences are read: every document uses the configured defaults.
- Part B (where recipient preferences are stored, and the portal settings section) is decided in [ADR 0022](0022-recipient-settings-table.md). It replaces the null default binding stated above with `SettingsRecipientPreferenceResolver`.
- Glossary in this package's `CONTEXT.md`: *Format choice*, *Format binding*, *Document identity = Customer*.
