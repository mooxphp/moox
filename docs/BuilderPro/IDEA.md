# Idea

https://github.com/nunomaduro/skeleton-php

-   We do not have real translations (of fields) in Builder, that's odd
-   We cannot add contexts as the command is not flexible, it just knows preview and package (and falls back to app, I guess). It should be using the config and the config can have custom contexts. Another thing, contexts are taken from builder.php not pro/builder.php, why?
-   CustomDemo ausbauen, FullItemPreset with all Blocks, see if all blocks are ready for prime time
-   Translatable Block and TranslatableItemPreset
-   visible for features that depend on deleted tab must be compatible with translations
-   Installer in Moox Core - https://chatgpt.com/c/675da73e-ed6c-800c-8a7a-6a886fa78f1f
-   We have dependencies, these must be given to a package or app's composer.json ... but how? Also for preview ...? Maybe in first iteration it is just an informal message?
    -   Nested Taxonomy - Lazychaser Nested Set
    -   Translatable - Moox Languages
    -   Media Library Field - Spatie Media Library and Filament Spatie ML

## Translatable

-   Moox Data Languages
-   Moox Languages
-   Moox Translatable - Traits ...

## Media

-   siehe Media

## New Laravel

Of course you can use Builder in existing projects, but the best way to use Builder is to create a new Laravel project

```bash
composer create-project laravel/laravel buildertest
```

Using Laravel Herd for example this should work: http://buildertest.test/

Create a database and wire it in your .env

## Install

Install Moox Builder

```bash
composer require moox/builder
```

Route http://buildertest.test/builder da, Filament nicht? Müsste Core übernehmen?

Idee: ein Installer für alle, der entsprechend

-   Immer Filament prüfen muss oder installieren
-   Alle Tabellen und Plugins prüft und installiert

Das einzige was mir aufgefallen ist, das wenn man bei Content zu viel Text eingegeben hat, dass dann diese Fehlermeldung kam: SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column 'content' at row 1

I want to work with you on my project Moox Builder. The project is well documented in README.md and the current state and open issues can be found in DEVLOG.md

Please always stick to the rules in README and keep DEVLOG always updated. And please don't assume anything, instead ask for files.

One of the most important files is the builder.php config file that contains the wiring for contexts etc.

We need to work on the next Task in DEVLOG.

## Compatibility

All Moox Packages and Plugins are versioned together to one mayor version, currently **v4**.

The PHP compatibility is based on Laravel, visit [https://laravel.com/docs/11.x/releases](https://laravel.com/docs/11.x/releases#support-policy) for details, and defined in Moox Core's composer.json.

| Moox |    PHP    | Laravel | Filament | Livewire | Tailwind |
| ---- | :-------: | :-----: | :------: | :------: | :------: |
| <4   | 8.0 - 8.2 |  10,11  |   3.2    |    3     |    3     |
| 4    | 8.2, 8.3  |   11    |    4     |    3     |    3     |

Excluding Moox Press there are no database-specific implementations, so all database drivers should be working. However, we develop, test and deploy using MySQL, we are not able to reproduce issues on other DBMS.

## Restoring entities

Generation of entities is currently done by directly accessing the classes. If we want to restore entities from a build, we need to use the data array.

The current state of the data array is:

✓ useStatements (model, resource, pages)
✓ traits (model, resource, pages)
✓ methods
✓ formFields
✓ tableColumns
✓ block type and options
✓ migrations

Missing:

-   Form sections and meta sections (see Publish block lines 77-89)
-   Page-specific methods (see Publish block lines 63-72)
-   Form actions
-   Table filters
-   Table actions
-   Navigation settings
-   Resource configuration (icons, labels, etc.)
-   Context information (namespace, paths)
-   Relations configuration
-   Validation rules
-   Config entries
-   Sections

After adding the missing data, we need to implement the RestoreService and use it in the UI.

## Packages

Current Status:

-   Files are prepared: Service, Generators, Templates and Commands
-   Config is prepared
-   Install Template and Readme are not finished, as well as their partials
-   All templates could be completely prepared
-   Then we could go for the Generators
-   Then the Services
-   Finally the Commands

-   We need Preparation to be able to install packages locally, `PrepareAppForPackagesCommand`
-   Create a /packages directory
-   Paste `composerrepos.stub` into composer.json
-   Generate an empty package, where we are able to generate Entities in package context with the `CreatePackageCommand`, it uses the `PackageGenerator` Service that iterates over the new `package_generator` config key, that conntects the Generators and the Templates.

-   Now we can `Generate Entities` into that package
-   Generate the Entity in Package context
-   Generate the Resource part in the config, like wired in the `package_entity_enabler`config key
-   Generate the parts into the installer, like wired in the `package_entity_enabler`config key
-   Generate the part into the README, like wired in the `package_entity_enabler`config key
-   For activation of packages, I also created a config key `package_entity_activator`, that just wires the `PackageActivator`Service used by the `ActivatePackageCommand`

-   We need to require the package using composer
-   We need to run php artisan package:install
-   Finally the `PackagePublisher` service used by the `PublishPackageCommand`

-   Create a Git repo
-   Publish to GitHub
-   Add the package to Moox Monorepo
-   Publish to Packagist - https://packagist.org/apidoc#create-package

-   Later we'll need a `RemovePackageCommand` that uses the `PackageRemover`service
-   Last the `AbstractPackageService`
