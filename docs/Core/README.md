# Core

The core package is the foundation of the Moox framework. It contains the basic functionality that is used by all other packages.

## Entity Structure

All Moox Entities follow the same file and folder structure.

```plaintext
📦 package/
  |
  └── 📂 config/
  |   └── 📂 entities/
  |       └── 📜 entity.php
  |
  └── 📂 resources/
  |   └── 📂 lang/
  |   |   └── 📂 en/
  |   |       └── 📂 entities/
  |   |           └── 📜 entity.php
  |   └── 📂 views/
  |       └── 📂 entities/
  |           └── 📂 entity/
  |               ├── 📜 card.blade.php
  |               ├── 📜 row.blade.php
  |               ├── 📜 table.blade.php
  |               ├── 📜 form.blade.php
  |               ├── 📜 index.blade.php
  |               ├── 📜 show.blade.php
  |               ├── 📜 create.blade.php
  |               ├── 📜 edit.blade.php
  |
  └── 📂 src/
      └── 📂 Moox/
         └── 📂 Entities/
          |  └── 📂 Items/
          |   |   └── 📜 ExampleItem.php
          |   |   └── 📂 ExampleItem/
          |   |   ├── 📂 Pages/
          |   |   ├── 📂 Widgets/
          |   |       ├── 📂 RelationManagers/
          |   |       ├── 📂 Relation/
          |   └── 📂 Taxonomies/
          |   |   └── 📜 ExampleTaxonomy.php
          |   |   └── 📂 ExampleTaxonomy/
          |   |       ├── 📂 Pages/
          |   |       ├── 📂 Widgets/
          |   |       ├── 📂 RelationManagers/
          |   |       ├── 📂 Relation/
          |   |       ├── 📂 Forms/
          |   └── 📂 Modules/
          |   |   └── 📜 ExampleModule.php
          |   |   └── 📂 ExampleModule/
          |   |       ├── 📂 Widgets/
          |   |       ├── 📂 Extender/
          └── 📂 Panels/
          |   └── 📜 PackagePanel.php
          └── 📂 Plugins/
          |   ├── 📜 PackagePlugin.php
          |   ├── 📜 EntityPlugin.php
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

### Moox Build

-   `php artisan moox:build` to build a Moox package or Entity
-   `php artisan moox:entity` to apply fields to a Moox Entity
-   `php artisan moox:wire` to wire Moox Entities, Taxonomies and Modules

### Moox Devlink

-   `php artisan moox:link` to symlink or locally wire packages for development
-   `php artisan moox:unlink` to unlink local packages and prepare for deployment

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
