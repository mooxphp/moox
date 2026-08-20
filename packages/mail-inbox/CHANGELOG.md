# Changelog

## Unreleased

### Added

- Filament operator UI: `InboxMessageResource` (list, view, config-driven tabs under `resources.inbox-messages.tabs`, retry/re-enqueue actions) and `MailInboxSyncStateResource` (per-mailbox sync diagnostics)
- `MailInboxPlugin` for optional Filament panel registration (pipeline unchanged when not registered)
- `MailInboxService::retryFailedMessage()` for single-message retry (shared by the resource and `retryFailedMessages()`)
- Sync-state `catch_up_in_progress` column, set by `FetchMailsJob` when a poll defers continuation and cleared when a resume cursor is stored
- English and German translations for Filament resource titles and field labels (`resources/lang/{en,de}/`)

### Changed

- Filament config shape follows Moox address-style resources: `resources.*.single` / `plural`, tabs nested under each resource, top-level `navigation_group`
- Message body preview prefers `raw_body_text` and falls back to stripped plain text from `raw_body_html` (many provider payloads are HTML-only)

### Added

- `mail-inbox:status` reports sync-state scopes with no matching `mailboxes` entry (actionable configuration instructions)
- `cursor_reset_max_per_run` and `cursor_reset_warning_minutes` config keys to bound invalid-cursor reset loops
- Sync-state `cursor_reset_at` column recording when the cursor was last cleared

### Removed

- **Breaking:** Legacy Graph integration: `GraphMailService`, `MailInboxGraphServiceClientFactory`, `DeltaPage`, `DeltaMessageInspector`, and Graph exception classes. No Microsoft SDK types or Graph credential keys remain in this package.
- `suggest.microsoft/microsoft-graph` from `composer.json`.

### Changed

- **Breaking:** Removed `microsoft/microsoft-graph` from package requirements; `suggest` points at `moox/msgraph` only. Package description no longer claims to be Graph-based. Move Azure AD credentials from `MAIL_INBOX_TENANT_ID` / `CLIENT_ID` / `CLIENT_SECRET` to `MSGRAPH_*` in the host (and publish `config/msgraph.php`).
- Unconfigured mailbox exceptions name the scope, the `mail-inbox.mailboxes.{scope}` key, and required fields
- `InvalidSyncCursorException` and `InboxDriver::fetch()` document that the exception means a rejected cursor, not general failures
- `FetchMailsJob` stops resetting after `cursor_reset_max_per_run` and fails loudly instead of spinning

### Changed

- **Breaking:** Removed flat `graph`, `mailbox`, and folder keys from `config/mail-inbox.php`. Configure via `connections` + `mailboxes`; folder names live in the adapter package that registers your driver.
- **Breaking:** `mailboxes.*.driver` has no package default — set it explicitly in config or env.
- **Breaking:** `InboxDriver::claim()` returns `ClaimResult` (`Won`, `AlreadyHeld`, `MoveFailed`) instead of `bool`.
- Pipeline jobs and `MailInboxService` use `InboxDriver` / `SettlementOutcome` only (no provider SDK types at those call sites).
- `StoreAttachmentsJob` lists attachments from the driver; persist no longer writes stub attachment rows.
- Sync-state `driver` column is nullable with no default; mismatch with the configured driver clears the cursor for a fresh sync.
- `InMemoryDriver` moved to `tests/Support/` (dev autoload only).

### Added

- `InvalidSyncCursorException` for invalid/expired sync cursors
- `ClaimResult` enum for exclusive claim outcomes
- `InboxDriver::listAttachments()` for provider-side attachment metadata
- `InboxDriverManager::driverNameFor()` as the single source for mailbox driver names
- `InboxMessageDto::$messageId` (plus optional from/to name fields) for dual-key dedup
- Feature tests for persist, fetch, attachments, in-flight upgrade regression, and fake-driver E2E pipeline
- Package `phpunit.xml` and host test-suite wiring

### Changed

- **Breaking:** `MessagePage` now distinguishes `continuationCursor` (more pages in this run) from `resumeCursor` (start of the next run). The previous `nextCursor` property is removed.
- `InboxDriver` documents that cursor validation is the driver's responsibility, because the domain package treats cursors as opaque.

### Added

- `InboxDriver` contract: transport-neutral interface for fetching, claiming and settling inbox messages
- `SettlementOutcome` enum: `Processed`, `Failed`, `Ignored` — semantic outcomes, not folder operations
- `InboxMessageDto`: provider-agnostic message representation
- `MessagePage`: resumable page result with opaque continuation and resume cursors
- `InboxDriverManager`: resolves a named mailbox to its configured driver via configuration strings
- Two-tier config shape: connections (credentials) + mailboxes (driver, connection, address)
- Pest test suite covering the contract, driver manager, and fake driver

---

We previously didn't track changes in this package. Please refer to the [Moox Monorepo](https://github.com/mooxphp/moox) for historical changes.

