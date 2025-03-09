# Documentation

Documentation is the place to learn about Moox. So it is important to keep it up to date, complete and interesting.

To be rendered on our awesome website, it needs to follow some rules.

## Markdown

Use GitHub Flavored Markdown (https://docs.github.com/en/get-started/writing-on-github/getting-started-with-writing-and-formatting-on-github/basic-writing-and-formatting-syntax), specially the following:

```markdown
> [!TIP]
> Use this for tips (!TIP), notes (!NOTE), warnings (!WARNING),
> and really important things (!CAUTION).
```

## Sections for packages

Use only the following files for the docs of a package:

-   `art/banner/package.jpg` - The banner of the package
-   `docs/package/README.md` - The first part to be rendered, just a short description
-   `Requirements`, `Installation` are rendered automatically
-   `Features.md` - The features of the package, short concise list
-   `art/video/package.mp4` - A video of the package in action
-   `art/video/package.gif` - A GIF of the package in action
-   `art/screenshot/package.jpg` - Main Screenshot, preferably dark
-   `Usage.md` - A detailed guide how to use the package
-   `Beginner.md` - An optional section for beginners
-   `Advanced.md` - An optional section for advanced users
-   `Changelog`, `Security`, `Contributing` ... will be rendered automatically

## Screenshot and Video

Screenshots and videos should be done on a Mac, using SnagIt and the following AppleScripts for CLI and Browser:

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
> This ensures that iTerm and Safari always launch at the exact size you want.
> Same possible for "Google Chrome", but we use Safari.

## File Structure

Directory structure of a package:

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
