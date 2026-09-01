# Microsoft Graph

Graph credentials, the Graph inbox driver, and the Graph outbound mail transport. This package owns Connections and mailbox folder names; it implements `mail-inbox`'s `InboxDriver` for inbound mail and registers a Symfony mailer transport (`microsoftgraph`) for outbound mail — both built from the same connection registry. It does not own messages, send logs, tables, or Filament (see [ADR 0001](../../docs/adr/0001-extract-msgraph-mail-packages-depend-on-contracts.md)).

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

**Outbound mail transport**:
The Symfony mailer transport registered under the `microsoftgraph` driver via `Mail::extend`. DSN credentials (client id, secret, tenant id) come from the same named Connection used by the inbox driver. A default `msgraph` Laravel mailer is registered unless the host app already defines one.
_Avoid_: SMTP transport, mail driver (Laravel calls this a "mailer")

## Not this context

**Inbox Message**, **Attachment**, **Outcome**, **Scope**, **Cursor** and **Catch-up** belong to `mail-inbox`. **Send log**, **Send window** and templates belong to `mail-outbox` — this package only supplies the transport those packages send through.
