---
status: accepted
date: 2026-09-25
---

# Choosing between credit note (381) and corrected invoice (384) is an audited reviewer act, not a value correction

A credit note (381) credits an amount without correcting a specific invoice. A corrected invoice (384) corrects or cancels an issued invoice and must reference it (BG-3). The source document often reads the same for both, so a parser cannot tell them apart; a reviewer has to decide. `ClassifyDocumentTypeAction` performs that decision. The resulting field changes (`document_type`, the totals) are audited by **moox/audit** like every other invoice update, and the act itself is logged as its own activity, `document_classified` (from, to, whether amounts were negated), on the document. It is deliberately **not** a value correction (mooxphp/e-billing#40 / #45): a value correction means "the parser read the document wrong" and feeds the parser-feedback report, while a classification leaves the document's content untouched. Counting it as a correction would report every 384 as a parser defect in `document_type`.

The types a reviewer may switch between, and the sign their amounts carry, are configured in `e-billing.document_classification.types` (package default `381 => positive`, `384 => negative`, following the KoSIT/e-rechnung-bund FAQ and ADR 0010). Switching between types with different signs negates every document, line and allowance/charge amount in the same transaction, because the sign follows from the type, not from what the document prints. A classification is refused once the document is approved, like any review change. The choice is offered in the mixed review workspace (mooxphp/e-billing#47); leaving the workspace regenerates and re-validates the artifact (#48), so an artifact never carries the old type. The dropdown labels and "when to choose" hints are package translations (`e-billing::fields.document_classification.{code}`), which hosts override by publishing them.

## Considered options

- **A value correction on `document_type`.** Rejected: it would skew the parser-feedback metric that #40 exists to measure.
- **A dedicated review-actions table.** Rejected: moox/audit already records who changed which invoice field when; a second log would duplicate it. The distinction from a correction needs only a distinct activity event.
- **Re-signing amounts by hand as per-field corrections.** Rejected: dozens of corrections per document, all counted as parser errors, for a change that follows mechanically from the type.

## Consequences

- Like the other e-billing activities (e.g. `delivery_attempted`), the `document_classified` activity is written only when moox/audit is installed.
- The 384 profile is selected through `field_validation.document_type_profiles` (ADR 0009 addendum 2).
- The negative-total block of ADR 0010 stays limited to 381; a 384 is expected to carry negative amounts when it reduces or cancels.

## Addendum (2026-09-28): the uploader declares the type; sign convention for 384

Research: `docs/research/2026-09-28-credit-note-381-vs-corrected-invoice-384-examples.md` (web repo).

- **Declared document type.** The manual upload offers the resource's selectable codes, `resources.{key}.manual_upload.document_types` (credit notes: `381`, `384`). The list must be a subset of the resource's `document_types`, of `document_classification.types` and of `allowed_document_type_codes` (which now includes 384); a violation fails with a clear error when the upload action is built. With one code there is no dropdown, and with none the parser decides as before. There is no preselection: the choice is required. The choice is stored on the uploaded source (`UploadedPdfSource.document_type`) and recorded at upload time as a `document_classified` activity with the uploader as actor. After parsing, the pipeline applies it through the same classification core as the reviewer switch, including the sign flip, without an actor check; when that replaces the parsed type, a second `document_classified` activity (origin `parsing`) records the parsed and the declared type and whether the amounts were negated. A parsed type that is itself selectable is overridden: 381 and 384 cannot be told apart by parsing. A parsed type outside the selectable set (e.g. 380) is kept, and `document_type` becomes a review finding; it is never overwritten silently.
- **Classification family for duplicates.** The classification types count as one type for the identical-content and the number duplicate check. Uploading the same PDF again under the other type is an identical duplicate, and a wrongly declared type is fixed by reclassifying, never by a second upload. Until the review workspace (#47) ships, the interim fix is to delete the unapproved document and upload it again.
- **Instruction in the upload dialog.** A collapsible section, closed by default, with the rule (384: the original invoice was wrong or is cancelled, reference required; 381: the original was correct and the amount owed changed later, §17 UStG) and examples per code, as one compact text block (bold label, rule, bullet list; inline styles, all texts escaped). A 384 cancellation is labelled "Stornorechnung": XRechnung has no separate storno code (BR-DE-17), KoSIT calls a Stornorechnung a corrected invoice. It is translatable (`e-billing::fields.document_classification.*`, en and de), carries generic examples only, and hosts override them with their own cases. Sources stay in the research note, not in the UI.
- **Sign for 384.** Two valid patterns exist: a full, positive restatement of the corrected invoice (KoSIT test case 01.18) and a correction issued as a credit, a delta (e-rechnung-bund FAQ: "Hierzu wird eine Gutschrift ausgesprochen"). The package default `384 => negative` describes the delta. A host that issues full restatements configures `positive`. The earlier statement that a 384 "reverses the corrected invoice" applies to the delta pattern only.
- **Wording.** The label "Gutschrift" alone does not trigger § 14c UStG (UStAE 14.3 Abs. 1 S. 6, 14c.1 Abs. 3 S. 4). The legal weight lies in the type code and the reference to the original invoice. Choosing 381 where the original invoice was wrong leaves that invoice uncorrected.
