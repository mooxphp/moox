---
status: accepted
date: 2026-09-18
---

# Manual confirm hard-blocks only on missing must fields

## Context

`review_status = db_validated` means automatic pre-review finished and a human may confirm. The invoice view banner and confirm action copy already treat that status as “manual confirmation needed,” including when attention fields remain (`action_confirm_with_attention`).

`ConfirmInvoiceAction` previously refused whenever `needsHumanReview()` was true. That predicate is broader than confirm: it treats **must/should + `needs_review`** and **must/should + `missing`** (should until severity release) as unresolved findings. It is also used for the review queue, auto-approve, and dispatch approval.

That made confirm effectively unreachable in the normal `db_validated` case: the same findings that keep the document out of auto-`validated` also failed confirm, while the UI still offered confirm. The failure notification claimed the document was not in “Automatically pre-reviewed,” which was wrong when `review_status` was already `db_validated`.

Severity gating from [#13](https://github.com/mooxphp/e-billing/issues/13) remains the source of truth for **findings** and for **`needsHumanReview()`**. This ADR only separates the **confirm** hard-stop from that broader predicate.

## Decision

### Confirm hard-block

`ConfirmInvoiceAction` (while `review_status` is `db_validated`) **hard-blocks only when a configured `must` field is `missing`**.

Confirm **is allowed** when open findings are only:

- `needs_review` on **must** or **should** fields (including `duplicate_invoice_number`)
- `missing` on **should** fields (severity release is **not** required for confirm)
- `could` / non-blocking statuses

Confirm remains refused when `review_status` is not `db_validated`, or when there is no linked document.

### `needsHumanReview()` unchanged

Do **not** redefine `needsHumanReview()` / `scopeNeedsHumanReview()` for this decision.

Tabs, filters, auto-approve, and dispatch approval keep today’s stricter meaning of “unresolved findings.” Operators may confirm a `db_validated` document while attention remains; later gates may still require clearance or an explicit human dispatch approval.

### UX on refuse

When confirm is visible (`db_validated`) but hard-blocked by missing must fields:

- Keep the confirm action visible.
- Show a specific failure that lists the missing **must** fields.
- Do not reuse the “not in status Automatically pre-reviewed” body for this case.

### Duplicates

Sibling versions of the same invoice number may still be flagged as `duplicate_invoice_number` / `needs_review`. That is attention, not a confirm hard-block. True foreign duplicates are likewise confirmable as attention; the human accepts them by confirming.

### Host field priorities

Which fields are `must` vs `should` remains host configuration under `field_validation`. Hosts should keep `must` aligned with legally required fields so the confirm hard-block stays meaningful. Narrowing a host’s must list is a separate config change, not part of this gate rule.

## Consequences

- Confirm becomes the human override for `db_validated` documents with open `needs_review` (and missing should) findings.
- Empty legal must fields remain a hard stop until values exist (no severity release for must).
- Severity release stays relevant for clearing `needsHumanReview()` / queue / auto-approve paths for missing should fields — not as a confirm prerequisite.
- Existing tests that expect confirm to refuse on should-missing or needs_review must be updated to match this gate.
- Implement the confirm-only predicate in (or beside) `ConfirmInvoiceAction`; do not silently loosen dispatch by changing `needsHumanReview()`.

## Related

- Severity gating / MoSCoW: [#13](https://github.com/mooxphp/e-billing/issues/13), `CONTEXT.md` (Severity gating)
- Dispatch approval remains a separate gate: [#15](https://github.com/mooxphp/e-billing/issues/15)
