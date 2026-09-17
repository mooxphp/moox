---
status: accepted
---

# Mixed review workspace and leave-edit pipeline

## Context

Invoice review split **value correction** (restoring parsed fields to what the source document says) from **customer attribution** (binding the document to a customer). Separate admin actions for set-attribution, re-match, and regenerate/revalidate multiplied entry points and made it unclear when the artifact was stale relative to review changes.

Product decisions in [#40](https://github.com/mooxphp/e-billing/issues/40) (amendment), [#47](https://github.com/mooxphp/e-billing/issues/47), and [#48](https://github.com/mooxphp/e-billing/issues/48) supersede the standalone attribution UI from [#27](https://github.com/mooxphp/e-billing/issues/27). Domain rules from #27 remain: `attribution_source` distinguishes auto vs manual, and rematch must not silently overwrite a manual attribution.

## Decision

### Mixed review workspace

One **edit mode** on the existing invoice admin document view combines:

- **Value corrections** on the configured **correctable** field set (per-field save, append-only correction records — [#45](https://github.com/mooxphp/e-billing/issues/45) / [#47](https://github.com/mooxphp/e-billing/issues/47)).
- **Customer attribution** on the same surface (searchable customer control; sets `attribution_source = manual` when the operator chooses a customer).

There is no separate attribution-only screen and no parallel edit flow.

### Master-data pickers (document-only)

For fields in the configured **cross-checked** set (same catalogue as corroboration / divergence — not a second list), the editor offers a searchable dropdown of master-data / bound data-package options **scoped to the currently attributed customer**, plus:

- **Default (permissive):** free text on the **document** when the value is not in that list.
- **Strict (later config):** select-only — free text disabled for that set and for the customer control.

Review **never writes master data**. Free entry and select-only only constrain what is stored on the document.

### Customer change mid-edit

When the attributed customer changes while still editing:

- Field values already on the document are **kept**.
- Master-data dropdown options **re-scope** to the new customer immediately.
- Values absent from the new customer’s master data are **flagged** (not auto-cleared, not written into master data).

### Per-field save vs leave-edit pipeline

- **Per-field saves** persist value corrections only; they do **not** rematch, regenerate, or revalidate.
- **Confirmed leave-edit** is the single reviewer-facing trigger for: rematch (manual attribution preserved) → regenerate via the existing idempotent path when needed → revalidate (existing validation job path). The reviewer is warned before this runs.
- Durable **Validating… / done / failed** status appears on the invoice admin surface; an actor notification on completion is allowed in addition to that status.
- Standalone set-attribution, re-match, and regenerate/revalidate admin actions are **retired** when this ships.

### Stale-artifact approval invariant

Approval remains refused while the artifact predates the most recent correction, while the leave-edit pipeline is in progress, or after a failed re-validation. Approval itself never starts regeneration.

### Semantics that stay

- Value correction still restores what the **source document** says — it does not align the document to master data to hide a divergence.
- Master-data divergence reporting stays separate ([ADR 0002](0002-reports-live-in-the-package-that-owns-their-data.md)).
- Manual `attribution_source` still survives rematch overwrite, including rematch started by leave-edit.

## Considered options

- **(a) Keep separate attribution and re-match buttons.** Rejected: duplicate entry points and unclear staleness relative to field corrections ([#47](https://github.com/mooxphp/e-billing/issues/47)).
- **(b) Auto-rematch on every field save.** Rejected: thrashing and unpredictable approval gates; #27 required an explicit human rematch trigger — leave-edit is that trigger ([#48](https://github.com/mooxphp/e-billing/issues/48)).
- **(c) Free entry writes master data from review.** Rejected: blurs document truth vs CRM stewardship; destroys a clean divergence baseline ([#40](https://github.com/mooxphp/e-billing/issues/40) out of scope).
- **(d) Keep a separate regenerate button alongside leave-edit.** Rejected: two contracts for the same downstream work.

## Consequences

- Operators get one mental model: edit fields and customer together, save fields incrementally, confirm once to refresh match and artifacts.
- Implementers must wire leave-edit to the existing rematch / generate / validate paths rather than inventing a second pipeline.
- Hosts must not expect master-data CRUD from review; CRM gaps stay on the divergence report and owning resources.
- Strict select-only is a config upgrade without changing workspace shape.
- Tests should assert: per-field save does not enqueue the full pipeline; leave-edit does; approval stays blocked under stale / in-progress / failed validation; manual attribution survives rematch.
