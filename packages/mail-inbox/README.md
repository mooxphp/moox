<div class="filament-hidden">

![Moox MailInbox](banner.jpg)

</div>

# Moox MailInbox

<!-- Description -->

Mail-inbox is a new package made with Moox.

<!-- /Description -->

The package is part of the **Moox ecosystem** — a suite of Filament packages that form a solid foundation for Laravel apps, websites, CMS, and eCommerce projects.

Learn more about [Moox](https://moox.org).

## Features

<!-- Features -->

- Transport-neutral inbox driver contract (`InboxDriver`)
- Semantic settlement outcomes: `Processed`, `Failed`, `Ignored` — no folder assumptions
- Driver manager resolving named mailboxes to driver instances via configuration
- Two-tier config: connections (credentials) + mailboxes (driver, connection, address)
- In-memory fake driver (`tests/Support/InMemoryDriver`) for testing with no network access
- Opaque sync cursors — the package never inspects them; drivers must validate them
- Catch-up flag on sync state when a poll defers continuation before a resume cursor
- Optional Filament operator UI (`MailInboxPlugin`): inbox messages, attachments, sync state, retry/re-enqueue
- Microsoft Graph polling via delta API (when paired with `moox/msgraph`)

<!-- /Features -->

## Installation

To install this package, require it via Composer and run the Moox Installer:

```bash
composer require moox/mail-inbox
php artisan moox:install
```

Learn more about the [Moox Installer or common requirements](https://moox.org/docs/getting-started/installation).

This package does **not** require the Microsoft Graph SDK. For Exchange Online mailboxes, also require `moox/msgraph` (listed under `suggest` in `composer.json`). IMAP-only or custom drivers need only `moox/mail-inbox`.

## Screenshot

![Moox MailInbox screenshot](screenshot/main.jpg)

## Usage

<!-- Usage -->

### Driver Contract

The package defines an `InboxDriver` contract in `Moox\MailInbox\Contracts\InboxDriver`. Drivers implement five methods:

- `fetch(?string $cursor): MessagePage` — resumable page of messages against an opaque cursor
- `claim(string $externalId): ClaimResult` — claim a message for exclusive processing (`Won`, `AlreadyHeld`, `MoveFailed`)
- `settle(string $externalId, SettlementOutcome $outcome): void` — report outcome: `Processed` (success), `Failed` (error), or `Ignored` (recognised and deliberately not processed)
- `listAttachments(string $externalId): array` — file attachment metadata from the provider (no content bytes)
- `readAttachment(string $externalId, string|int $attachmentId): string` — read attachment content

`StoreAttachmentsJob` calls `listAttachments()` and `readAttachment()`; the fetch loop does not list attachments per message.

`MessagePage` carries two opaque tokens. `continuationCursor` is set while more pages remain in the current run and is null when that run is complete. `resumeCursor` is set only on the last page of a run and is what a later poll should pass back. Because this package never inspects a cursor, **validating it is the driver's responsibility**.

### Configuration

The config (`config/mail-inbox.php`) uses a two-tier shape:

```php
'connections' => [
    'default' => [
        'tenant_id' => env('MAIL_INBOX_TENANT_ID'),
        'client_id' => env('MAIL_INBOX_CLIENT_ID'),
        'client_secret' => env('MAIL_INBOX_CLIENT_SECRET'),
    ],
],

'mailboxes' => [
    'default' => [
        'driver' => env('MAIL_INBOX_DRIVER'),
        'connection' => 'default',
        'address' => env('MAIL_INBOX_MAILBOX'),
    ],
],
```

A mailbox names a driver and references a connection **by name** (a plain string, never a class). Several connections and several mailboxes can be configured, and two mailboxes may reference different connections.

There is no `direction` field — a mailbox's role follows from which configuration file it appears in.

Jobs and services resolve the driver through `InboxDriverManager` using the mailbox name as pipeline `scope`. Settlement uses `SettlementOutcome` (`Processed`, `Failed`, `Ignored`) — never folder names. Graph folder names live in `config/msgraph.php` when using `moox/msgraph`.

The sync-state table stores an opaque cursor (`delta_link`), a `driver` column, `cursor_reset_at` (timestamp of the last cursor reset), and `catch_up_in_progress` (whether a poll deferred continuation before completing). Each pipeline `scope` must have a matching `mail-inbox.mailboxes.{scope}` entry — the package does not backfill configuration from sync-state rows. Run `php artisan mail-inbox:status` after upgrades to list any sync-state scopes missing from configuration.

When a driver rejects a stored cursor as expired, `FetchMailsJob` clears it and starts a fresh sync. That reset is bounded by `cursor_reset_max_per_run` (default `1`). Repeated resets within `cursor_reset_warning_minutes` (default `60`) are logged as a warning.

### Filament operator UI

When Filament is installed, register `Moox\MailInbox\Plugins\MailInboxPlugin` on your panel (the Moox installer can do this automatically). The plugin adds two readonly resources:

- **Inbox messages** — list with config-driven tabs (all, new, failed, processed) and filters for mailbox, status, and received date. Open a message to see attachments with individual processing statuses and failure reasons. Body preview uses plain text when present, otherwise a stripped plain-text fallback from HTML (provider payloads are often HTML-only).
- **Mailbox sync** — per-scope sync state: mailbox name, address, driver, last sync, catch-up indicator, and the sync cursor as a truncated diagnostic blob (never parsed or presented as structured data).

Resource titles and tabs follow the Moox address-style config keys (`resources.*.single` / `plural`, tabs nested under the resource, top-level `navigation_group`).

Header actions on a message dispatch the existing pipeline services — **Retry failed** calls `MailInboxService::retryFailedMessage()` (same reset logic as `mail-inbox:process --retry-failed`, scoped to one message); **Re-enqueue processing** calls `enqueueParseJobsForInboxMessage()`. The package pipeline runs without Filament when the plugin is not registered.

### Breaking configuration change

Flat `graph`, `mailbox`, and folder keys were removed from the package config. Use `connections` + `mailboxes` instead. Require `moox/msgraph` for the Graph driver and set folder names there.

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


