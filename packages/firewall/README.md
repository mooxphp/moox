<div class="filament-hidden">

![Moox Firewall](banner.jpg)

</div>

# Moox Firewall

Moox Firewall allows you to lock down your website or application and allow access by whitelisting IP addresses or open a backdoor that needs a access token.

It will be integrated into Moox Auth in the near future.

## Screenshot

![Firewall Backdoor](./screenshot/main.jpg)

## Features

<!--features-->

-   Application level firewall
-   IP Whitelisting
-   Backdoor with Token

<!--/features-->

## Installation

```bash
composer require moox/firewall
```

## Configuration

You can configure all things in firewall.php:

```php
return [
    // Whitelist IP addresses to access the route or route group
    'whitelist' => array_filter(explode(',', env('MOOX_FIREWALL_WHITELIST', ''))),

    // Globally enable firewall?
    'global_enabled' => env('MOOX_FIREWALL_ENABLED', false),

    // Logo to display on the firewall page, not used yet
    'logo' => env('MOOX_FIREWALL_LOGO', 'img/logo.png'),

    // Backdoor allowed?
    'backdoor' => env('MOOX_FIREWALL_BACKDOOR', true),

    // Backdoor bypass token
    'backdoor_token' => env('MOOX_FIREWALL_BACKDOOR_TOKEN', 'let-me-in'),

    // Firewall page message
    'message' => env('MOOX_FIREWALL_MESSAGE', 'Moox Firewall'),

    // Firewall page color, currently hex only
    'color' => env('MOOX_FIREWALL_COLOR', 'darkblue'),

    // Firewall page description
    'description' => env('MOOX_FIREWALL_DESCRIPTION', 'Please enter your access token to continue.'),
];
```

## Usage

1. After installation you need to global_enable the firewall or use it in your routes
2. Set config values or use your environment to adjust it to your needs
3. Use the backdoor token to log in or append it to your URL like `?backdoor_token=let-me-in`

## Roadmap

See the [roadmap](ROADMAP.md) for more.

## Security

Please review [our security policy](https://github.com/mooxphp/moox/security/policy) on how to report security vulnerabilities.

## License

The MIT License (MIT). Please see [our license and copyright information](https://github.com/mooxphp/moox/blob/main/LICENSE.md) for more information.
