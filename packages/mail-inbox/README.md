![Moox MailInbox](https://github.com/mooxphp/moox/raw/main/art/banner/record.jpg)

# Moox MailInbox

Microsoft Graph–based mail inbox for Laravel (Moox package).

## Features

- Microsoft Graph delta sync for inbound mailbox polling
- Persistent `InboxMessage` and `InboxAttachment` records with idempotency via `scope` + `external_id` / `message_id`
- Attachment download and storage on a configurable disk
- PDF parse pipeline dispatch (`ParsePdfJob`) for e-billing integration
- Optional Graph folder routing (Processing, Processed, Failed)
- Scheduled `mail-inbox:poll` command with overlap protection
- Status and retry commands for operations and recovery

## Requirements

See [Requirements](https://github.com/mooxphp/moox/blob/main/docs/Requirements.md).

This package also requires:

- An Azure app registration with Microsoft Graph application permissions for the target mailbox (client credentials flow)
- Valid values for `MAIL_INBOX_TENANT_ID`, `MAIL_INBOX_CLIENT_ID`, `MAIL_INBOX_CLIENT_SECRET`, and `MAIL_INBOX_MAILBOX`
- The `microsoft/microsoft-graph` package (^2.26), installed automatically via Composer

## Installation

```bash
composer require moox/mail-inbox
php artisan moox:install
```

Curious what the install command does? See [Installation](https://github.com/mooxphp/moox/blob/main/docs/Installation.md).

## Configuration

Copy the environment variables below into your `.env` and adjust for your Azure app and mailbox.

### Environment variables

```env
# Required — Microsoft Graph client credentials
MAIL_INBOX_TENANT_ID=your-azure-tenant-id
MAIL_INBOX_CLIENT_ID=your-azure-app-client-id
MAIL_INBOX_CLIENT_SECRET=your-azure-app-client-secret

# Required — mailbox user principal name or ID polled via Graph
MAIL_INBOX_MAILBOX=invoices@example.com

# Optional — Graph folder display names (defaults shown)
MAIL_INBOX_PROCESSED_FOLDER=Processed
MAIL_INBOX_FAILED_FOLDER=Failed
MAIL_INBOX_PROCESSING_FOLDER=Processing

# Optional — scheduler and delta sync tuning
MAIL_INBOX_POLL_INTERVAL=5
MAIL_INBOX_DELTA_MAX_PAGES_PER_POLL=50

# Optional — job runtime limits
MAIL_INBOX_MEMORY_LIMIT=512M
MAIL_INBOX_RETRY_STALENESS_MINUTES=30
MAIL_INBOX_LISTENER_TIMEOUT_MINUTES=5

# Optional — attachment storage
MAIL_INBOX_ATTACHMENT_DISK=local
MAIL_INBOX_ATTACHMENT_PATH=mail-inbox/attachments

# Optional — ZUGFeRD / PDF extraction paths
MAIL_INBOX_ZUGFERD_PATH=zugferd
MAIL_INBOX_PDF_PASSWORD=
```

| Variable | Config key | Default | Required |
| --- | --- | --- | --- |
| `MAIL_INBOX_TENANT_ID` | `graph.tenant_id` | — | Yes |
| `MAIL_INBOX_CLIENT_ID` | `graph.client_id` | — | Yes |
| `MAIL_INBOX_CLIENT_SECRET` | `graph.client_secret` | — | Yes |
| `MAIL_INBOX_MAILBOX` | `mailbox` | — | Yes |
| `MAIL_INBOX_PROCESSED_FOLDER` | `processed_folder` | `Processed` | No |
| `MAIL_INBOX_FAILED_FOLDER` | `failed_folder` | `Failed` | No |
| `MAIL_INBOX_PROCESSING_FOLDER` | `processing_folder` | `Processing` | No |
| `MAIL_INBOX_POLL_INTERVAL` | `poll_interval` | `5` | No |
| `MAIL_INBOX_DELTA_MAX_PAGES_PER_POLL` | `delta_max_pages_per_poll` | `50` | No |
| `MAIL_INBOX_MEMORY_LIMIT` | `memory_limit` | `512M` | No |
| `MAIL_INBOX_RETRY_STALENESS_MINUTES` | `retry_staleness_minutes` | `30` | No |
| `MAIL_INBOX_LISTENER_TIMEOUT_MINUTES` | `listener_timeout_minutes` | `5` | No |
| `MAIL_INBOX_ATTACHMENT_DISK` | `attachments.disk` | `local` | No |
| `MAIL_INBOX_ATTACHMENT_PATH` | `attachments.path` | `mail-inbox/attachments` | No |
| `MAIL_INBOX_ZUGFERD_PATH` | `zugferd.path` | `zugferd` | No |
| `MAIL_INBOX_PDF_PASSWORD` | `zugferd.pdf_password` | — | No |

### Config file (`config/mail-inbox.php`)

| Key | Controls |
| --- | --- |
| `graph` | Azure AD tenant, client ID, and client secret for Graph API authentication |
| `mailbox` | User principal name or ID of the mailbox to poll |
| `processed_folder` | Display name of the Graph folder for successfully processed messages |
| `failed_folder` | Display name of the Graph folder for failed messages |
| `processing_folder` | Optional intermediate folder; after each delta batch, `FetchMailsJob` moves newly persisted messages here. Set to an empty string to skip the move |
| `poll_interval` | Minutes between scheduled `mail-inbox:poll` runs (clamped to 1–59) |
| `delta_max_pages_per_poll` | Maximum Graph delta pages fetched per `FetchMailsJob` run; large catch-ups span multiple polls |
| `memory_limit` | PHP memory limit applied during fetch jobs |
| `retry_staleness_minutes` | Attachments stuck in `processing` longer than this are eligible for retry |
| `listener_timeout_minutes` | Timeout for listener-style job coordination |
| `attachments.disk` | Laravel filesystem disk for stored attachments |
| `attachments.path` | Base path on the disk for attachment files |
| `zugferd.path` | Sub-path for extracted ZUGFeRD XML |
| `zugferd.pdf_password` | Optional password for encrypted PDF attachments |

## Commands

### `mail-inbox:poll`

Dispatches `FetchMailsJob` to the queue. The job continues the pipeline asynchronously (attachment storage and PDF parsing).

```bash
php artisan mail-inbox:poll --scope=default
```

Use this for manual polling or rely on the scheduler (see [Scheduling](#scheduling)). The `--scope` option selects the mailbox ingest scope (default: `default`).

### `mail-inbox:fetch`

Runs `FetchMailsJob` synchronously: fetches delta pages from Graph, persists new messages, and queues attachment/PDF jobs.

```bash
php artisan mail-inbox:fetch --scope=default
```

Use for debugging or one-off catch-up when you need the fetch to complete in the foreground. Attachment and PDF jobs may still run on the queue after the command returns.

### `mail-inbox:process`

Processes inbox messages through the e-billing PDF pipeline.

```bash
php artisan mail-inbox:process --scope=default
```

Processes all `new` messages in the scope. To retry failed messages:

```bash
php artisan mail-inbox:process --scope=default --retry-failed
```

To process a single message by database ID:

```bash
php artisan mail-inbox:process --scope=default --message=42
```

### `mail-inbox:status`

Shows message and attachment counts per processing status, plus latest received and processed timestamps.

```bash
php artisan mail-inbox:status --scope=default
```

## Scheduling

When the application runs in the console context, `MailInboxServiceProvider` registers a scheduled `mail-inbox:poll` command. The cadence is driven by `mail-inbox.poll_interval` (default: 5 minutes), expressed as a cron expression `*/{interval} * * * *` with the interval clamped between 1 and 59.

The scheduled run uses `withoutOverlapping()`, `runInBackground()`, and appends output to `storage/logs/mail-inbox.log`.

Ensure Laravel's scheduler is active in production (`* * * * * php artisan schedule:run`).

## The InboxMessage Model

The `InboxMessage` model (`Moox\MailInbox\Models\InboxMessage`) stores ingested mail metadata and processing state.

`scope` is the mailbox ingest identifier (not the Laravel scope pattern). It participates in unique constraints `(scope, external_id)` and `(scope, message_id)` for idempotent delta ingest. On MySQL/MariaDB, `external_id` and `message_id` use `utf8mb4_bin` collation so Graph opaque identifiers compare byte-for-byte.

Existing volatile Graph message IDs are reconciled automatically as delta sync re-discovers each message (see `docs/ARCHITECTURE.md`).

### Attributes

- `id` (bigIncrements) - Primary key
- `scope` (string) - Mailbox ingest identifier; indexed; part of unique `(scope, external_id)` and `(scope, message_id)`
- `channel` (string) - Source channel (default: `email`)
- `external_id` (string, nullable) - Microsoft Graph message ID; indexed
- `message_id` (string, nullable) - RFC 822 `Message-ID` header value
- `from_email` (string, nullable) - Sender email address
- `from_name` (string, nullable) - Sender display name
- `to_email` (string, nullable) - Primary recipient email
- `to_name` (string, nullable) - Primary recipient display name
- `subject` (string, nullable) - Message subject
- `received_at` (timestamp, nullable) - When Graph reports the message was received
- `raw_headers` (json, nullable) - Internet message headers as key-value pairs
- `raw_body_text` (longText, nullable) - Plain-text body when available
- `raw_body_html` (longText, nullable) - HTML body when available
- `has_attachments` (boolean) - Whether Graph reports attachments (default: false)
- `processing_status` (string) - Pipeline status (default: `new`)
- `processed_at` (timestamp, nullable) - When processing completed
- `error_message` (text, nullable) - Last error detail when failed
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

### Relationships

- `attachments()` - `HasMany` to `InboxAttachment`

## The InboxAttachment Model

The `InboxAttachment` model (`Moox\MailInbox\Models\InboxAttachment`) stores downloaded attachment metadata and per-file processing state.

### Attributes

- `id` (bigIncrements) - Primary key
- `scope` (string) - Mailbox ingest identifier; indexed
- `inbox_message_id` (foreignId, nullable) - References `inbox_messages.id` (`nullOnDelete`)
- `external_attachment_id` (string, nullable) - Graph attachment ID (`utf8mb4_bin` on MySQL/MariaDB)
- `storage_disk` (string) - Laravel filesystem disk name
- `storage_path` (string) - Path on the disk
- `filename` (string) - Original filename
- `mime_type` (string) - MIME type
- `extension` (string, nullable) - File extension
- `filesize` (unsignedBigInteger, nullable) - Size in bytes
- `checksum` (string, nullable) - Content checksum
- `is_pdf` (boolean) - Whether the attachment is a PDF (default: false)
- `attachment_role` (string, nullable) - Role classification when applicable
- `processing_status` (string) - Pipeline status (default: `new`)
- `zugferd_storage_disk` (string, nullable) - Disk for extracted ZUGFeRD XML
- `zugferd_storage_path` (string, nullable) - Path for extracted ZUGFeRD XML
- `processed_at` (timestamp, nullable) - When processing completed
- `error_message` (text, nullable) - Last error detail when failed
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

### Relationships

- `inboxMessage()` - `BelongsTo` to `InboxMessage`

## The MailInboxSyncState Model

The `MailInboxSyncState` model (`Moox\MailInbox\Models\MailInboxSyncState`) stores per-scope Microsoft Graph delta sync cursor state.

### Attributes

- `scope` (string) - Primary key; mailbox ingest identifier
- `delta_link` (text, nullable) - Persisted `@odata.deltaLink` URL for incremental sync
- `last_synced_at` (timestamp, nullable) - Timestamp of the last successful delta page
- `created_at` (datetime) - Creation timestamp
- `updated_at` (datetime) - Last update timestamp

When Graph invalidates a delta link (`syncStateNotFound`), the job resets sync state and performs a full re-sync for that scope.

## Public API

### `MailInboxService`

Primary methods for application and job integration:

- `persistDeltaMessages(array $graphMessages, string $scope): DeltaPersistResult` — Persist Graph delta messages and enqueue attachment jobs
- `finalizeMessageProcessingAfterAttachments(InboxMessage $message): void` — Resolve message status after all attachments reach a terminal state
- `enqueueParseJobsForInboxMessage(InboxMessage $message): void` — Queue `ParsePdfJob` for new PDF attachments
- `processNewMessages(string $scope = 'default'): int` — Process all `new` messages in a scope
- `retryFailedMessages(string $scope = 'default'): int` — Reset and re-queue failed messages and stale attachments
- `attachmentTerminalCountsForScope(string $scope): array` — Count processed/failed/skipped attachments for a scope
- `attachmentTerminalCountsForMessage(InboxMessage $message): array` — Same counts for one message
- `inboxStatusBreakdown(string $scope): array` — Message and attachment counts per processing status
- `latestReceivedAtForScope(string $scope): ?Carbon` — Latest `received_at` in a scope
- `latestProcessedAtForScope(string $scope): ?Carbon` — Latest `processed_at` in a scope

### `GraphMailService`

Microsoft Graph client for mailbox operations:

- `fetchInboxMessagesViaDelta(?string $deltaLink): DeltaPage` — Fetch one page of inbox mail via Graph delta
- `fetchAttachments(string $messageId): Collection` — List file attachments for a message
- `downloadAttachmentContent(string $messageId, string $attachmentId): array` — Download attachment bytes and metadata
- `markMessageAsRead(string $messageId): void` — Mark a Graph message as read
- `moveMessageToFolder(string $messageId, string $destinationFolderId, ?string $scope = null): void` — Move with pipeline source guard
- `moveGraphMessageToProcessingFolder(string $messageId, string $scope): void` — Best-effort move to `processing_folder`
- `moveGraphMessageToProcessedOrFailedFolder(string $messageId, bool $success, ?string $scope = null): void` — Move to Processed or Failed folder
- `moveGraphMessageToIgnoredFolder(string $messageId, string $ignoredFolderDisplayName, ?string $scope = null): void` — Move to a named ignore folder
- `getMessageParentFolderId(string $messageId): ?string` — Current parent folder ID
- `moveMessageToFolderByName(string $messageId, string $folderName, bool $createIfMissing = true, ?string $scope = null): void` — Resolve folder by display name and move
- `getOrCreateFolder(string $folderName): string` — Resolve or create a mail folder by display name

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

Thanks to so many [people for their contributions](https://github.com/mooxphp/moox#contributors) to this package.

## License

The MIT License (MIT). Please see [our license and copyright information](https://github.com/mooxphp/moox/blob/main/LICENSE.md) for more information.
