# Mail Inbox

Inbound mail processing: messages arriving in a mailbox, their attachments, and how far each one has got. The vocabulary here is deliberately transport-neutral — Microsoft Graph is one driver among possible others (see [ADR 0001](../../docs/adr/0001-extract-msgraph-mail-packages-depend-on-contracts.md)).

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

## Not this context

**Send window**, **Send log**, **Delivery channel** and **Approval** belong to outbound mail and invoice delivery. They are defined in [ADR 0002](../../docs/adr/0002-outbound-mail-through-laravels-mailer.md) and [ADR 0003](../../docs/adr/0003-outgoing-invoice-delivery-and-central-approval.md) until `moox/mail-outbox` exists and gets its own `CONTEXT.md`.
