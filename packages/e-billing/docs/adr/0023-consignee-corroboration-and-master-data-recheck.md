---
status: accepted
date: 2026-11-12
---

# Consignee corroboration against all delivery addresses, and a delayed master-data recheck

## Context

Delivery corroboration (#25, narrowed by #98, ADR 0020) treated two kinds of separate consignee differently. A consignee carrying the buyer's name was checked against the buyer's delivery addresses only, and a miss needed review. A consignee of another company had to match the **buyer's** name and an address among the delivery addresses, then the billing addresses. Its name almost never matches the buyer's, so it practically never corroborated, and a miss was information only, because a genuine third-party delivery was assumed to be absent from master data.

That assumption no longer holds for hosts whose master data keeps third-party delivery addresses under the buyer, each with its own name. Corroboration exists to confirm that the parser read the consignee correctly, so a consignee that could be checked but was not found is a review case.

Master data is often synchronised from another system on a schedule. An invoice can be issued in the source system before the master data that belongs to it (a new delivery address, a changed address, a new customer) has reached the host. Field validation then reports a divergence that resolves itself on the next sync, and a person reviews a document that would have been clean minutes later.

## Decision

- **One rule for every separate consignee.** Its address must be corroborated (ADR 0019) against one of the buyer's addresses of the configured roles, and its name must pass the buyer name check (token overlap, #108) against the name kept on that master-data address or against the buyer's own names. The special path for a consignee carrying the buyer's name goes away.
- **`BuyerMasterDataAddress` gets an optional `name`.** The resolver fills it from master data; an address without a name can only match through the buyer's names.
- **Configurable, defaults as above** — `e-billing.master_data.consignee_corroboration`:
  - `address_roles`: `['delivery']`; `['delivery', 'billing']` restores the billing fallback.
  - `names`: `['address', 'buyer']`; `['buyer']` restores the old buyer-name-only rule, `[]` checks the address alone.
  - `on_miss`: `review` (`needs_review`, `consignee_not_in_master_data`) or `info` (the old `parsed` hint). Applies on the header and on lines.
- **Master-data recheck.** When field validation leaves a `needs_review` entry on one of the configured `recheck_fields`, unless its reason is one master data cannot resolve (consignee address missing, unrecognised print, print differs from the configured seller), and the document still needs review, `GenerateArtifactJob` dispatches a delayed recheck job. The job regenerates the document through the revalidate path, keeping review corrections, because corroborated master data may be written into the artifact.
  - Config `e-billing.master_data.recheck_after_minutes` and `recheck_attempts`; `0` disables either. The package defaults are `recheck_after_minutes = 0` (off) and `recheck_attempts = 1`.
  - The pending recheck is stored as `approval_flags.master_data_recheck` (`due_at`, `attempt`); no migration.
  - The attempt count is kept after the recheck ends, so a later generation does not start over.
  - When, by then, a person has edited, approved or held the document, it was dispatched, or no master-data finding is left, the job does not regenerate. It ends the recheck and tries auto-approval, or announces the review.
  - While a recheck is pending the document is not listed under "needs review" but under its own tab, and reviewer notifications and escalation wait for the last attempt. A clean recheck lets auto-approve proceed.

## Considered options

- **Keep third-party misses as information.** Rejected: master data now holds those addresses, and a miss is exactly the signal that the parser may have misread the consignee.
- **Address match alone.** Rejected as default (kept as `names: []`): a forwarder and the buyer can share an address.
- **A scheduled command scanning for due documents.** Rejected for now: it needs a due-at column and a minute-by-minute scan. A delayed queue job with the attempt in its payload needs neither.
- **Re-run field validation only.** Rejected: corroborated master data can end up in the artifact, so the artifact has to be regenerated as well.

## Consequences

- Hosts get more `needs_review` on consignees until their master data is complete; `on_miss: info` keeps the old behaviour.
- Documents already in review were judged under the old rule. Hosts should run `e-billing:revalidate --in-review --dry-run`, then `--in-review --hold`, after upgrading.
- Supersedes the delivery-address parts of ADR 0020 (consignee tiers; "`delivery_address` is unchanged").
