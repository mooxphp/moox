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
- In-memory fake driver for testing with no network access
- Opaque sync cursors — the package never inspects them; drivers must validate them
- Microsoft Graph polling via delta API (when paired with `moox/msgraph`)

<!-- /Features -->

## Installation

To install this package, require it via Composer and run the Moox Installer:

```bash
composer require moox/mail-inbox
php artisan moox:install
```

Learn more about the [Moox Installer or common requirements](https://moox.org/docs/getting-started/installation).

## Screenshot

![Moox MailInbox screenshot](screenshot/main.jpg)

## Usage

<!-- Usage -->

### Driver Contract

The package defines an `InboxDriver` contract in `Moox\MailInbox\Contracts\InboxDriver`. Drivers implement four methods:

- `fetch(?string $cursor): MessagePage` — resumable page of messages against an opaque cursor
- `claim(string $externalId): bool` — claim a message for exclusive processing
- `settle(string $externalId, SettlementOutcome $outcome): void` — report outcome: `Processed` (success), `Failed` (error), or `Ignored` (recognised and deliberately not processed)
- `readAttachment(string $externalId, string|int $attachmentId): string` — read attachment content

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
        'driver' => 'msgraph',
        'connection' => 'default',
        'address' => env('MAIL_INBOX_MAILBOX'),
    ],
],
```

A mailbox names a driver and references a connection **by name** (a plain string, never a class). Several connections and several mailboxes can be configured, and two mailboxes may reference different connections.

There is no `direction` field — a mailbox's role follows from which configuration file it appears in.

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

