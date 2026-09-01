# Changelog

All notable changes to `moox/mail-outbox` will be documented in this file.

## Unreleased

### Added

- `mail-outbox:test-send` Artisan command (`Commands\SendTestMailCommand`) — sends a probe mail through `SendMailJob` and prints the resulting `mail_send_logs` row; `--to=` (required), `--mailer=` (defaults to `mail.default`), `--test` (route through safe test mode), `--redirect=` (override sandbox address); transport-agnostic
- `Mail\OutboxTestMail` — minimal, transport-agnostic probe mailable used by `mail-outbox:test-send`
- Filament `MailSendLogResource` — list with status/mailer/date filters and config-driven tabs; detail view with intended vs actual recipients, identifiers, related-record link, confirmation-gated raw message, and resend action
- `MailOutboxPlugin` for panel registration via `moox:install`
- `raw_message` and encrypted `resend_payload` columns on `mail_send_logs`
- `ResendMailService` — dispatches `SendMailJob` for a new row; unavailable for `suppressed` or `recorded` rows; optional `resend.allowed_mailables` class allow-list
- Safe test mode — global `MessageSending` interception plus `SendMailJob` mixed-allowlist splitting; Laravel `alwaysTo` on redirect legs; wildcard allowlist; subject prefix; both recipient sets on the log; `suppressed` status; production boot warning; `MailSendLog::deliveredToIntendedRecipients()` contract
- `RecordSentMailListener` on `MessageSent` — dispatches `RecordSentMailJob` only (no DB work in the listener)
- `RecordSentMailJob` — records foreign Laravel mail sends from a queue-safe `RecordedSentMailSnapshot` built at dispatch; deduplication by correlation id (unique) or message id (indexed); disable via `record_foreign_mail` / `MAIL_OUTBOX_RECORD_FOREIGN_MAIL`
- `MailSendSource` enum (`outbox`, `recorded`) on `mail_send_logs.source`
- `SendMailJob` — queued send of a Mailable through a named Laravel mailer with `JobProgress` and `failed()` hook
- `MailSendLog` model and `create_mail_send_logs_table` migration (mailer, recipients, subject, status, attempts, error, message id, provider reference, correlation id, polymorphic related)
- Statuses: `queued`, `sent`, `failed`, `suppressed` (`sent` = provider accepted + logged; `suppressed` = test mode redirected at least one intended recipient)
- Size guard (`MessageSizeGuard` / `MessageTooLargeException`) before transport, including path and `attachData` attachments
- Transient vs permanent failure classification (`MailFailureClassifier`) with configurable retry tries/backoff and provider retry-after honouring (delays clamped to ≥ 1s)
- Correlation: self-assigned header + optional per-mailer provider id read-back (`ProviderMessageIdReader`; default never confuses Message-ID with provider reference)
- `CONTEXT.md` domain language; config `mail-outbox.php`; Pest feature/unit coverage (mail fake + in-memory transport doubles)

### Fixed

- Do not invent package-local RFC 5322 Message-IDs after send; ensure Symfony’s on-wire Message-ID before transport and capture it from the sent copy
- Attach correlation header once across retries; honour zero retry-after without a tight loop
- Foreign-mail recorder: listener dispatches a `RecordedSentMailSnapshot` (no live MIME); recordable when identifiers and/or recipients/subject are present; unique `correlation_id`; `failed()` logging; `tries = 1`


