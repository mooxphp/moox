# Changelog

All notable changes to `moox/mail-outbox` will be documented in this file.

## Unreleased

### Added

- `SendMailJob` — queued send of a Mailable through a named Laravel mailer with `JobProgress` and `failed()` hook
- `MailSendLog` model and `create_mail_send_logs_table` migration (mailer, recipients, subject, template key, status, attempts, error, message id, provider reference, correlation id, polymorphic related)
- Statuses: `queued`, `sent`, `failed`, `suppressed` (`sent` = provider accepted + logged; `suppressed` reserved for test mode)
- Size guard (`MessageSizeGuard` / `MessageTooLargeException`) before transport, including path and `attachData` attachments
- Transient vs permanent failure classification (`MailFailureClassifier`) with configurable retry tries/backoff and provider retry-after honouring (delays clamped to ≥ 1s)
- Correlation: self-assigned header + optional per-mailer provider id read-back (`ProviderMessageIdReader`; default never confuses Message-ID with provider reference)
- `CONTEXT.md` domain language; config `mail-outbox.php`; Pest feature/unit coverage (mail fake + in-memory transport doubles)

### Fixed

- Do not invent package-local RFC 5322 Message-IDs after send; ensure Symfony’s on-wire Message-ID before transport and capture it from the sent copy
- Attach correlation header once across retries; honour zero retry-after without a tight loop
