<div class="filament-hidden">

![Moox Msgraph](banner.jpg)

</div>

# Moox Msgraph

<!-- Description -->

Msgraph is a new package made with Moox.

<!-- /Description -->

The package provides Microsoft Graph SDK client creation for moox packages, including:
- a named connection registry (tenant + app credentials resolved by name)
- a Graph client factory
- an immutable-identifier header middleware on every outgoing request
- a Graph `InboxDriver` (`Moox\MsGraph\Mail\GraphInboxDriver`) auto-registered as `msgraph` on `InboxDriverManager` when `moox/mail-inbox` is installed

## Features

<!-- Features -->

- Named connection registry (`Auth\ConnectionRegistry`) resolving credentials by connection name
- `Auth\GraphClientFactory` building authenticated `GraphServiceClient` instances for named connections
- Every outgoing request carries `Prefer: IdType="ImmutableId"` (immutable identifiers)
- Typed exception types for Graph API failures (authentication, rate limiting, not found, connection/transport, expired delta sync state)
- Graph inbox driver: resumable delta fetch, claim, settle-by-outcome, attachment download
- This package owns mailbox folder names; consumers pass settlement outcomes only
- Configuration-only package: no models, no migrations, and no Filament surface

<!-- /Features -->

## Installation
To install this package, require it via Composer and run the Moox Installer:

```bash
composer require moox/msgraph
php artisan moox:install
```

Learn more about the [Moox Installer or common requirements](https://moox.org/docs/getting-started/installation).

## Screenshot
![Moox Msgraph screenshot](screenshot/main.jpg)

## Usage
<!-- Usage -->

### Configuration

Publish the config:

```bash
php artisan vendor:publish --tag=msgraph-config
```

Set your Azure AD credentials in `.env` (the default connection):

```env
MSGRAPH_TENANT_ID=your-tenant-id
MSGRAPH_CLIENT_ID=your-client-id
MSGRAPH_CLIENT_SECRET=your-client-secret
```

For multiple connections, add entries to `config/msgraph.php`:

```php
'connections' => [
    'default' => [
        'tenant_id' => env('MSGRAPH_TENANT_ID'),
        'client_id' => env('MSGRAPH_CLIENT_ID'),
        'client_secret' => env('MSGRAPH_CLIENT_SECRET'),
    ],
    'secondary' => [
        'tenant_id' => env('MSGRAPH_SECONDARY_TENANT_ID'),
        'client_id' => env('MSGRAPH_SECONDARY_CLIENT_ID'),
        'client_secret' => env('MSGRAPH_SECONDARY_CLIENT_SECRET'),
    ],
],
```

### Required Graph Application Permissions

Register an Azure AD App Registration with **application** permissions (not delegated):

- `Mail.Read` / `Mail.ReadWrite` — for mail operations
- `User.Read.All` — for user lookups

Grant admin consent after adding the permissions.

### Building a client

```php
use Moox\MsGraph\Auth\GraphClientFactory;

$factory = app(GraphClientFactory::class);

// Default connection
$client = $factory->make();

// Named connection
$client = $factory->make('secondary');
```

### Mail inbox driver

`Moox\MsGraph\Mail\GraphInboxDriver` implements `Moox\MailInbox\Contracts\InboxDriver`. The service provider registers it as the `msgraph` driver on `InboxDriverManager` when that manager is bound. Construct it manually with a `GraphServiceClient` from `GraphClientFactory`, a mailbox address, and `MailSettings` if you need a one-off instance. Expired delta tokens surface as `Moox\MailInbox\Exceptions\InvalidSyncCursorException`.

Folder display names and `$top` (`page_size`) live **only** in `config/msgraph.php`. The per-run page budget (`mail-inbox.delta_max_pages_per_poll`) is enforced by `moox/mail-inbox`. Domain code passes a `SettlementOutcome` (`Processed`, `Failed`, `Ignored`); it never passes a folder name. Consumers such as `moox/e-billing` settle foreign invoices as `Ignored` — they do not define `EBILLING_IGNORED_FOLDER` or other mailbox folder settings locally.

```env
MSGRAPH_MAIL_PROCESSING_FOLDER=Processing
MSGRAPH_MAIL_PROCESSED_FOLDER=Processed
MSGRAPH_MAIL_FAILED_FOLDER=Failed
MSGRAPH_MAIL_IGNORED_FOLDER=Ignored
MSGRAPH_MAIL_PAGE_SIZE=50
```

```php
'mail' => [
    'folders' => [
        'processing' => env('MSGRAPH_MAIL_PROCESSING_FOLDER', 'Processing'),
        'processed' => env('MSGRAPH_MAIL_PROCESSED_FOLDER', 'Processed'),
        'failed' => env('MSGRAPH_MAIL_FAILED_FOLDER', 'Failed'),
        'ignored' => env('MSGRAPH_MAIL_IGNORED_FOLDER', 'Ignored'),
    ],
    'page_size' => (int) env('MSGRAPH_MAIL_PAGE_SIZE', 50),
],
```

An empty `processing` folder skips the claim move (and creates nothing). Outcome mapping:

| Call | Config key | Default folder |
|---|---|---|
| `claim()` | `msgraph.mail.folders.processing` | `Processing` |
| `settle(Processed)` | `msgraph.mail.folders.processed` | `Processed` |
| `settle(Failed)` | `msgraph.mail.folders.failed` | `Failed` |
| `settle(Ignored)` | `msgraph.mail.folders.ignored` | `Ignored` |

`settle(Processed)` marks the message as read in Graph before moving it to the processed folder (same behaviour as the pre-extraction pipeline finalizer).

Folders are resolved by display name and created when missing. `fetch()` returns **one** Graph delta page. When Graph returns `@odata.nextLink`, that value is `MessagePage::$continuationCursor` so the domain job can loop up to `mail-inbox.delta_max_pages_per_poll`. When Graph returns `@odata.deltaLink`, that value is `MessagePage::$resumeCursor` (persist for the next poll). Both tokens are opaque; this driver allowlists Graph national-cloud hosts before calling `withUrl()`. Every request carries `Prefer: IdType="ImmutableId"`.

<!-- /Usage -->

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

