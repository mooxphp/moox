# Changelog

## Unreleased

### Changed

- Replaced `GenerateXmlJob` / `ValidateXmlJob` / `MergeZugferdPdfJob` with `GenerateArtifactJob` → `ValidateArtifactJob` (generate-first pipeline; KOSIT-only slice).
- `gateway_status` enum is now format-agnostic: `generating`, `generation_failed`, `validating`, `validated`, `validation_failed`, `validator_error`, `ignored_foreign`.
- Hybrid ZUGFeRD artifacts are built before validation; deliverable PDFs are unencrypted; `artifact_content_hash` is populated on validation pass.
- Pipeline events renamed to `ArtifactGenerated`, `ArtifactValidated`, `ArtifactValidationFailed`.
- Filament invoice list: `gateway_status` badge column, gateway failure/processing tabs, and gateway status filter.
- Artifact downloads (ZUGFeRD PDF / XML) are gated on `gateway_status = validated` and a stored `artifact_content_hash`.
