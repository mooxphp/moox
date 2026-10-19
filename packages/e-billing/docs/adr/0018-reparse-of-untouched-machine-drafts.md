---
status: proposed
date: 2026-10-08
---

# Reparse of untouched machine drafts

Supersedes the "Mapped once, no re-parse" bullet of ADR 0013 in part; the rest of ADR 0013 stands.

## Context

ADR 0013 says the payload is mapped into the Invoice once and that this package never deletes and recreates an Invoice ("There is no re-parse act"). In practice parser fixes must reach documents already imported but not yet looked at, so `ReparseDocumentAction` exists: it re-runs the parser on the stored source, soft-deletes the draft Invoice and generates a new one; the regenerated Artifact overwrites the previous files at the same path. The glossary documents the act as *Reparse* (see *Revalidate vs reparse*).

Research on GoBD (`docs/research/gobd-reparse-of-unsent-drafts.md` in the host repository) finds: drafts that were never sent need not be retained (GoBD Rz. 5); the sent structured XML must be kept unaltered (BMF E-Rechnung letter Rn. 60, GoBD Rz. 119/125/131); entries of an upstream system that serve as the booking voucher must not be changed without a log that keeps the original content ascertainable (GoBD Rz. 52, 84, 107–111).

## Decision

- Reparse is allowed only for an **untouched machine draft**: nobody reviewed, confirmed, approved or rejected it, and no delivery attempt exists on any channel. Otherwise it is refused.
- Reparse soft-deletes the draft Invoice and creates a new one from the source; the source PDF is never changed.
- Each reparse is recorded as a `document_reparsed` activity with actor, time and the old and new totals.
- Overwriting the Artifact of such a draft is accepted, because it was never made available to the customer and the bookings do not come from this package.

## Considered options

- **Keep ADR 0013 as written (no reparse; reviewers correct by hand).** Rejected: parser fixes for frequent document forms would leave every imported draft to manual correction.
- **Reparse into the existing Invoice, matching lines by BT-126.** Rejected for now (as in ADR 0013): needs line matching and still loses the machine reading.
- **No-overwrite reparse (new Artifact path per generation with content hash, predecessor link, parser version, reason, old content kept).** Advisable, not required for a never-sent draft. Deferred to the audit hardening.

## Consequences

- **Trigger for the no-overwrite reparse:** once bookings are derived from this package (e.g. an accounting export built from the Invoice), a draft becomes a booking voucher and the no-overwrite reparse is required before reparse may be used again.
- The refusal must keep covering every channel that can make a document available to the customer (mail, portal); a new channel must record a delivery attempt before it exposes the document.
- Glossary: *Revalidate vs reparse* in this package's `CONTEXT.md`.
