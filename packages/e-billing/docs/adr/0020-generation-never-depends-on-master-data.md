---
status: accepted
date: 2026-10-28
---

# Generation never depends on master data

## Context

Field validation runs inside `GenerateArtifactJob`, before the XML is generated. It queried the Moox `Company` and `Customer` models directly: customer-number and name matching, then corroboration of name, VAT id, country and addresses. When a host changed its master data (for example the companies table was dropped), the queries threw and every document ended in `generation_failed`, although the XML needs nothing from master data (mooxphp/e-billing#119).

## Decision

- **Only two things may block generation:** missing `bill_data`, and a format/profile that cannot be resolved (there is a default). Master-data matching, the uploaded recipient email and the recipient format preference degrade to review hints.
- **Contract.** `BuyerMasterDataResolver::resolve(BuyerLookup): BuyerMasterDataResult`. The result status is `matched`, `no_match`, `ambiguous` or `unavailable`; `ambiguous` is treated like `no_match` for now.
  - The resolver **finds** the buyer: by `BuyerLookup::$reference` for a manual attribution, otherwise by customer number first, then by name.
  - The package **corroborates** name, VAT id, country and addresses against the returned `BuyerMasterData` with its existing rules (token and short-name rules, postal fingerprint, city and additional-line divergence as information, ADR 0019).
  - Billing addresses corroborate the buyer address and country. A consignee is matched against delivery addresses first, then billing addresses; an own site (a consignee with the buyer's name) only against delivery addresses. Which host address roles map to billing or delivery is the resolver's job.
- **A throwing resolver never fails the document.** `BuyerMasterDataLookup` catches the exception, logs it as an error and returns `unavailable`. There is no retry: `unavailable` only leads to review, and a later revalidation catches up.
- **Default binding** is `NullBuyerMasterDataResolver` (always `unavailable`). `e-billing.master_data.resolver` replaces it; a class that does not implement the contract makes the service provider throw `InvalidArgumentException`.
- **`e-billing.master_data.when_unavailable`:**
  - `review` (default): the non-empty `customer_number`, `customer_name`, `customer_address`, `customer_vat_id` and `country` become `needs_review` with reason `master_data_unavailable`. `customer_number`, `customer_name` and `customer_address` are must fields, so auto-approval is blocked.
  - `trust_parsed`: the same fields become `parsed`.
  - `delivery_address` is unchanged: a consignee was already information-only without a match.
  - There is deliberately no option to block generation.
- **Attribution.** On `matched`, the document's `customer_id` is set to the returned `reference` (`attribution_source` `auto`). On any other status an existing `customer_id` is left untouched, so attribution written by the host by other means is not wiped. Field validation no longer writes `company_id`. A manual attribution is looked up by its reference and reported with source `manual`.
- **A matched buyer puts `customer_number` into review** when its own customer number differs from the parsed one, or when `BuyerMasterData::$isReviewable` is set (for example deleted or inactive).
- **Format preference.** Format and profile freeze with the first generated artifact, so a failing lookup must never silently ship the default.
  - A preference port that throws becomes `FormatPreferenceUnavailableException`.
  - `GenerateArtifactJob` releases itself back to the queue with its backoff on earlier attempts.
  - On the last attempt it generates the configured default format (`EBillingFormatResolver::resolveDefault()`) and holds the document (`approval_flags.held.reason = format_preference_unavailable`), so it is never auto-approved or auto-sent. The invoice view shows a dedicated banner.
  - "No preference" (`null`) still means the default, as before.
- **Other lookups degrade too.** The uploaded recipient email step and the visual-copy customer lookup (`resolveSendVisualCopy`) log failures and continue with the default.
- **Removed from the generation path:** `CompanyNameMatcher` (deleted), `CustomerMatcher::isReviewableMatch` (deleted). `AttributionCorroborator` no longer touches `Company` or `Customer`; it works on `BuyerMasterData`.

## Considered options

- **Keep the direct Company/Customer queries and catch their errors.** Rejected: the package would still be coupled to one master-data shape, and a swallowed error looks like "no match".
- **Fail generation when master data is unavailable.** Rejected: the XML does not need it; review is the right place for the gap.
- **Retry an unavailable resolver.** Rejected: unavailable only leads to review, and revalidation catches up.
- **Silently use the default format when the preference lookup fails.** Rejected: format and profile freeze with the first artifact, so the recipient would keep a wrong format.

## Consequences

- Generation succeeds without any master data; the package works with the null resolver.
- Hosts that relied on the built-in Customer/Company matching must bind a resolver to keep corroboration. Until then documents go to review (`master_data_unavailable`) and are not auto-approved.
- Stored statuses change only on revalidation.
- Out of scope, tracked in mooxphp/e-billing#120: the `company_id` / `customer_id` columns stay; UI usages of `Customer` (`ViewInvoice`, `ReviewWorkspace`, `CrossCheckedFields` options, `SetInvoiceAttributionAction` including `CustomerMatcher::resolveCompanyId`) and `EbillingDocument::company()` (still used by the delivery recipient resolver's master-data mode) are untouched; `moox/company` and `moox/customer` stay in `composer require`; an optional Moox-based resolver is a follow-up.
- Glossary: *Buyer master-data resolver* and *Master data unavailable* in this package's `CONTEXT.md`.
