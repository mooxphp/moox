# TALLUI Monorepo

This is the Monorepo for TALLUI containing the following

## TALLUI App

Laravel app

- **TALL**UI Website - A locally installable dev app by now

## TALLUI Packages

Installable packages

- **TALL**UI AdminPanel - Lean Admin and Dashboard with simple API to build new Modules
- **TALL**UI Analytics - Integrates Open Web Analytics for privacy-compliant user tracking
- **TALL**UI Blocks - Block-Editor and blocks for Markdown-like editing experience
- **TALL**UI Brand Icons
- **TALL**UI Components - Our component-manager and base set of components
- **TALL**UI Config - Config Manager
- **TALL**UI Cookie - Consent Manager
- **TALL**UI Core - Core Package for building the TALL-Stack
- **TALL**UI Data - Manage static Data like languages, countries, currencies
- **TALL**UI Docs - Documentation module
- **TALL**UI File Icons - offers SVG file icons
- **TALL**UI Flag Icons
- **TALL**UI Google Fonts - offers all 1400 Google Fonts without Google Fonts CDN. Depends on [Laravel Google Fonts](https://github.com/spatie/laravel-google-fonts) from Spatie.
- **TALL**UI Logviewer - Logfile Explorer
- **TALL**UI Maps - Google Maps implementation
- **TALL**UI Material Icons
- **TALL**UI Media - Media-Manager
- **TALL**UI Packages - Composer-compatible Package Manager
- **TALL**UI Routes - Route Manager
- **TALL**UI Theme - Theme for tallui.io
- **TALL**UI Themes - Theme manager for website and admin-themes
- **TALL**UI Users - User manager

## Other stuff

There is some other stuff here

- **TALL**UI Art - Creative Artwork

## Installation

https://github.com/usetall/tallui.git



Probably required at first-install:

```shell
cd storage/
mkdir -p framework/{sessions,views,cache}
chmod -R 775 framework
```

## Installation (tbd)

Simple packages like components can ship their own configurations and assets. They don't depend on Core or any other package, but you have to care for the main dependencies, means the TALL-Stack.

**TALL**UI itself depends on on Laravel Jetstream.

```php
composer require usetall/tallui-adminpanel
```

will install Laravel Jetstream, **TALL**UI Core, Users and the main component libraries.

```php
composer require usetall/tallui-cms
```

will install **TALL**UI AdminPanel, Pages, Blog, Tools and System-Modules.

```php
composer require usetall/tallui-full
```

will install all **TALL**UI packages. The best option to explore **TALL**UI.

```javascript
{
    "repositories": [
        {
            "type": "path",
            "url": "../tallui/tallui-adminpanel"
        }
    ],
    "require": {
        "my/package": "*"
    }
}
```

You should now install the Livewire-Stack for Jetstream

```shell
php artisan jetstream:install livewire
```

run Laravel Mix

```shell
npm install && npm run dev
```

and run the migrations

```shell
php artisan migrate
```

## Objects

- Blocks
- Modules
- Components
- Data
- Docs
- Packages
- Themes