# Changelog

## Unreleased

### Added

- Document-level delivery date (EN 16931 BT-72): `delivery_date` on the persisted invoice flows from the parser DTO through `ParsedInvoiceMapper` / `InvoiceFactory`; shown in the delivery section of the detail view and validated as a contextual `should` field (present ⇒ `parsed`, absent ⇒ `missing`). Line-level delivery dates are unchanged ([#12](https://github.com/mooxphp/invoice/issues/12), part of [#27](https://github.com/heco-gmbh/billing/issues/27)).
- Persisted line unit code (EN 16931 BT-130): `unit_code` flows from the parser DTO through `ParsedInvoiceMapper` onto `invoice_lines`; `ZugferdInvoiceLineAdapter` emits the stored code (with piece-code normalization) instead of re-resolving from the label. Configurable `preferred_piece_unit_code` (default `H87`) and `piece_unit_codes` replace the German-label override that mapped Stück to `C62` ([#55](https://github.com/mooxphp/e-billing/issues/55)).
- Manual attribution and explicit rematch ([#27](https://github.com/mooxphp/e-billing/issues/27)): nullable indexed `attribution_source` (`auto`|`manual`) on `ebilling_documents`; `SetInvoiceAttributionAction` and `RematchAttributionAction`; Filament `set_attribution` / `rematch` on the invoice detail (rematch also on the list). Rematch resets `review_status` to `parser_created` and re-runs `InvoiceFieldValidator::validate()`. Manual attributions are never overwritten; the automatic path still refuses confirmed/validated documents. Changing attribution after `HumanConfirmed`/`Validated` clears attestation back to `DbValidated`. No scheduled rematch.
- Attribution corroboration via internal `Support\AttributionCorroborator`: after a customer match, name (significant token overlap ≥ `corroboration.name_min_token_length`, legal-form stop words ignored), VAT, country, and buyer address (existence among company billing addresses via `AddressFingerprint` and `corroboration.address_roles`) are checked against master data. Divergences only flag `needs_review`; corroboration never clears or rewrites attribution. Package now requires `moox/address`. Config keys under `e-billing.corroboration` ([#25](https://github.com/mooxphp/e-billing/issues/25)).
- Customer attribution on `EbillingDocument`: nullable `customer_id` FK → `customers` (`nullOnDelete`), `customer()` BelongsTo, and internal `Support\CustomerMatcher` (normalises buyer identifier; looks up `Customer::withTrashed()` by `customer_number`; derives `company_id` from exactly one Company morph assignment). Package now requires `moox/customer` ([#24](https://github.com/mooxphp/e-billing/issues/24)).
- Buyer identifier (`customer_number`, EN 16931 BT-46) now flows from the parser DTO onto the persisted invoice via `ParsedInvoiceMapper` / `InvoiceFactory`. Empty DTO values become `null`; non-empty values are stored unchanged. The field is validated from the invoice like any other field (present ⇒ `parsed`, absent ⇒ configured MoSCoW priority) and is no longer listed under `INVOICE_FIELDS_WITHOUT_PERSISTED_SOURCE` ([#23](https://github.com/mooxphp/e-billing/issues/23)).
- Three-format registry: XRechnung (pure CII XML), ZUGFeRD (hybrid PDF), Factur-X (hybrid PDF). All share one CII generator; XRechnung uses `XRECHNUNG` profile, hybrids use `EN16931`.
- Per-customer format resolution via `EbillingFormatResolver`: reads `customers.data.preferred_ebilling_format` from the attributed customer, falls back to `default_format` config (default `zugferd`). Format is frozen on the document at generation time; preference changes affect only future documents ([#53](https://github.com/mooxphp/e-billing/issues/53)).
- XRechnung documents are validated by KOSIT only (no PDF, veraPDF not invoked) and reach `Validated` on pass.
- Hybrid artifact validation runs veraPDF PDF/A-3 checks in `ValidateArtifactJob` when `moox/verapdf` is installed; verdicts persist via `veraPdfValidations()` alongside KoSIT results. When veraPDF is not configured, hybrid validation falls back to KOSIT-only (degraded mode).

### Changed

- `InvoiceFieldValidator` wires attribution corroboration when a customer was matched: name/VAT/country/address field statuses reflect master-data agreement or `needs_review` on divergence; VAT and country compare only when both sides are present; address/country corroboration runs only after a customer match. Name-fallback (no customer match) keeps exact company name/VAT behaviour. Fields without a persisted source (`payment_terms`, `shipping_method`) return `not_applicable`. `parsed` is included in clean validation statuses so auto-Validated remains reachable ([#25](https://github.com/mooxphp/e-billing/issues/25)).
- `CustomerMatcher::isReviewableMatch` also returns true (⇒ `needs_review` on `customer_number`) when the derived `company_id` is null (no company or multi-company assignment). Soft-deleted/inactive customers remain reviewable; attribution is still kept ([#25](https://github.com/mooxphp/e-billing/issues/25)).
- `InvoiceFieldValidator` attributes documents by buyer identifier first: unique `CustomerMatcher` hit sets `customer_id` and derived `company_id` (`db_validated` when active; soft-deleted/inactive still attributed with `customer_number` = `needs_review`). No match or missing identifier leaves `customer_id` null; name fallback via `CompanyNameMatcher` may set `company_id` only. `customer_id` is the identity / visibility gate; `company_id` is reporting-only. Name/VAT/address corroboration followed in [#25](https://github.com/mooxphp/e-billing/issues/25) ([#24](https://github.com/mooxphp/e-billing/issues/24)).
- Format resolution reads `customers.data.preferred_ebilling_format` from the attributed customer (`customer_id` or live `CustomerMatcher` on invoice `customer_number`), not from company. A customer without a company assignment still receives its preferred format; company-only name matches no longer influence format ([#53](https://github.com/mooxphp/e-billing/issues/53)).
- Company name matching for field validation is unified in `CompanyNameMatcher`; matches against the persisted invoice buyer name (whitespace-collapsed, case-insensitive exact match, unique hit only). No longer filters on removed `company_type` / `is_active` columns. `EBillingFormatResolver` no longer reads `bill_data['customer_name']` ([#22](https://github.com/mooxphp/e-billing/issues/22)).
- Reduced cyclomatic/NPath complexity in `ValidateArtifactJob::handle` (named stages: resolve document/inputs, run validations, persist success/failure), `UnitCodeResolver::lookupMaps`, and `DocumentTypeCodeResolver::resolveLabel`. Behaviour unchanged.
- Deduplicated KOSIT/veraPDF validation persistence in `ValidateArtifactJob` via `ArtifactValidationPersister`; supplemental verdicts (veraPDF) stay as closures so the shared seam does not type-hint optional validator packages. No behaviour change.
- Replaced `GenerateXmlJob` / `ValidateXmlJob` / `MergeZugferdPdfJob` with `GenerateArtifactJob` → `ValidateArtifactJob` (generate-first pipeline).
- `gateway_status` enum is now format-agnostic: `generating`, `generation_failed`, `validating`, `validated`, `validation_failed`, `validator_error`, `ignored_foreign`.
- Hybrid ZUGFeRD artifacts are built before validation; deliverable PDFs are unencrypted; `artifact_content_hash` is populated on validation pass.
- Pipeline events renamed to `ArtifactGenerated`, `ArtifactValidated`, `ArtifactValidationFailed`.
- Filament invoice list: `gateway_status` badge column, gateway failure/processing tabs, and gateway status filter.
- Artifact downloads (ZUGFeRD PDF / XML) are gated on `gateway_status = validated` and a stored `artifact_content_hash`.

### Fixed

- `UnitCodeResolver` and `DocumentTypeCodeResolver` no longer select translated `common_name` from the parent static tables (column lives on `*_translations` after the Astrotomic move). They eager-load translations and index all locale labels instead.
- Declare `"php": "^8.4"` in `composer.json`. Zugferd adapters (and `moox/zugferd` contracts) use PHP 8.4 property hooks; without an explicit constraint the package inherited `moox/core`'s `^8.2|^8.3|^8.4` and advertised runtimes that fatal-parse on load ([#11](https://github.com/mooxphp/e-billing/issues/11)).
- SonarQube line-length (120 cols) and brace-placement findings in the
  generate-then-validate pipeline files, `InvoiceResource`,
  `InvoiceFactory`/`ParsedInvoiceMapper`, `EbillingDocument`, and related
  tests/fixtures. Long lines were wrapped or extracted into named locals;
  empty-body classes (`ContainerTestCase`, `UnknownFormatException`) put
  the opening brace on its own line.
- Pint cannot enforce max line length (PHP-CS-Fixer has no such fixer), so
  120-col wraps stay manual. Empty-class brace style is enforced via
  `single_line_empty_body: false` in root `pint.json`.
