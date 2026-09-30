---
status: accepted
date: 2026-09-30
---

# The Invoice is the aggregate root; e-billing owns the sealed record

For an outgoing e-invoice the GoBD-relevant **record** is what was transmitted, not the Invoice rows. The record is made up of the transmitted artifact (the structured XML is authoritative, and a PDF is only a view of it), the source it was produced from, and the human-readable copy PDF wherever a customer can receive or download it. Validator reports (KOSIT, veraPDF) are supporting evidence, not part of the record. The **Invoice** (`moox/invoice`) is the working model an artifact is generated from, and it is the aggregate root that processing concerns point at. It does not own them. The **e-billing case** (`EbillingDocument`) is the processing record from intake to dispatch. It exists before any Invoice does, and it owns the record. This matches how ERPs work: the billing document is the root, gets locked once posted, and its outputs are separate archived objects *linked* to it, not columns on it.

Today none of this holds. Artifact paths are deterministic and overwritten in place. Mail attaches whatever file is on disk at send time. Delivery attempts do not record which bytes they sent. `artifact_content_hash` is written but never compared against the file. Every generate run re-maps the parsed payload and soft-deletes and recreates the Invoice. This ADR fixes that.

## Decision

- **Sealed at approval, final at first transmission.** Dispatch approval seals the record: the approved bytes and their hashes. Nothing is regenerated after sealing. Until the first delivery attempt, the seal can be broken: withdrawing approval (for example on re-attribution) turns the bytes back into a draft, and the unsealing is audited. From the first delivery attempt on, failed or not (a mail may have partly left), the record is **final**. It cannot be unsealed, and re-attribution and rematch are refused. A change after that is only possible as a new commercial document (credit note 381 or corrected invoice 384), with its own case and its own record. Artifacts before sealing are drafts and may be overwritten.
- **Record files are 1:n per case.** Each file (kind: transmitted / source / copy, plus disk, path, sha256, sealed_at) is its own row belonging to the case. Hashing, verification, retention and "which file was sent" attach to a file, not to a case. Format stays frozen per case, so there is exactly one record per case. The Invoice reaches it as `$invoice->records`, a relation e-billing registers through `resolveRelationUsing`. `moox/invoice` gets no artifact or hash columns.
- **Dispatch sends the sealed bytes and says so.** Dispatch and every redispatch (ADR 0008) send exactly the sealed transmitted file, after verifying its hash. Each delivery attempt references the record file it sent. The copy PDF carries a hash like the transmitted artifact.
- **Hash verification.** The hash is checked before every send and before every customer download or portal delivery. A mismatch is a hard block, visible on the case.
- **Sealed files are never deleted.** No code path deletes a sealed record file, and delivery attempts on a final record no longer cascade-delete with the case. Who stores the bytes (this package or a media package) is outside this decision: the model is the same either way.
- **Mapped once, no re-parse.** The parsed payload (`bill_data`) is mapped into the Invoice exactly once, when the case gets its Invoice. Generation reads the Invoice rows, not the payload, and this package never deletes and recreates an Invoice. There is no re-parse act: a later parser fix does not touch existing cases, and reviewers correct wrong values by hand (value correction). Because rows are never rewritten by parsing, each row's `created` activity stays the one parsed value that value corrections are compared against.
- **The Invoice gets locked.** When the record becomes final, e-billing sets the generic invoice lock from `moox/invoice` (its ADR 0001), so no other consumer of the Invoice can change or delete it.
- **Attribution and approval stay here.** The matched debtor (`customer_id`) and dispatch approval are processing concerns of this package and do not move to the Invoice. `moox/invoice` keeps only the printed buyer identifier (BT-46).
- **Traceable acts are `audit` entries, not `log` entries.** `document_classified` becomes `entry_type = audit`, and its subject moves from the case to the Invoice. That is consistent with `value_corrected`: both are reviewer acts that change Invoice fields. The delivery-attempts activity becomes `entry_type = audit`, keeps the case as its subject, and carries the record file id and hash. The seal and unseal events are `audit` entries that carry the sealed hashes, so a hash edited in the database can be detected against the trail.

## Considered options

- **The Invoice as the record (hashes and files on `invoices`).** Rejected: the Invoice is changed by corrections and, until now, replaced by re-parse. It also has no concept of format or artifact, so adding them would couple `moox/invoice` to e-billing.
- **Seal at first successful delivery instead of at approval.** Rejected: it would leave a window where regenerated bytes go out without having been approved. Approval is the human sign-off and has to cover concrete bytes.
- **A permanent seal at approval.** Rejected: before anything has left, withdrawing approval is harmless, and requiring a credit note for it would be absurd.
- **Keep artifact columns on the case** (adding copy hash, sealed_at, finalized_at). Rejected: every file-level rule (hash, verification, retention, "what was sent") would exist three times.
- **Keep re-mapping on every generate run (delete and recreate).** Rejected: it silently discards value corrections and orphans their activity, severity releases and field validations, which are keyed by line row id.
- **An explicit re-parse act** (re-run the parser on the stored source and apply the result to the existing Invoice, matching lines by the printed line identifier, BT-126). Rejected for now: parser fixes on cases still in review are rare, and a reviewer can correct them by hand. It would also need a `reparsed` snapshot for the parsed value, and a guard against overwriting reviewer acts. If it is ever needed, it gets its own ADR. The Invoice's existing document versioning covers re-uploads of an invoice number, which is a different concern.
- **A hash chain across records.** Deferred: GoBD does not require one. A per-file hash plus append-only audit entries is enough until the revision-safety work.

## Consequences

- Amends ADR 0001: the "hash on the document" and the storage columns are replaced by record files. Idempotent overwrite now applies to drafts only.
- Amends ADR 0011: `document_classified` becomes an `audit` entry with the Invoice as subject.
- ADR 0004 (leave-edit regenerates) still holds, but only before sealing, and regeneration reads the corrected Invoice instead of re-mapping the payload.
- Fixes a latent hazard for value corrections (#45): once leave-edit regenerates (#48), delete-and-recreate would have discarded the corrections and orphaned their `value_corrected` activity. The "mapped once" change therefore has to land before #48 (#48 is blocked by it).
- GoBD completeness also needs append-only audit entries and per-model retention of at least eight years (mooxphp/audit#2). That is a named dependency, not a prerequisite for implementing this ADR. Until it lands, the audit trail can still be pruned or edited.
- Planned follow-up: a nightly job that re-hashes every sealed record file and reports mismatches. WORM storage and PAdES signing stay with the revision-safety work (ADR 0001).
- The DB is dev-only and gets wiped. No data migration.
- Glossary terms that land in `CONTEXT.md` together with the implementation: **Record**, **Sealing** (and its drafts), **Final record**. **E-billing case** is already in the glossary, because it only names the existing `EbillingDocument`.
