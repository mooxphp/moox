---
status: accepted
date: 2026-10-07
---

# Recipient override on selective redispatch

## Context

ADR 0008 kept the *Re-dispatch* action (selective redispatch) to channels only and rejected a recipient override in the same modal ("wrong-address fixes belong in master data or a later dedicated action"). In practice customers regularly ask for an invoice to be sent **additionally** to an address other than the invoice address (e.g. a second accounting mailbox) — a one-off favour, not a master-data error. Changing master data for that would redirect every future invoice; a separate action would duplicate the whole redispatch flow (channel choice, success warning, attempt recording).

## Decision

The selective redispatch modal gets an optional **Recipient override** for the mail channel:

- Field shown only while the mail channel is selected; one or more addresses, each validated as an email.
- Empty → recipients resolved as today (resolver / master data); the helper text shows the currently resolved address(es).
- Non-empty → the override **replaces** the resolved recipients for this run only. Master data, inbox To and the document stay unchanged.
- Mail-outbox safe test mode still applies to overridden addresses.
- When a previously successful mail channel is re-selected with an override, the warning states the redirection ("sent to X instead of Y") instead of the generic re-delivery warning.
- Each recipient is recorded on its delivery attempt; an additional activity entry records actor, time and override addresses. No mandatory reason.
- Portal and other channels are unaffected.

Supersedes the "Channels only — no recipient override" bullet of ADR 0008; the rest of ADR 0008 stands.

## Considered options

- **Keep ADR 0008 (fix master data, then redispatch).** Rejected: the use case is a one-off copy; changing master data would affect all future invoices.
- **Separate "send to other address" action.** Rejected: duplicates channel selection, warnings and attempt recording for a single extra field.
- **Add override recipients on top of the resolved ones (CC).** Rejected: blurs who an invoice was actually sent to; the operator can type the resolved address too if both are wanted.
- **Mandatory reason.** Rejected: extra friction for a routine request; the activity entry already names the actor.

## Consequences

- Queue/dispatch seam carries an optional recipient override next to the channel-key filter; approval-triggered dispatch passes neither.
- Channels opt in through `RecipientOverridableDeliveryChannel` (`deliverTo()`), so `DeliveryChannelInterface` and existing host channels stay unchanged; the shipped mail channel implements it.
- Glossary: *Recipient override* in this package's `CONTEXT.md`.
