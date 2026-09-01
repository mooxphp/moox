# Mail Inbox

Inbound mail processing: messages arriving in a mailbox, their attachments, and how far each one has got. The vocabulary here is deliberately transport-neutral — provider-specific drivers (for example `moox/msgraph`) register against the contract in this package (see [ADR 0001](../../docs/adr/0001-extract-msgraph-mail-packages-depend-on-contracts.md)).

## Language

### Access

**Connection**:
A set of credentials for a mail provider, identified by name. Owned by the provider package, never by this one.
_Avoid_: App registration, tenant, credentials, account

**Mailbox**:
A single mail address this system reads from or sends to, bound to a Connection and a Driver. Whether it receives, sends, or both follows from which configuration it appears in.
_Avoid_: Postfach, inbox, mail account

**Driver**:
The provider-specific implementation that fetches messages from a Mailbox and settles them. Interchangeable by configuration.
_Avoid_: Adapter, client, provider, gateway

**Scope**:
The label that partitions messages and sync state belonging to one logical intake stream.
_Avoid_: Tenant, channel, source

### Processing

**Inbox Message**:
One mail as this system knows it, independent of its representation at the provider.
_Avoid_: Mail, email, item

**Attachment**:
A file carried by an Inbox Message, tracked with its own processing state because it, not the message, is what downstream packages consume.
_Avoid_: File, document, enclosure

**Outcome**:
The terminal verdict on an Inbox Message — `Processed`, `Failed` or `Ignored`. It states what the message turned out to be, never where it should be moved.
_Avoid_: Status, result, folder, disposition

**Ignored**:
The Outcome for a message that is recognised and deliberately not processed, as opposed to one that failed. Currently: invoices for customers we are not obliged to issue e-invoices for.
_Avoid_: Skipped, rejected, discarded, foreign

**Cursor**:
The opaque marker of how far a Mailbox has been read. Only the Driver may interpret its contents.
_Avoid_: Delta link, sync token, offset, watermark

**Catch-up**:
A sync run that has not finished paging through the current fetch: the poll deferred on a continuation cursor (for example after `delta_max_pages_per_poll`) and will continue on the next poll. Stored as `catch_up_in_progress` on sync state — never inferred by parsing the Cursor.
_Avoid_: Backlog, lag, full sync, resync (those mean different things; resync is clearing an expired Cursor)

### Operator surface

**Inbox message resource**:
Readonly Filament list and detail over Inbox Messages and their Attachments — status, failure reason, retry / re-enqueue — registered only when `MailInboxPlugin` is on the panel.
_Avoid_: CRUD admin, second pipeline

**Sync state resource**:
Readonly Filament list of per-Scope sync diagnostics (Mailbox address, driver, last run, Catch-up, Cursor as diagnostic blob only).
_Avoid_: Cursor editor, sync control panel

## Not this context

**Send window**, **Send log**, **Delivery channel** and **Approval** belong to outbound mail and invoice delivery. They are defined in [ADR 0002](../../docs/adr/0002-outbound-mail-through-laravels-mailer.md) and [ADR 0003](../../docs/adr/0003-outgoing-invoice-delivery-and-central-approval.md) until `moox/mail-template` exists and gets its own `CONTEXT.md`.

