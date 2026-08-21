# Microsoft Graph

Graph credentials and the Graph inbox driver. This package owns Connections and mailbox folder names; it implements `mail-inbox`'s `InboxDriver` and does not own messages, tables, or Filament (see [ADR 0001](../../docs/adr/0001-extract-msgraph-mail-packages-depend-on-contracts.md)).

## Language

**Connection**:
A named set of Microsoft Graph credentials (tenant, client id, secret). Mailboxes in other packages refer to it by name.
_Avoid_: App registration, tenant, account, credentials

**Graph Inbox Driver**:
The Graph implementation of `InboxDriver`: delta fetch, claim, settle-by-outcome, attachment download. Registered as `msgraph`.
_Avoid_: Adapter, Graph client, mailbox service

**Immutable ID**:
The Graph identifier that stays stable when a message is moved between folders. Required on every outgoing request so stored `external_id` values do not change on settle.
_Avoid_: Graph id, item id, message id

**Mailbox folder**:
A display-named folder in the Graph mailbox. This package maps settlement Outcomes to folders; consumers never pass a folder name.
_Avoid_: Destination, disposition, pipeline stage

## Not this context

**Inbox Message**, **Attachment**, **Outcome**, **Scope**, **Cursor** and **Catch-up** belong to `mail-inbox`. **Send log**, **Send window** and templates belong to `mail-outbox`. There is no Graph send transport in this package yet.
