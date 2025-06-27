> We are currently rebuilding the Monorepo, preparing for Filament 4, stay tuned!

# Moox

This is the Monorepo of the Moox Project. It is home of our ecosystem of Laravel packages and Filament plugins that are developed to form a CMS, Shop platform or other website or app.

If you want to install and use Moox, please refer to any of our packages or directly install a Bundle using Moox Core.

## Requirements

| Moox Version | Laravel Version | Filament Version | PHP Version |
| ------------ | --------------- | ---------------- | ----------- |
| 2.x          | \> 9.x          | 2.x              | \> 8.0      |
| 3.x          | \> 10.x         | 3.x              | \> 8.1      |
| 4.x          | \> 11.x         | 4.x              | \> 8.2      |

Moox Press packages require WordPress Version 6.7, password hashing is currently not compatible with newer versions. We will fix that soon.

## Installation

Install and use the Monorepos ...

```bash
git clone https://github.com/mooxphp/moox
composer create-project laravel/laravel mooxdev
composer require moox/devlink
php artisan vendor:publish --tag="devlink-config"
php artisan moox:devlink
```

There is another option for running our CI ...

```bash
# Installs a fresh Laravel app and all packages
php ci.php
# or to have a special Laravel version running
php ci.php -l=11.0
# and to clean up the Laravel app
php ci.php -d
```

## Todo

### Monorepo

-   Get CI working again
-   Restore README.md
-   Release feature
-   Restore Art files

### Filament 4

-   Copy over every Fila4-ready package
-   Restore the composer.json
-   Restore changes from Moox Jobs
-   Care for News, Monorepo, Github ...

### Installer

-   Check and Install Filament incl. user
-   tbc ...
