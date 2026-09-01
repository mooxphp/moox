<div class="filament-hidden">

![Moox MailOutbox](banner.jpg)

</div>

# Moox Mail Outbox

<!-- Description -->

Queued outbound mail for Laravel: send through `SendMailJob`, record a queryable send log, enforce a size ceiling before transport, classify transient vs permanent failures for retry, and mint correlation identifiers at send time.

<!-- /Description -->

The package is part of the **Moox ecosystem** — a suite of Filament packages that form a solid foundation for Laravel apps, websites, CMS, and eCommerce projects.

Learn more about [Moox](https://moox.org).

## Features

<!-- Features -->

- `SendMailJob` — send a Mailable via a named Laravel mailer and write one send-log row
- Send log statuses: `queued`, `sent`, `failed`, `suppressed` (`sent` means provider accepted + logged, not mailbox delivery)
- Size guard — fail with `MessageTooLargeException` before the transport runs
- Retry classification — transient (rate limit, timeout, connection) vs permanent (rejected/malformed recipient)
- Correlation — self-assigned header plus optional provider message-id read-back (per mailer)
- Optional polymorphic related business object on the log row
- Foreign mail recording — Laravel `MessageSent` listener dispatches `RecordSentMailJob` for mail sent outside `SendMailJob` (deduplicated against outbox rows)
- Filament send-log UI — list, detail, raw-message inspection, and resend (via `MailOutboxPlugin`)
- Safe test mode — redirect non-allowlisted recipients via Laravel's `alwaysTo`, record both recipient sets, prefix redirected subjects, log as `suppressed` when not delivered to intended recipients
- `mail-outbox:test-send` Artisan command — send a probe mail through any configured mailer and print the resulting send-log row

<!-- /Features -->

## Installation

To install this package, require it via Composer and run the Moox Installer:

```bash
composer require moox/mail-outbox
php artisan moox:install
```

Publish config and migrations if you prefer to do it manually:

```bash
php artisan vendor:publish --tag=mail-outbox-config
php artisan vendor:publish --tag=mail-outbox-migrations
php artisan migrate
```

Learn more about the [Moox Installer or common requirements](https://moox.org/docs/getting-started/installation).

## Screenshot

![Moox MailOutbox screenshot](screenshot/main.jpg)

## Configuration

Keys in `config/mail-outbox.php`:

| Key | Purpose |
| --- | --- |
| `max_message_bytes` | Rendered message size ceiling (default 10 MiB). Oversize fails before transport. |
| `retry.max_tries` | Maximum send attempts for transient failures (default 5). |
| `retry.backoff` | Backoff seconds between retries (default `[60, 300, 900]`). |
| `resend.allowed_mailables` | Optional class allow-list for resend payload restoration (default `[]` = all classes permitted after decrypt). |
| `correlation_header` | Header name for the self-assigned correlation id (default `X-Moox-Mail-Correlation-Id`). |
| `read_back_provider_id` | Default: whether to read the provider message id after send (default `false`). |
| `record_foreign_mail` | Record mail sent via Laravel's mailer without `SendMailJob` (default `true`). |
| `mailers.{name}.read_back_provider_id` | Per-mailer override for provider id read-back. |
| `resources.send-logs` | Filament resource labels and list-page tabs (`all`, `queued`, `sent`, `failed`, `suppressed`). |
| `navigation_group` | Filament navigation group for the send-log resource. |
| `test_mode.enabled` | Safe test mode: redirect non-allowlisted recipients (default `false`). |
| `test_mode.redirect_to` | Sandbox address for redirected mail (required when enabled). |
| `test_mode.redirect_name` | Optional display name for the sandbox recipient. |
| `test_mode.allowlist` | Wildcard patterns (`Str::is`) delivered for real while test mode is on. |
| `test_mode.subject_prefix` | Prefix for redirected mail; `%s` is replaced with the original recipient(s). |
| `test_mode.warn_in_production` | Log a warning at boot when test mode is on in production (default `true`). |

Environment variables: `MAIL_OUTBOX_MAX_MESSAGE_BYTES`, `MAIL_OUTBOX_RETRY_MAX_TRIES`, `MAIL_OUTBOX_CORRELATION_HEADER`, `MAIL_OUTBOX_READ_BACK_PROVIDER_ID`, `MAIL_OUTBOX_RECORD_FOREIGN_MAIL`, `MAIL_OUTBOX_TEST_MODE`, `MAIL_OUTBOX_TEST_MODE_REDIRECT_TO`, `MAIL_OUTBOX_TEST_MODE_REDIRECT_NAME`.

## Usage

### Send through the job

```php
use Moox\MailOutbox\Jobs\SendMailJob;

SendMailJob::dispatch($mailable, 'smtp');

// Optional related Eloquent model (polymorphic on the log row)
SendMailJob::dispatch($mailable, 'docs', $invoice);
```

The job:

1. Creates exactly one `mail_send_logs` row as `queued`
2. Checks rendered size against `max_message_bytes`
3. Mints a correlation id and adds it as a message header
4. Sends via Laravel’s mailer (`Mail::mailer($name)->send(...)`)
5. Updates the row to `sent`, `suppressed`, or `failed`

Work lives in the job (progress via `Moox\Jobs\Traits\JobProgress`, terminal handling in `failed()`). Listeners are not used for sending.

### Test-send command

```bash
php artisan mail-outbox:test-send --to=someone@example.com --mailer=smtp
```

Sends a minimal, transport-agnostic probe mailable (`Moox\MailOutbox\Mail\OutboxTestMail`) through `SendMailJob`, then prints the resulting `mail_send_logs` row (id, status, mailer, intended/actual recipients, message id, error). Any configured mailer works via `--mailer` — the command is not tied to a specific transport.

Without `--test` the command honours the ambient configuration, so a global `MAIL_OUTBOX_TEST_MODE=true` still redirects the probe. Use this to verify the environment switch itself: with test mode enabled in `.env` (and the config cache cleared), a run without any flag should come back `suppressed`, redirected to the sandbox — proof that nothing reaches the intended address. `--test` forces test mode on for a single run regardless of the ambient setting.

| Option | Purpose |
| --- | --- |
| `--to=` | Intended recipient address (required) |
| `--mailer=` | Named Laravel mailer (defaults to `mail.default`) |
| `--test` | Force [safe test mode](#safe-test-mode) on for this run — redirect + `suppressed` status. Omit it to honour the ambient `MAIL_OUTBOX_TEST_MODE` |
| `--redirect=` | Sandbox address for `--test`; overrides `mail-outbox.test_mode.redirect_to` for this run only |

### Safe test mode

When `test_mode.enabled` is true, **all outbound Laravel mail** is intercepted on `MessageSending` via `ApplyTestModeListener`. Non-allowlisted recipients are redirected to `test_mode.redirect_to`; allowlisted patterns (wildcard via `Str::is`) are delivered for real. Redirected mail gets a subject prefix naming the original recipient(s).

`SendMailJob` adds a second layer for mixed allowlist runs: it can perform two sends (real leg for allowlisted addresses, redirect leg for the rest) under one outbox log row. Non-outbox mail with mixed recipients is redirected entirely to the sandbox in a single send.

The log row always records **intended** recipients (from before redirection) and **actual** recipients (who received mail on the wire). When any intended recipient was redirected, status is **`suppressed`**, not `sent`. Foreign-mail rows recorded via `RecordSentMailJob` follow the same rule.

**Not-delivered guarantee:** use `MailSendLog::deliveredToIntendedRecipients()` (or `MailSendStatus::deliveredToIntendedRecipients()`) before marking a business object as delivered. A suppressed row means the provider may have accepted a sandbox copy, but the intended recipient did not receive the mail.

Mixed allowlist runs may perform two sends (real leg for allowlisted addresses, redirect leg for the rest) under one log row. Test mode logs a boot-time warning when enabled in production.

Exercise this from the command line with the [test-send command](#test-send-command): `php artisan mail-outbox:test-send --to=... --test`.

### Foreign mail recording

Mail sent through Laravel's mailer **without** `SendMailJob` can still be logged. On `Illuminate\Mail\Events\MessageSent`, `RecordSentMailListener` builds a queue-safe `RecordedSentMailSnapshot` from the sent message and dispatches `RecordSentMailJob` with that snapshot — the listener performs no database work or branching on business rules.

`RecordSentMailListener` (at dispatch):

1. Builds a `RecordedSentMailSnapshot` from the framework sent message (no live MIME on the queue)

`RecordSentMailJob` (when the job runs):

1. Returns immediately when `record_foreign_mail` is `false` (the listener still dispatches; the job no-ops)
2. Skips when the snapshot is missing, or when the snapshot has neither identifiers nor recipients/subject
3. Skips creating a row when an existing log matches the correlation header or Message-ID (`correlation_id` is unique; `message_id` is indexed for lookup). `SendMailJob` always stamps a correlation id, so its path never doubles.
4. Otherwise creates one `mail_send_logs` row with `source=recorded`, `status=sent`, and `attempt_count=1`

Rows from `SendMailJob` use `source=outbox`. Disable foreign recording with `MAIL_OUTBOX_RECORD_FOREIGN_MAIL=false` or `record_foreign_mail => false` in config.

### Send log

Model: `Moox\MailOutbox\Models\MailSendLog`  
Table: `mail_send_logs`

Notable columns: `mailer`, `source` (`outbox` | `recorded`), `intended_recipients`, `actual_recipients`, `subject`, `status`, `attempt_count`, `error`, `message_id` (RFC 5322), `provider_reference`, `correlation_id`, polymorphic `related`.

`sent` means the provider accepted the message and the send was recorded. This package does not assert recipient mailbox delivery. `suppressed` means test mode redirected at least one intended recipient — check `deliveredToIntendedRecipients()` before treating the send as delivered.

Also stored when available: `raw_message` (rendered MIME for inspection) and encrypted `resend_payload` (outbox sends only, for operator resend).

### Filament send log

`php artisan moox:install` registers `MailOutboxPlugin`, which exposes `MailSendLogResource` in the panel.

- **List** — status, mailer, recipient, subject, sent-at; filters on status, mailer, and date; config-driven tabs. Redirected sends show a badge when intended and actual recipients differ.
- **Detail** — intended and actual recipients, error, message id, related-record link when Filament can resolve one.
- **Raw message** — confirmation-gated modal for `sent` rows with stored MIME (may include personal data and attachment bytes).
- **Resend** — dispatches `SendMailJob` and creates a new row. Not offered for `suppressed` or `recorded` rows.

### Retry classification

| Kind | Examples | Behaviour |
| --- | --- | --- |
| Transient | Rate limit, timeout, connection error, HTTP 429/5xx | Retry with configured backoff; honour provider `retry-after` when present (`release($seconds)`). |
| Permanent | Rejected/malformed recipient, HTTP 4xx (except 429) | No retry; terminal on first attempt; `attempt_count = 1`. |

Exhausting transient retries leaves the row `failed`, not stuck in `queued`.

You can also throw `Moox\MailOutbox\Exceptions\TransientMailFailureException` or `PermanentMailFailureException` from custom transports/adapters.

### Correlation identifiers

Minted **only at send time**:

1. **Correlation id** — always minted, stored on the log, and set on the configured message header.
2. **Provider reference** — optional read-back via `ProviderMessageIdReader` after a successful send (switchable per mailer). This is **not** the RFC 5322 Message-ID (that column is separate). The default reader only returns provider-stamped headers already present on the sent Symfony message (`X-SES-Message-ID`, etc.). Bind a custom reader when the provider assigns ids only through a follow-up API call.

If read-back fails, the send is still `sent` with `provider_reference` left null. Before transport, the job ensures a Symfony-generated RFC 5322 Message-ID is on the message so the log can capture the on-wire id; it never invents a package-local id after the fact.

### Size guard

Before transport, `MessageSizeGuard` estimates rendered HTML/text size plus `attach` / `attachData` / `attachFromStorage` payloads. Oversize raises `MessageTooLargeException` and records the row as `failed` without invoking the transport.

## Testing

Package tests use Pest, `Mail::fake()`, and in-memory/array transport doubles. No network access is required.

```bash
cd packages/mail-outbox
# PHP 8.5+ (monorepo platform requirement), e.g. Herd's php85
php ../../vendor/bin/pest --configuration=phpunit.xml.dist
```

(`phpunit.xml` is gitignored at the monorepo root; the tracked file is `phpunit.xml.dist`.)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## Credits

Thanks to so many [people for their contributions](https://github.com/mooxphp/moox#contributors) to Moox, special thanks to our sponsors.

## Help Moox

Want to help us to develop and grow Moox. Fortunately there are so many ways to do this, learn more about [helping Moox](https://moox.org/help-moox).

## License

The MIT License (MIT). Please see [our license and copyright information](https://github.com/mooxphp/moox/blob/main/LICENSE.md) for more information.

