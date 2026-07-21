---
status: accepted
---

# Generate the e-invoice artifact first, then validate it (per-format, dual validation, unencrypted)

## Context

The e-billing conversion pipeline turned a parsed invoice into a **CII XML → KOSIT-validated XML → (if pass) merged ZUGFeRD PDF**. Two forces changed the requirements: (1) a future customer portal will let each customer choose their output **format** (XRechnung / ZUGFeRD / Factur-X), so format becomes a per-document input rather than a global `zugferd.profile`; and (2) we want to validate the **actual artifact we ship**, including PDF/A-3 conformance — which only exists once the PDF is built, and which the old "validate the loose XML then build the PDF" order could never check.

## Decision

Invert and restructure the pipeline to **generate the customer-chosen artifact first, then validate that artifact**, format-aware throughout.

- **Format = strategy seam.** A generator-strategy registry keyed by format; each entry declares `{ label, generator, artifact kind (xml|pdf), profile }`. The customer-facing menu is **XRechnung / ZUGFeRD / Factur-X** (three labels) backed by **two** internal generators (pure-XML; hybrid-PDF, since ZUGFeRD 2.x ≡ Factur-X). Scope now = the seam + the **three CII-based formats**. UBL and Peppol are future drop-in strategies (UBL is a separate generator/library and the on-ramp to Peppol); the validator is **not** made pluggable.
- **Format binding.** Customer preference lives in `companies.data` JSON (`preferred_ebilling_format`, written later by the portal). Each document **freezes** the format actually produced in a real `ebilling_documents.format` column. Resolution: `document.format` ← `company.data.preferred_ebilling_format` ← global default (**ZUGFeRD / EN16931**). Preference changes affect **future** documents only.
- **Jobs.** Two format-aware jobs replace the three step-named ones: `GenerateArtifactJob` (XRechnung = XML only; hybrid = XML + merge PDF in one job) → `ValidateArtifactJob`. `MergeZugferdPdfJob` is removed as a stage.
- **Validation stack.** **KOSIT** validates the XML for *every* format (loose, or extracted from the hybrid PDF via horstoeko `ZugferdDocumentPdfReader`). **veraPDF** (new `moox/verapdf` package, mirroring `kosit-validator`) validates PDF/A-3 for hybrids. A hybrid passes iff **KOSIT(xml) AND veraPDF(pdf)**.
- **No encryption on the deliverable.** PDF/A-3 forbids all encryption, so the existing `qpdf --encrypt` re-encryption of the merged PDF is **removed** (it produced non-conformant output that veraPDF would reject). Input decryption (`qpdf --decrypt`) stays as generation-time preprocessing. Tamper-resistance moves to: a **SHA-256 hash** of the validated artifact stored on the document (in scope, tamper-detection), plus **PAdES signing** and **revisionssichere Archivierung** (deferred — the *Revisionssicherheit* workstream; a signature is legally optional under §14 UStG but is the PDF/A-3-compatible successor to encryption).
- **Failure & status.** A built-but-invalid artifact is **retained and flagged** (never auto-deleted); validator reports persisted; delivery is validation-gated. The `gateway_status` enum becomes format-agnostic (`Generating / GenerationFailed / Validating / Validated / ValidationFailed / ValidatorError / IgnoredForeign`); `review_status` and `IgnoredForeign` are untouched. DB is dev-state and will be **wiped** — no data migration.
- **Storage.** `xml_storage_path` (always) + `pdf_storage_path` (hybrids only) + `storage_disk`; the deliverable is derived from `format`.

## Considered options

- **Keep validate-XML-before-build (cheap-check-first).** Rejected: it can never validate PDF/A-3 conformance of the shipped file, and doesn't fit a multi-format world where the artifact type varies.
- **Mustangproject as a single holistic validator.** Rejected: it would supplant KOSIT's XML verdict and make the XML authority differ by format (KOSIT for XRechnung, Mustang for hybrids). KOSIT + veraPDF keeps one XML authority for all formats and a reference tool per layer.
- **Encrypt / permissions-lock the delivered PDF for immutability.** Rejected: incompatible with PDF/A-3, and the owner-permissions form (the only one that stays readable) is strippable and not real protection. Immutability belongs at the archive/signature layer.

## Consequences

- One uniform "produce chosen format → validate it" flow; adding UBL/Peppol/a profile is additive (new strategy, jobs unchanged).
- veraPDF may reveal that the **source** supplier PDF isn't PDF/A (unembedded fonts/ICC), so the unencrypted merged output still fails — a source PDF/A-normalization step (e.g. Ghostscript `-dPDFA=3`) is the likely next work item (out of scope here).
- New runtime dependency: veraPDF (Java, MPL-2.0, CLI process like KOSIT — commercial-safe).
- Out of scope: portal UI, delivery/sending, UBL/Peppol, manual reprocess-in-new-format, PAdES, revisionssichere Archivierung, source PDF/A normalization.
