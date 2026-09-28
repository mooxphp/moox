# ZUGFeRD — Context

Glossary for structured e-invoice **emission** (`packages/zugferd`, `moox/zugferd`): turning an emission view into CII XML and, for hybrids, embedding that XML in a PDF/A-3. This package is **not** the persisted invoice model (see `moox/invoice`) and **not** the conversion pipeline or format menu (see `moox/e-billing`). Keep lean; curated truth lives in the vault.

## Glossary

### Formats and profiles

- **Profile** — the EN 16931 / CIUS conformance level passed into the document builder for one convert (`MINIMUM`, `BASIC`, `EN16931`, `EXTENDED`, `XRECHNUNG`). It selects which carriers exist (e.g. how a line delivery date is written). Unknown keys are refused. *Avoid:* treating profile as the customer-facing format id; parking the pipeline default inside this package (defaults belong to the host / e-billing).

- **Format** — the customer-facing deliverable shape (XRechnung / ZUGFeRD / Factur-X): pure XML vs hybrid PDF container. Owned by the e-billing format registry, not by this package. ZUGFeRD 2.x and Factur-X share one hybrid generator; they differ by label/container preference, not by a third XML dialect. *Avoid:* “three generators”; conflating format with profile.

- **Hybrid PDF** — a PDF/A-3 whose human-readable pages carry an **embedded** CII XML (ZUGFeRD / Factur-X). Distinct from a loose XRechnung `.xml`. *Avoid:* “ZUGFeRD file” for pure XML; “PDF invoice” without saying whether XML is embedded.

- **Pure XML artifact** — CII XML alone (XRechnung path). No PDF wrapper. *Avoid:* calling it ZUGFeRD.

- **Unencrypted deliverable** — PDF/A-3 forbids encryption on the shipped hybrid. Input PDFs may be decrypted for merge; the merged artifact must not be re-encrypted. *Avoid:* owner-password “protection” on the outbound hybrid.

- **Business process (BT-23)** — the process identifier some profiles stamp on the document. Left to the builder’s profile defaults: Peppol URI on XRechnung 3; omitted on EN16931 hybrids. *Avoid:* forcing Peppol BT-23 onto every profile.

### Emission view

- **Emission view** — the flat, convert-ready shape of a document (`ZugferdInvoice` / line / address / bank / allowance contracts). Adapters in other packages project persisted or parsed data into it. *Avoid:* passing the e-billing DTO or Eloquent invoice straight into the converter; calling the emission view the “stored invoice”.

- **Incomplete invoice** — an emission view missing a field the converter requires for a valid EN 16931 document (e.g. seller/buyer postal address, seller electronic address, bank account with IBAN). Convert fails closed rather than inventing values. *Avoid:* silent defaults for missing fiscal parties or accounts.

- **Seller identifier** — BT-29 on the emission view. When non-empty (after trim) it is written as an unschemed seller id; when empty it is omitted. Meaning and persistence live with the invoice / e-billing glossaries. *Avoid:* inventing an ISO 6523 scheme the document never carried; conflating with VAT id (BT-31).

- **Preceding invoice reference** — BG-3 on the emission view (BT-25 number, optional BT-26 date). One referenced document per entry. Document-type rules for when it applies live in e-billing / invoice. *Avoid:* treating it as this document’s own number.

- **Document note** — unstructured text carried as BT-22 (and line BT-127 where used). Includes facts that have no IssueDate carrier (e.g. purchase order date must not become OrderReference IssueDate — UBL-CR-018). *Avoid:* stuffing structured trade refs into notes when a core BT exists.

- **Item attribute / classification** — BG-32 (BT-160/161) and BT-158 on a line. Free-text attributes need both name and value (BR-54); classifications need a scheme (BR-65), e.g. HS for customs. *Avoid:* dumping the same weight both as an attribute and as a duplicate BT-127 unless they differ.

### Ship-to on emission

- **Ship-to equality** — ship-to matches the buyer (or another baseline party) when names match case-insensitively after trim **and** the postal fingerprint matches: street, address line 2, postal code, country. **City is not** in the fingerprint. *Avoid:* name-token overlap as equality; treating layout labels such as `Firma` as address lines.

- **Duplicate consignee (emission)** — a ship-to that equals the buyer under ship-to equality. At convert time BT-70 / BG-15 are **omitted** (XRechnung §11.7); BT-72 may still emit. VAT category **K** still emits a full BG-15 so BR-IC-12 / BR-IC-11 can pass. Storage must not invent a buyer-as-consignee (host / invoice ADR 0014); this package only suppresses duplicates on new artifacts. *Avoid:* “always emit whatever is stored”; omitting BT-72 because the address was dropped.

- **Line ship-to promotion** — when the header has no consignee and every line shares one party that is **not** equal to the buyer, that party is emitted once as document BG-13 (emission only; the invoice column is not written). Otherwise divergent line parties become BT-127 notes; lines equal to the baseline get no note. *Avoid:* writing the promoted party back into storage from the converter.

### Delivery dates

- **Actual delivery date (document)** — BT-72 on the emission view, written as the document supply-chain event when present. *Avoid:* folding several different line dates into a header invoicing period (BG-14).

- **Line delivery date** — a per-line date on the emission view. Non-EXTENDED profiles carry it as a line billing period with start and end equal to that day; EXTENDED carries line actual delivery. The **profile** selects the carrier. *Avoid:* deriving BG-14 from line dates.

## Boundary

- **In:** CII XML build from an emission view; PDF/A-3 merge with embedded XML; emission rules that must be identical for every adapter (duplicate ship-to, profile-keyed line dates, BT-23 defaults, required-field refusals).
- **Out:** Persisted invoice schema; parse/ MoSCoW / ViewInvoice; format menu and artifact jobs; KOSIT / veraPDF validation; host PDF layout chrome (`Firma`, letterhead); Peppol network transport.

Cross-links: host ADR `docs/adr/0020-omit-duplicate-consignee-on-emission.md`, host ADR `docs/adr/0014-consignee-is-a-party-line-consignees-ride-as-line-notes.md`, e-billing ADR `packages/e-billing/docs/adr/0001-generate-then-validate-per-format-artifacts.md`, e-billing ADR `packages/e-billing/docs/adr/0012-supplier-number-bt-29-on-invoices.md`.
