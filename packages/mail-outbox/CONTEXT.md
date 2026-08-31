# Mail Outbox

Outbound mail: one Mailable leaves through a queued job, is accepted by a named Laravel mailer, and is recorded in a queryable send log. This package sits beside Laravel’s mailer; it does not own a transport.

## Language

### Sending

**Mailbox / Mailer**:
A named Laravel mailer configuration. Provider choice and credentials are a host concern.
_Avoid_: Transport contract, adapter (package-owned), SMTP session

**Send log**:
One row per send attempt chain for a single job dispatch — intended and actual recipients, subject, template key, status, attempts, error, identifiers, optional related business object, and **source** (`outbox` from `SendMailJob`, `recorded` from the framework `MessageSent` recorder).
_Avoid_: Archive, outbox folder, delivery receipt

**Source**:
Whether the row was created by `SendMailJob` (`outbox`) or by `RecordSentMailJob` after Laravel's post-send event (`recorded`). Foreign mail from other packages is logged as `recorded`; outbox sends stay `outbox` even when the recorder runs (deduplicated by correlation id).

**Status**:
`queued` → `sent` | `failed` | `suppressed`. **`sent` means the provider accepted the message and the send was logged** — never that the recipient’s mailbox received it. `suppressed` is reserved for safe test mode (later ticket).
_Avoid_: Delivered, opened, bounced (those need inbound reports)

**Size guard**:
Estimate of rendered message size (body + attachments) checked against a configured ceiling **before** the transport runs.
_Avoid_: Provider quota, attachment policy

### Failure

**Transient failure**:
Rate limit, timeout, connection error, HTTP 429/5xx — retried with backoff; provider `retry-after` honoured when present.
_Avoid_: Soft bounce (inbound)

**Permanent failure**:
Rejected or malformed recipient, most HTTP 4xx — terminal on the first attempt; attempt count stays at one.
_Avoid_: Hard bounce (inbound)

### Correlation

**Correlation id**:
Self-assigned id minted at send time, stored on the log and set as a message header. Used later to match reports that carry original headers.
_Avoid_: Message-ID (that is the separate RFC 5322 audit id)

**Provider reference**:
Provider-assigned id read back after accept (switchable per mailer). Never the RFC 5322 Message-ID. Default reader only picks provider-stamped headers already on the sent Symfony message; adapters that need a follow-up API call bind their own `ProviderMessageIdReader`.
_Avoid_: Delivery token, tracking pixel

**Message id**:
RFC 5322 Message-ID captured from the sent copy when present. Left null when the send path does not expose one — never invented.
_Avoid_: Provider reference

## Not this context

**Safe test mode**, **Filament send-log UI**, **database templates**, **send windows / throttling**, **inbound NDR correlation**, and **archiving / retention** are later tickets or other packages. Inbound vocabulary lives in [mail-inbox CONTEXT](../mail-inbox/CONTEXT.md).

