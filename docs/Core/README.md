# Core

Moox Core provides the foundational functionality used by all other Moox Packages.

Moox is built with Laravel and Filament, aiming to become a modular framework for building Laravel applications, websites, or intranet solutions.

While Moox Core itself does not ship with any entities, it contains the essential services and traits that power Moox.

## Core Concepts

### Moox Entity

A Moox Entity consists of a Migration, Model, Filament Resource including pages, a configuration file and translation files.

Depending on the type and purpose of the Entity, it ships with additional files.

There are three types of Moox Entities:

#### Moox Item

The Moox Item is a Filament Resource and Model that has the ability to build dynamic relations, to be extended by a Module and to have one or more Taxonomies.

A Moox Item consists of:

-   Migration
-   Model
-   Filament Resource (including pages)
-   Configuration file
-   Translation files
-   Frontend (optional)
-   Widgets (optional)
-   Relation Managers (optional)
-   Relation (optional)

There are a lot of Moox Items like `Page`, `Post`, `Product`, etc., and there are some ready-made templates:

-   Moox Item
-   Moox Record
-   Moox Publish
-   Moox Draft

Those templates are used by the `moox:build` command of Moox Build to create a new Moox Item.

#### Moox Taxonomy

The Moox Taxonomy is a Filament Resource and Model, that can be easily attached to an Item. It can have one or more Items. It can be extended by a Module.

A Moox Taxonomy consists of:

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

#### Moox Module

The Moox Module is a Filament Resource and Model, that can be easily attached to an Moox Item or Taxonomy. It's fields are rendered in a Tab on the Item or Taxonomy.

A Moox Module consists of:

-   Migration
-   Model
-   Filament Resource Extender
-   Configuration file
-   Translation files
-   Frontend (optional)
-   Widgets (optional)

## Moox Package

A Moox Package is a package that contains Moox Entities. It's Service Provider extends the Moox Service Provider.

Moox Packages follow the Laravel Naming Conventions, so Jobs are named like `ExampleJob`, Listeners are named like `ExampleListener` and so on, and the reside in the `/Jobs`, `/Listeners` and `/Console/Commands` folders.

Traits can be in the `/Traits` folder, or in the `/Console/Traits` folder, if they are CLI related.

Moox Entities follow this file structure:

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
  └── 📂 src/
      └── 📂 Moox/
         └── 📂 Entities/
          |  └── 📂 Items/
          |   |   └── 📜 ExampleItem.php
          |   |   └── 📂 ExampleItem/
          |   |        ├── 📂 Relation/
          |   |        ├── 📂 Widgets/
          |   |        ├── 📂 RelationManagers/
          |   |        └── 📂 Pages/
          |   |           ├── 📜 CreateExample.php
          |   |           ├── 📜 EditExample.php
          |   |           ├── 📜 ListExamples.php
          |   |           └── 📜 ShowExample.php
          |   |
          |   |── 📂 Taxonomies/
          |   |   └── 📜 ExampleTaxonomy.php
          |   |   └── 📂 ExampleTaxonomy/
          |   |       ├── 📂 Pages/
          |   |       ├── 📂 Widgets/
          |   |       ├── 📂 RelationManagers/
          |   |       ├── 📂 Relation/
          |   |       └── 📂 Forms/
          |   |
          |   |── 📂 Modules/
          |   |   └── 📜 ExampleModule.php
          |   |   └── 📂 ExampleModule/
          |   |       ├── 📂 Widgets/
          |   |       └── 📂 Extender/
          |   |
          |   |── 📂 Panels/
          |   |    └──📜 PackagePanel.php
          |   |
          |   └── 📂 Plugins/
          |        └── 📜 PackagePlugin.php
          |        └── 📜 EntityPlugin.php
          |
          └── 📜 PackageServiceProvider.php (extends MooxServiceProvider)
```

## Moox Models

```plaintext
        +-------------------------------------+
        | 🧑 User                             |
        +-------------------------------------+
        | id: int                             |
        | name: str                           |
        | email: str                          |
        +-------------------------------------+
        | posts(): HasMany                    |
        +-------------------------------------+

        +-------------------------------------+
        | 📝 Item                             |
        +-------------------------------------+
        | id: int                             |
        | title: str                          |
        | body: text                          |
        | user_id: fk                         |
        +-------------------------------------+
        | user(): BelongsTo                   |
        +-------------------------------------+

        +-------------------------------------+
        | 🏷️ Taxonomy                         |
        +-------------------------------------+
        | id: int                             |
        | title: str                          |
        | body: text                          |
        | user_id: fk                         |
        +-------------------------------------+
        | user(): BelongsTo                   |
        +-------------------------------------+

        +-------------------------------------+
        | 🧩 Modules                          |
        +-------------------------------------+
        | id: int                             |
        | title: str                          |
        | body: text                          |
        | user_id: fk                         |
        +-------------------------------------+
        | user(): BelongsTo                   |
        +-------------------------------------+

        +-------------------------------------+
        | 📜 Log                              |
        +-------------------------------------+
        | id: int                             |
        | title: str                          |
        | body: text                          |
        | user_id: fk                         |
        +-------------------------------------+
        | user(): BelongsTo                   |
        +-------------------------------------+

        +-------------------------------------+
        | 🌐 Api Data                         |
        +-------------------------------------+
        | id: int                             |
        | title: str                          |
        | body: text                          |
        | user_id: fk                         |
        +-------------------------------------+
        | user(): BelongsTo                   |
        +-------------------------------------+

        +-------------------------------------+
        | 🗄️ Others                           |
        +-------------------------------------+
        | id: int                             |
        | title: str                          |
        | body: text                          |
        | user_id: fk                         |
        +-------------------------------------+
        | user(): BelongsTo                   |
        +-------------------------------------+

        +-------------------------------------+
        | 🔄 Pivot                            |
        +-------------------------------------+
        | id: int                             |
        | title: str                          |
        | body: text                          |
        | user_id: fk                         |
        +-------------------------------------+
        | user(): BelongsTo                   |
        +-------------------------------------+
```

## Moox Commands

### Moox Core

-   `php artisan moox:install` to install or update Moox packages
-   `php artisan moox:status` to show the status of Moox, including installed packages and entities
-   `php artisan moox:wire` to wire Moox Entities, Taxonomies and Modules

### Moox Build

-   `php artisan moox:build` to build a Moox package or Entity

### Moox Devlink

-   `php artisan moox:devlink` to symlink or locally wire packages for development
-   `php artisan moox:deploy` to unlink local packages and prepare for deployment

## Video

Use GIFs from the `art/video` folder. This is an example of a CLI video:

![Moox](../../art/video/test-cli-video.gif?raw=true)

It is 75% sized of the original video and compressed with [Squoosh](https://squoosh.app/) or [FreeConvert](https://www.freeconvert.com/gif-compressor), so 15 seconds of video are under 1MB.

```applescript
tell application "iTerm"
	activate
	tell the first window
		set bounds to {100, 100, 1200, 900}
		tell current session
			write text "cd ~/Herd/moox"
			write text "clear"
		end tell
	end tell
end tell
```

💡 This ensures that iTerm always launches at the exact size you want.

```applescript
tell application "Safari"
	if (count of windows) = 0 then
		make new document
	end if
	set bounds of front window to {100, 100, 1400, 900}
    set URL of front document to "https://www.moox.org"
end tell
```

> [!TIP]
> This ensures that Safari always launches at the exact size you want.
> Same possible for "Google Chrome", but we use Safari.

# We discuss docs here

-   https://docs.github.com/en/get-started/writing-on-github/getting-started-with-writing-and-formatting-on-github/basic-writing-and-formatting-syntax
-   Doing Screenshots
-   Adding Data and Images (Seeding) - use addMedia in the Factory
    -   https://thispersondoesnotexist.com/
    -   https://picsum.photos/
    -   https://unsplash.com/
