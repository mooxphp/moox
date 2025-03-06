# Moox Core

**Moox Core** provides the foundational functionality used by all other **Moox Packages**.

**Moox** is built with Laravel and Filament, aiming to become a modular framework for building Laravel applications, websites, or intranet solutions.

While **Moox Core** itself does not ship with any entities, it contains the essential services and traits that power **Moox**.

## Core Concepts

### Moox Entity

A **Moox Entity** consists of a Migration, Model, Filament Resource including pages, a configuration file and translation files.

Depending on the type and purpose of the Entity, it ships with additional files.

There are three types of **Moox Entities**:

#### Moox Item

The **Moox Item** is a Filament Resource and Model that has the ability to build dynamic relations, to be extended by a **Moox Module** and to have one or more **Moox Taxonomies**.

A **Moox Item** consists of:

-   Migration
-   Model
-   Filament Resource (including pages)
-   Configuration file
-   Translation files
-   Frontend (optional)
-   Widgets (optional)
-   Relation Managers (optional)
-   Relation (optional)

There are a lot of **Moox Items** like

-   [Moox Page](https://github.com/mooxphp/page)
-   [Moox Post](https://github.com/mooxphp/post)
-   [Moox Product](https://github.com/mooxphp/product)

and there are some ready-made **Moox Item Templates**:

-   [Moox Item](https://github.com/mooxphp/item)
-   [Moox Record](https://github.com/mooxphp/record)
-   [Moox Publish](https://github.com/mooxphp/publish)
-   [Moox Draft](https://github.com/mooxphp/draft)

These templates are used by the `php artisan moox:build` command of

-   [Moox Build](https://github.com/mooxphp/build)

to create a new **Moox Item**.

#### Moox Taxonomy

The **Moox Taxonomy** is a Filament Resource and Model, that can be easily attached to an Item. It can have one or more Items. It can be extended by a **Moox Module**.

A **Moox Taxonomy** consists of:

-   Migration
-   Model
-   Filament Resource (including pages)
-   Configuration file
-   Translation files
-   Frontend (optional)
-   Widgets (optional)
-   Relation Managers (optional)
-   Relation (optional)
-   TaxonomyCreateForm (optional)

There are two main **Moox Taxonomies**:

-   [Moox Category](https://github.com/mooxphp/moox-category) - Nested Set Taxonomy
-   [Moox Tag](https://github.com/mooxphp/moox-tag) - Flat Taxonomy

Both are used as Global System Taxonomies, and by the `php artisan moox:build` command of Moox Build to create a new flat or nested **Moox Taxonomy**.

For managing flexible Global System Taxonomies, there is also the

-   [Moox Taxonomy](https://github.com/mooxphp/moox-taxonomy) - Flexible Taxonomy

that consists of **Terms** and **Taxonomies**, simply extensible, like WordPress does, with known limitations in nesting and performance.

While **Moox Taxonomy** offers flexibility in one simple package, you might find dedicated taxonomies like **Moox Category** and **Moox Tag** useful, specially when using the Nested Set feature with thousands of entries in combination with nesting and filtering.

There are a lot of those dedicated taxonomies:

-   [Moox Shop Category](https://github.com/mooxphp/shop-category) - Shop Category Taxonomy
-   [Moox Shop Tag](https://github.com/mooxphp/shop-tag) - Shop Tag Taxonomy

and it is easy to create your own dedicated taxonomies using the `php artisan moox:build` command of **Moox Build**.

#### Moox Module

The **Moox Module** is a Filament Resource and Model, that can be easily attached to an **Moox Item** or **Moox Taxonomy**. It's fields can be rendered in a tab of the edit form of the Item or Taxonomy, and on the Frontend.

A **Moox Module** consists of:

-   Migration
-   Model
-   Filament Resource Extender
-   Configuration file
-   Translation files
-   Frontend (optional)
-   Widgets (optional)

There are a **Moox Modules** like

-   [Moox SEO](https://github.com/mooxphp/seo) - Moox Module for SEO

and there is a **Moox Module Template**:

-   [Moox Module](https://github.com/mooxphp/module) - Moox Module Template

you can use the `php artisan moox:build` command of **Moox Build** to create a new **Moox Module** and just attach it to an **Moox Item** or **Moox Taxonomy** in the configuration file of the Entity.

### Moox Package

A **Moox Package** is a package that contains **Moox Entities**. It's Service Provider extends the **Moox Service Provider**.

**Moox Packages** follow the Laravel Naming Conventions, so Jobs are named like `ExampleJob`, Listeners are named like `ExampleListener` and so on, and the reside in the `/Jobs`, `/Listeners` and `/Console/Commands` folders.

Traits can be in the `/Traits` folder, or in the `/Console/Traits` folder, if they are CLI related.

**Moox Entities** follow this file structure:

```plaintext

    📦 package/
    |
    |── 📂 config/
    |   └── 📂 entities/
    |       └── 📜 example.php
    |
    |── 📂 resources/
    |   └── 📂 lang/
    |   |   └── 📂 en/
    |   |       |── 📜 example.php
    |   |       |── 📜 fields.php
    |   |       └── 📂 enums/
    |   |           └── 📜 enun-name.php
    |   |
    |   └── 📂 views/
    |       └── 📂 entities/
    |           └── 📂 example/
    |               ├── 📜 view.blade.php
    |               └── 📜 more-views.blade.php
    |
    |── 📂 database/
    |   |── 📂 migrations/
    |   |   └── 📜 2025_03_06_000000_create_example_table.php
    |   └── 📂 seeders/
    |       └── 📜 ExampleSeeder.php
    |
    |── 📂 src/
    |   └── 📂 Moox/
    |       └── 📂 Entities/
    |       |  └── 📂 Items/
    |       |   |   └── 📜 ExampleItem.php
    |       |   |   └── 📂 ExampleItem/
    |       |   |        ├── 📂 Relation/
    |       |   |        │   └── 📜 ExampleRelation.php
    |       |   |        ├── 📂 Widgets/
    |       |   |        │   └── 📜 ExampleWidget.php
    |       |   |        ├── 📂 RelationManagers/
    |       |   |        |   ├── 📜 ExampleRelationManager.php
    |       |   |        |
    |       |   |        └── 📂 Pages/
    |       |   |            ├── 📜 CreateExample.php
    |       |   |            ├── 📜 EditExample.php
    |       |   |            ├── 📜 ListExamples.php
    |       |   |            └── 📜 ShowExample.php
    |       |   |
    |       |   |── 📂 Taxonomies/
    |       |   |   └── 📜 ExampleTaxonomy.php
    |       |   |   └── 📂 ExampleTaxonomy/
    |       |   |       ├── 📂 Pages/
    |       |   |       ├── 📂 Widgets/
    |       |   |       ├── 📂 RelationManagers/
    |       |   |       ├── 📂 Relation/
    |       |   |       └── 📂 Forms/
    |       |   |            └── 📜 TaxonomyCreateForm.php
    |       |   |
    |       |   └── 📂 Modules/
    |       |       └── 📜 ExampleModule.php
    |       |       └── 📂 ExampleModule/
    |       |           ├── 📂 Widgets/
    |       |           └── 📂 Extender/
    |       |               └── 📜 ModuleExtender.php
    |       |
    |       |── 📂 Panels/
    |       |    └──📜 PackagePanel.php
    |       |
    |       |── 📂 Plugins/
    |       |    └── 📜 PackagePlugin.php
    |       |    └── 📜 EntityPlugin.php
    |       |
    |       └── 📂 Models/
    |            └── 📜 ExampleModel.php
    |
    |── 📂 tests/
    |   └── 📂 Feature/
    |       └── 📜 ExampleTest.php
    |
    └── 📜 PackageServiceProvider.php (extends MooxServiceProvider)

```

### Moox Installer

**Moox Packages** can be installed using Composer:

```bash
composer require moox/tag
php artisan moox:install
```

That is completely fine, if you want to install a single package, but - as the one and only drawback of modularity - you have to do this for dozens of packages to get a fully working application.

That's why there is **Moox Installer**:

```bash
composer require moox/core
php artisan moox:install
```

That is the intended way to install **Moox** as a whole. The installer will then guide you through the process of installing all necessary packages for your purpose.

### Moox Frontend

The package **Moox Frontend** is used to wire all needed parts together to generate a website:

-   **Moox Entities** - Entities like Page, Post deliver the content and views
-   **Moox SEO** - Modules like SEO add additional content and views
-   **Moox Components** - Renderless blade components
-   **Moox Navigation** - Navigations are dynamically rendered
-   **Moox Slug** - Slugs (permalinks) for all entities
-   **Moox Theme** - All layout and styling is done in themes

### Moox Components

The package **Moox Components** is used by **Moox Frontend** to build the base component layer:

-   [Moox Components](https://github.com/mooxphp/components) - Renderless Blade Components

Renderless means that the components do not contain any styles or scripts, but only a definition of the component and its props.

### Moox Navigation

The package **Moox Navigation** is used by **Moox Frontend** to build the navigation layer:

-   [Moox Navigation](https://github.com/mooxphp/navigation) - Build Navigations for your website

### Moox Theme

Theming your website is done in themes based on our

-   [Moox Theme Base](https://github.com/mooxphp/theme-base) - reduced to only functional styles
-   [Moox Theme](https://github.com/mooxphp/theme) - default theme with a modern and clean design

Means both themes are available for you to

-   use directly with your project
-   use them as parent or fallback theme when implementing your own
-   use them as starting point for your own theme

For example:

1. Use the `moox:build` command to create a new **Moox Theme**
2. That newly created theme already uses the **Moox Theme**
3. You can now extend the theme and add your own styles and scripts

It is possible to change the theme inheritance in your Service Provider, if needed.

### Moox Slug

The package **Moox Slug** is used by **Moox Frontend** to manage slugs and redirects for your entities:

-   [Moox Slug](https://github.com/mooxphp/slug) - Manage slugs and redirects for your entities

Moox Slug integrates in Moox Entities to create and manage slugs and redirects directly when creating or updating an entity.

### Moox Build

The package **Moox Build**

-   [Moox Build](https://github.com/mooxphp/build) - Build Moox Packages, Entities and Themes

is used to build **Moox Packages** with **Moox Entities** or a **Moox Theme** using a single command:

```bash
php artisan moox:build
```

The command will then guide you through the process of building an empty **Moox Package**, a package with a **Moox Item**, **Moox Taxonomy**, **Moox Module** or a **Moox Theme**.

![Moox](../../art/video/test-cli-video.gif?raw=true)

## Moox Commands

### Moox Core

-   `php artisan moox:install` to install or update Moox packages
-   `php artisan moox:status` to show the status of Moox
-   `php artisan moox:wire` to wire Moox Entities, Taxonomies and Modules

### Moox Build

-   `php artisan moox:build` to build a Moox package or Entity

## Technical Details

### Moox Service Provider

The **Moox Service Provider** is the central service provider for all Moox packages. It is responsible for loading all Moox packages and entities.

It is primarily used to register Moox packages and entities, and to make them available to the **Moox Installer** and the **Moox Build Command**.

The following example shows the minimal code needed to register a Moox package:

```php

<?php

declare(strict_types=1);

namespace Moox\Skeleton;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

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

### ... to be continued ...

The other parts are in the old docs.
