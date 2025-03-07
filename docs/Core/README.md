# Core

**Moox Core** provides the foundational functionality used by all other **Moox Packages**.

**Moox** is built with Laravel and Filament, aiming to become a modular framework for building Laravel applications, websites, or intranet solutions.

While **Moox Core** itself does not ship with any entities, it contains the essential services and traits that power **Moox**.

## Commands

### Moox Core

-   `php artisan moox:install` to install or update Moox packages
-   `php artisan moox:status` to show the status of Moox
-   `php artisan moox:wire` to wire Moox Entities

### Moox Build

-   `php artisan moox:build` to build a Moox package or Entity

### Moox Devlink

-   `php artisan moox:link` to symlink packages into a project
-   `php artisan moox:unlink` remove all symlinks and deploy

## Moox Service Provider

The **Moox Service Provider** is the central service provider for all Moox packages. It is responsible for loading all Moox packages and entities.

It is primarily used to register Moox packages and entities, and to make them available to the **Moox Installer** and the **Moox Build Command**.

The following example shows the minimal code needed to register a Moox package:

```php

<?php

class SkeletonServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        // Spatie Package Tools
        $package
            ->name('skeleton')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox Skeleton')            // title
            ->released(false)                   // released, default false
            ->stability('dev')                  // stability, default dev
            ->category('development')           // category, default unsorted
            // plugins, auto-detected if empty
            ->plugins([
                'skeleton',
            ])
            ->firstPlugin(false)                // first plugin, default false
            ->parentTheme('theme-moox')         // only if it ships with a theme
            ->staticSeeders(['SkeletonSeeder']) // only if it ships with static data
            // purpose, empty if template
            ->usedFor([
                'building new Moox packages, not used as installed package',
            ])
            // if the package is a template
            ->templateFor([
                'creating simple Laravel packages',
            ])
            // if the package is a template
            ->templateReplace([
                'Skeleton' => '%%PackageName%%',
                'skeleton' => '%%PackageSlug%%',
                'This template is used for generating Laravel packages.' => '%%Description%%',
                'building new Moox packages, not used as installed package' => '%%UsedFor%%',
                'creating simple Laravel packages' => '%%TemplateFor%%',
                '->category('development')' => '->category('unsorted')',
            ])
            // if the package is a template
            ->templateRename([
                'Skeleton' => '%%PackageName%%',
                'skeleton' => '%%PackageSlug%%',
            ])
            // if the package is a template
            ->templateSectionReplace([
                "/<!--shortdesc-->.*<!--\/shortdesc-->/s" => '%%Description%%',
            ])
            // if the package is a template
            ->templateRemove([
                'build.php',
            ])
            // alternate packages, otherwise none
            ->alternatePackages([
                'builder',
            ]);
    }
}
```

-   Extend the Moox Service Provider
-   Implement the `configureMoox` method
-   Use the `Package` class to configure the package, see [Spatie Package Tools](https://spatie.be/docs/laravel-package-tools/v6/installation-laravel) for more information

For the Moox Package all information is optional. Moox will use sane defaults or autodetect the needed information, feel free to override it. For the plugins array for example, use it to sort your navigation. As the build command also uses sane defaults, you can just leave everything as it is for your own Moox Package.

Yes, you're right, you don't even need to care about the technical implementation details, just use the `php artisan moox:build` command to create your Moox Package and let Moox do the rest.
