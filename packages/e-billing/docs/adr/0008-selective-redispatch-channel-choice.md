---
status: accepted
date: 2026-09-22
---

# Selective redispatch: operator chooses channels; first dispatch stays all-channels

## Context

Approved documents run every FQCN in `e-billing.delivery.channels` (today typically mail + portal). *Erneut zustellen* (`redispatch_delivery` on ViewInvoice) re-queues that same full set. Operators often need only one channel again (failed mail, bounce recovery) and must not silently re-push a channel that already succeeded. Host/root ADR 0003 (outgoing invoice delivery) still says customer delivery is both channels for the **initial** path; that must not be read as forcing full-set redispatch forever.

## Decision

**Selective redispatch** is the operator *Erneut zustellen* path only:

- First post-approval dispatch still runs **all** configured channels (unchanged).
- The redispatch modal lists **configured** channels only (not orphan historical keys).
- Default selection: channels whose **latest attempt wave** **failed**, or that **never ran**; successful channels unchecked. A wave is every attempt row for that channel sharing the latest `created_at` (mail may write several recipients in one `deliver()` call).
- At least one channel required; empty confirm is invalid.
- Selecting a previously successful channel is allowed; the UI **warns** for every such selected channel (consistent across mail, portal, and future channels). Previous attempts are kept; each run records new attempt rows.
- **Channels only** — no recipient override in this modal (resolver / master data stay the source of truth).
- Each option shows a read-only last-attempt hint (status, when, and mail recipient when known).
- Lives in `moox/e-billing` (generic ViewInvoice); hosts only register channel FQCNs.

Does **not** amend host/root ADR 0003’s “both channels, always” for initial customer delivery. Does **not** change Foreign Source-PDF relay (ADR 0006).

## Considered options

- **Keep full-set redispatch.** Rejected: forces duplicate customer mail / redundant portal marks when only one channel needs recovery.
- **Hard-block redispatch of successful channels.** Rejected: legitimate resends exist; default unchecked + warning is the safety net.
- **Warn only for mail.** Rejected: inconsistent UX; warn for every successful selected channel.
- **Recipient override in the same modal.** Rejected: blurs retry dispatch with ad-hoc send; wrong-address fixes belong in master data or a later dedicated action.
- **Include historical channel keys no longer in config.** Rejected: redispatch runs live ports, not dead integrations; audit history stays on attempt rows.

## Consequences

- Glossary: *Selective redispatch* in this package’s `CONTEXT.md`.
- Queue/dispatch seam must carry an optional channel-key filter used only by redispatch; approval-triggered queue stays unfiltered.
- Multi-recipient channels judge success on the latest wave (all rows at max `created_at`), not a single arbitrary row.
- Issue tracker: `mooxphp/e-billing`; code PR: `mooxphp/moox`.
