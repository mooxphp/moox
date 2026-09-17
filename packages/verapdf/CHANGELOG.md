# Changelog

All notable changes to `moox/verapdf` will be documented in this file.

## Unreleased

- Fix `veraPdfValidatables()` foreign key to `verapdf_validation_id` (Eloquent guessed `vera_pdf_validation_id`) so the Zuordnungen tab loads.
- Fix `VeraPdfValidation::getResourceName()` to `verapdf` so config-driven Zuordnungen (`verapdf.relations`) appear on the Filament resource.

- Models: `filename` and `result` accessors for config-driven relation tables.
- Filament: read-only `VeraPdfValidationResource` (list/view), report iframe + downloads, `VeraPdfPlugin`; MorphPivot registry wires `related_resource` for deep links from invoice tabs.

- Relations config: `verapdf_validatables` uses `pivot_has_many` / tab presentation; owner packages register `owner_types` at boot (e-billing registers `EbillingDocument`).

- Initial package: veraPDF install/validate/doctor, `VeraPdfService`, `VeraPdfResult`, audit model + morph pivot, Pest fixture tests.
