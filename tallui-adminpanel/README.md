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

- Dashboard 1000
- Pages 2000
- Blog 3000
  - Posts 3010
  - Categories 3020
  - Tags 3030
  - Comments 3040
- Shop 4000
  - Dashboard 4010
  - Products 4020
  - Categories 4030
  - Tags 4040
  - Customers 4050
  - Orders 4060
- Bookings 4100
  - Dashboard 4110
  - Properties 4120
  - Customers 4130
  - Reservations 4140
- Admin 5000
  - Users 5010
  - Permissions 5020
  - Packages 5030
  - Themes 5040
  - Components 5050
  - Blocks 5060
- Tools 6000
  - Scheduler 6040
  - Backup 6050
  - Import / Export 6070
- System 7000
  - Status 7010
  - Updates 7020
  - Configuration 7050
  - Routes 7070
  - Logs 7090
- Documentation 9000

You can override that in the config. But if you want to use some of our packages, it would be much better to use a free range between and build your own 2-level menus there. That's why we left plenty of space.