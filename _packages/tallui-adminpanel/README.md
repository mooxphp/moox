# TALLUI AdminPanel W-I-P

A lean AminPanel for Laravel Applications based on Laravel Jetstream and the TALL-Stack.

The package can be used to develop a feature-rich Laravel App or power the [**TALL**UI CMS](https://tallui.io).

## Modules

**TALL**UI AdminPanel is built on modules. There is a simple Module definition that cares for translatable multilevel menu items, the menu position and an optional icon.

- Module: the route of the module that should be called
- Position: 4-digit menu position
- Label: the translatable label
- Icon: own or from icon set

### Module auto-discovery

**TALL**UI auto-discovers modules through the packages service-provider

```php
<?php

namespace Tallui\Booking;

use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Tallui\Core\ExtensionServiceProvider;

class BookingServiceProvider extends ExtensionServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('your-package-name')
            ->hasConfigFile()
            ->hasViews()
            ->hasViewComponents('tallui', Booking::class)
            ->hasViewComposer('*', BookingViewComposer::class)
            ->sharesDataWithAllViews('downloads', 3)
            ->hasTranslations()
            ->hasAssets()
            ->hasRoutes(
            	'properties',
            	'booking', 
            	'book', 
            	'admin.booking',
            	'admin.booking.dashboard',
            	'admin.booking.properties',
            	'admin.booking.customers',
            	'admin.booking.reservation',
        		)
            ->hasMigration('create_package_tables')
            ->hasCommands(
            	YourCoolPackageCommand::class,
            	YourCoolPackageCommand::class
        		)
        	->hasModule('Booking', 'admin.booking', 'icon.book', 4100)
         	->hasModule('Dashboard', 'admin.booking.dashboard', 'icon.dashboard', 4110)
         	->hasModule('Properties', 'admin.booking.properties', 'icon.house', 4120)
         	->hasModule('Customers', 'admin.booking.customers', 'icon.people', 4130)
         	->hasModule('Reservations', 'admin.booking.reservations', 'icon.cal', 4140)
        	->hasWidget('Latest bookings', 'BookingsLatest', 'icon.book')
         	->hasWidget('Open bookings', 'BookingsOpen', 'icon.open')
         	->hasWidget('Next bookings', 'BookingsNext', 'icon.time')
           	->hasWidget('Find booking', 'FindBooking', 'icon.search')
        	->hasWidget('Booking statistics', 'BookingStats', 'icon.stats')
          	->hasBlock('Search Booking', 'SearchBooking')
        	->hasBlock('Properties', 'Properties')
           	->hasBlock('Booking', 'Booking')
        	->hasTheme('Booking', 'BookingTheme')
        	->hasDocs();
    }
}
```

### **TALL**UI default module positions

To fit the menu to our needs, there is an overview of all menu positions we set in our own application.

- Dashboard 100
- Pages 200
- News 300
  - Dashboard 300
  - Articles 301
  - Categories 302
  - Tags 303
  - Comments 304
- Shop 310
  - Dashboard 310
  - Products 311
  - Categories 312
  - Tags 313
  - Customers 314
  - Orders 315
  - Ratings 316
- Bookings 320
  - Dashboard 320
  - Properties 321
  - Categories 322
  - Bookings 323
  - Guests 324
  - Ratings 325
- Tools 600
  - Scheduler 610
  - Backup 620
  - Im- & Export 630
  - Newsletter 640
  - API-Manager 650
  - Redirects 660
  - SEO Toolbox 670
- Users
  - Dashboard
  - Users
  - Groups
  - Roles
  - Permissions
  - Teams
  - Activity
  - Security
- System 700
  - Status 701
  - Updates 702
  - Configuration 705
  /- Users 710
  /- Permissions 711
  - Packages 720
  - Routing 730
  - Logs 780
  - Vapor 790
- Help 900
  - User manual 901
  - Admin handbook 902
  - Developer docs 903

## Basic Scaffolding

- Users, Permission - extend
- Pages
- Contents
- Articles
- Categories
- Tags
- Comments
- Products
- ...


You can override that in the config. But if you want to use some of our packages, it would be much better to use the free range between 400 and 500 build your own 2-level menus there. That's why we left plenty of space.

## Alternatives and Inspiration

There are a lot of Admin Panels, CRUD Generators and UI Kits out there. Our selection mostly depend on the TALL-Stack.

- [TALL Frontent Preset](https://github.com/laravel-frontend-presets/tall) - Official UI preset.
- [Laravel Breeze](https://laravel.com/docs/9.x/starter-kits) - Official Starter Kit with Auth.
- [Laravel Jetstream](https://jetstream.laravel.com/) - Official Starter Kit with Auth, Register, 2-FA
- [Blade UIkit](https://blade-ui-kit.com/) – Components for the TALL-Stack, maintained from the Laravel devs
- [WireUI](https://livewire-wireui.com/) - Components with Livewire.
- [Filament](https://filamentphp.com/) - TALL stack UI und CRUD Generator.
- [Quick Admin Panel](https://quickadminpanel.com/) - Admin und CRUD Generator, TALL-Version verfügbar, $100/year.
- [Laravel Nova](https://nova.laravel.com/) - Offizielle Lösung mit Vue.js, kostet einmalig $199
- [Voyager](https://voyager.devdojo.com/) - Admin + CRUD Generator von Devdojo
- [Craftable](https://getcraftable.com/) - Freemium CRUD Generator and Admin.
- [Lean Admin](https://lean-admin.dev/) - TALL-stack basierendes Adminpanel, coming soon, nicht kostenfrei, [sneak peek](https://laravel-news.com/lean-admin-sneak-peek).

Siehe auch [Awesome Tall Stack](https://github.com/livewire/awesome-tall-stack) (Livewire Datatables, Views etc.)
