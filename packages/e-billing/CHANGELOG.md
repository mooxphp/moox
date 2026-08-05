# Changelog

## Unreleased

### Added

- Buyer identifier (`customer_number`, EN 16931 BT-46) now flows from the parser DTO onto the persisted invoice via `ParsedInvoiceMapper` / `InvoiceFactory`. Empty DTO values become `null`; non-empty values are stored unchanged. The field is validated from the invoice like any other field (present ⇒ `parsed`, absent ⇒ configured MoSCoW priority) and is no longer listed under `INVOICE_FIELDS_WITHOUT_PERSISTED_SOURCE` ([#23](https://github.com/mooxphp/e-billing/issues/23)).
- Three-format registry: XRechnung (pure CII XML), ZUGFeRD (hybrid PDF), Factur-X (hybrid PDF). All share one CII generator; XRechnung uses `XRECHNUNG` profile, hybrids use `EN16931`.
- Per-customer format resolution via `EbillingFormatResolver`: reads `companies.data.preferred_ebilling_format`, falls back to `default_format` config (default `zugferd`). Format is frozen on the document at generation time; preference changes affect only future documents.
- XRechnung documents are validated by KOSIT only (no PDF, veraPDF not invoked) and reach `Validated` on pass.
- Hybrid artifact validation runs veraPDF PDF/A-3 checks in `ValidateArtifactJob` when `moox/verapdf` is installed; verdicts persist via `veraPdfValidations()` alongside KoSIT results. When veraPDF is not configured, hybrid validation falls back to KOSIT-only (degraded mode).

### Changed

- Company name matching for field validation and format resolution is unified in `CompanyNameMatcher`; both consumers match against the persisted invoice buyer name (whitespace-collapsed, case-insensitive exact match, unique hit only). No longer filters on removed `company_type` / `is_active` columns. `EBillingFormatResolver` no longer reads `bill_data['customer_name']` ([#22](https://github.com/mooxphp/e-billing/issues/22)).
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
