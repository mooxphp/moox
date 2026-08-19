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

## Features

<!-- Features -->

- Named connection registry (`Auth\ConnectionRegistry`) resolving credentials by connection name
- `Auth\GraphClientFactory` building authenticated `GraphServiceClient` instances for named connections
- Every outgoing request carries `Prefer: IdType="ImmutableId"` (immutable identifiers)
- Typed exception types for Graph API failures (authentication, rate limiting, not found, connection/transport)
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
use Moox\Msgraph\Auth\GraphClientFactory;

$factory = app(GraphClientFactory::class);

// Default connection
$client = $factory->make();

// Named connection
$client = $factory->make('secondary');
```

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
