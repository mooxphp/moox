# E-Billing (generic) — Context

Glossary for the generic e-billing conversion pipeline (`packages/e-billing`). Keep lean; curated truth lives in the vault.

## Glossary

- **E-invoice format** — the concrete deliverable a customer receives. One of a fixed menu (currently under design): **XRechnung** (pure CII XML, no PDF), **ZUGFeRD** (PDF/A-3 with embedded CII XML), **Factur-X** (the French-labelled ZUGFeRD 2.x hybrid PDF). Distinguished by *profile* + *whether the XML is wrapped in a PDF/A-3*, not by three separate generators.
- **Profile** — EN16931 conformance level passed to the ZUGFeRD builder (`MINIMUM`, `BASIC`, `EN16931`, `EXTENDED`, `XRECHNUNG`). Today a single global config (`zugferd.profile`, default `EN16931`); moving toward per-customer selection.
- **Artifact** — the file produced by generation: a loose `.xml` (XRechnung) or a `.pdf` (ZUGFeRD/Factur-X). What gets validated and delivered.
- **Validation** — KOSIT/EN16931 conformance check. For pure XML it runs on the `.xml`; for a hybrid PDF it must run on the XML **as embedded in the PDF/A-3** plus PDF-conformance/XMP checks that only exist once the PDF is built.
- **Format choice** — customer-selected (planned: customer portal). Makes format a per-customer/per-invoice input rather than a global setting. Motivates generating the chosen artifact *first*, then validating that artifact.

## Current pipeline (generate-first, dual validation for hybrids)

`GenerateArtifactJob` (build chosen artifact — for ZUGFeRD: decrypt input PDF, merge unencrypted PDF/A-3 with embedded XML) → `ValidateArtifactJob` (KOSIT on the XML that will be delivered; + veraPDF on the PDF for hybrids when installed; SHA-256 hash on pass). When veraPDF is not installed, hybrid validation runs KOSIT-only (degraded mode). See ADR `docs/adr/0001-generate-then-validate-per-format-artifacts.md`.

## Proposed change

Generate the customer-chosen artifact first (XRechnung XML / ZUGFeRD PDF / Factur-X PDF), *then* validate that artifact. ✅ Implemented.

## Decisions (implemented)

- **Format = strategy seam.** ✅ Three formats registered: `xrechnung` (pure CII XML, `XRECHNUNG` profile), `zugferd` (hybrid PDF, `EN16931`), `factur-x` (hybrid PDF, `EN16931`). All share one `ZugferdGeneratorStrategy`; profile is per-format via `FormatDefinition.profile`. Adding UBL / Peppol = new registry entry + generator class; pipeline jobs unchanged.
- **Scope now:** the three **CII-based** formats are live. UBL and Peppol are deferred future strategies.
- **Validator stays single.** KOSIT is the only XML validator. XRechnung = KOSIT only. Hybrids = KOSIT + veraPDF.
- **Format binding.** ✅ `EbillingFormatResolver` reads `companies.data.preferred_ebilling_format` → falls back to `default_format` config (default `zugferd`). Format frozen on `ebilling_documents.format` at generation time (freeze = `xml_storage_path` is set). Preference changes affect future documents only.
- **Validation stack (validate the real artifact).** XML conformance → **KOSIT** for *every* format (pure XRechnung XML, or the XML **extracted from the hybrid PDF** via horstoeko `ZugferdDocumentPdfReader`). PDF/A-3 conformance → **veraPDF** (licensed MPL-2.0; commercial-safe as a CLI process). A hybrid passes iff **KOSIT(xml) AND veraPDF(pdf)**; a pure XRechnung passes iff **KOSIT(xml)**.
- **`moox/verapdf` = own package** (mirrors `kosit-validator`/`zugferd` boundary): config + `verapdf:install` command + `VeraPdfService::validate()` + persisted `VeraPdfValidation` model + morph pivot. Generic, no e-billing knowledge; e-billing orchestrates KOSIT+veraPDF. Note: veraPDF ships as an **installer zip** (headless IzPack install → `verapdf` launcher script), *not* a single `java -jar` standalone like KOSIT — the install command is heavier.
- **Failure handling.** Artifact is generated before validation; on failure it is **retained on disk + flagged** (never auto-deleted), validator reports persisted (KositValidation + VeraPdfValidation), `gateway_status` → failed, surfaced in "needs review". **Delivery is validation-gated** — an invalid artifact is never sent even though it exists.
- **Job shape.** Two format-aware jobs replace the three step-named jobs: `GenerateArtifactJob` (resolves format → generator strategy; XRechnung = XML only, ZUGFeRD/Factur-X = XML+merge PDF in one job) → `ValidateArtifactJob` (KOSIT on XML always; +veraPDF on PDF for hybrids). `MergeZugferdPdfJob` is removed as a stage; its `mergePdfWithXml` moves into the hybrid generator strategy.
- **Status enum** becomes format-agnostic & step-agnostic: `Generating, GenerationFailed, Validating, Validated, ValidationFailed, ValidatorError, IgnoredForeign`. `Validated` is the single success terminal for *all* formats. `ValidatorError` (tooling crashed) ≠ `ValidationFailed` (conformance failed). **`review_status`** (human workflow) and **`IgnoredForeign`** are untouched.
- **No data migration** — DB is dev-state and will be wiped; schema changes (new `format` column, new enum values) ship fresh without backfill.
- **Storage columns.** `xml_storage_path` (always — CII XML, what KOSIT validates) + `pdf_storage_path` (hybrids only, null for XRechnung) + `storage_disk`. Deliverable is *derived*: `format.isHybrid() ? pdf_storage_path : xml_storage_path`. (`zugferd_storage_path/disk` renamed.)
- **Idempotency.** `document.format` is frozen at generation. Retries regenerate idempotently against that frozen format (overwrite same paths); `ValidateArtifactJob` re-validates without rebuilding. A `preferred_ebilling_format` change applies to **future documents only** — existing documents are never silently regenerated.

## Scope of the reorder change

**In:** `moox/verapdf` package; generator-strategy seam + three CII formats; `company.data.preferred_ebilling_format` → frozen `ebilling_documents.format` (default ZUGFeRD); `GenerateArtifactJob → ValidateArtifactJob` (KOSIT always, +veraPDF for hybrids, `MergeZugferdPdfJob` removed as a stage); format-agnostic status enum + storage columns on fresh schema; events renamed to match.

**Out (deferred):** customer-portal UI that writes the format preference; actual delivery/sending of the validated artifact; UBL generator + Peppol; manual "reprocess in a new format" action; PAdES signing + revisionssichere Archivierung; source-PDF PDF/A normalization.

## Encryption & immutability

- **PDF/A-3 forbids all encryption** (incl. owner/permissions passwords). ZUGFeRD/Factur-X mandate PDF/A-3, so the deliverable must be **unencrypted**. The current `ZugferdConverter::mergePdfWithXml` re-encrypts (`qpdf --encrypt`, owner-only) → produces non-conformant output that veraPDF will reject. **The re-encryption (step 3) is removed.**
- **Decryption of the *input* stays** (`qpdf --decrypt`, config password): incoming supplier PDFs are owner-permissions-encrypted (open freely, password only to edit). Decryption is a generation-time preprocessing step (before merge), independent of veraPDF (which runs after and would catch leftover encryption).
- **"PDF 1.7" ≠ PDF/A-3.** PDF/A-3 is PDF 1.7 + constraints (XMP conformance decl, ICC, embedded fonts, `/AF` relationship, no encryption). Old output "worked" only because ZUGFeRD *consumers* read the embedded XML leniently without checking container conformance. veraPDF turns "it opened" into a real verdict.
- **Immutability is a system property, not a file lock.** German law (§14 UStG / EU 2010/45): authenticity+integrity via an *innerbetriebliches Kontrollverfahren mit verlässlichem Prüfpfad* — digital signature **optional**. Mechanisms: (1) revisionssichere/WORM archive = authoritative record [deferred, = epic's *Revisionssicherheit*]; (2) **SHA-256 hash of the validated artifact stored on the document [in scope]** = tamper-detection; (3) **PAdES signature** = recipient tamper-evidence & the PDF/A-3-legal successor to encryption [deferred, "later"].
- **Likely follow-up:** veraPDF may fail even the unencrypted output because the *source* supplier PDF isn't PDF/A (unembedded fonts/ICC); a source PDF/A-normalization step (e.g. Ghostscript `-dPDFA=3`) is the expected next finding — out of scope, flagged.
