```
/*
* Created with https://www.css-gradient.com
* Gradient link: https://www.css-gradient.com/?c1=0a10ad&c2=00051b&gt=r&gd=dcr
*/
background: #0A10AD;
background: radial-gradient(at right center, #0A10AD, #00051B);

```

https://devdojo.com/tnylea/create-a-typing-effect-in-alpinejs - toc and typing

Learned so far:

-   I ship with polymorphic support, rarely used in Moox, but not much overhead
-   I use a Stub Model in Core as Default Placeholder when `/** @var \App\Models\Product $product */` does not already fix the problem
-   I may provide **helper functions like `Moox::getItemConfig('Product')`**, which abstracts the complexity and makes it feel **Laravel-native**. But my focus is on the Filament layer, I might not need.
-   I could use:
    `composer require --dev barryvdh/laravel-ide-helper`
    `php artisan ide-helper:models`
    but why should I, when the problem is fixed without ;-)

Definitions:

A Moox Package is basically a Laravel and PHP Package, means Composer Package, but we also count our VS Code Extension Pack and our Monorepo, a Laravel App.

A Moox Plugin is a Laraval package that uses the MooxServiceProvider that extends Spaties PackageServiceProvider. It is ready for additional configuration. It can be or have the following:

-   Item (Product, Post, Page) with optional
-   Relation (provides RelationManager)
-   Taxonomy (Category, Tag)
-   Module (Virtual, Bundle)
-   Theme (Theme Light)
-   Workflow (just an idea for now, Events, Jobs, Mails, Notifications)
-   Purpose (Jobs, Sync, Connect)

A Moox Entity is a Filament Resource including pages, migration, model, configuration and translation.

There are three Entity types:

-   Item - a Moox Item uses an Item trait from Moox Core like the Publishable Item, it can have a RelationManager, if the Entity is prepared for relations.
-   Taxonomy - a Moox Taxonomy ships with a create form, to inline-create new taxonomies.
-   Module - a Moox Module is able to extend any Item or Taxonomy (while most probably being useful for only one or two), it has a form, that is rendered as new tab for existing items.

They can be configured in every entity that supports relations, taxonomies and modules.

```php
return [
	"relations" => [
		"RelatedItem"
	],
	"taxonomies" => [
		"Category",
		"Tag"
	],
	"modules" => [
		"MoreItemFields"
	],

],
```

A SEO Module would be a nice thing, I guess.

Moox Navigation

Manage all Navigations on a website.

```php
return [
    'name' => 'Moox Theme Light',
    'navigation' => [
        'header' => 'Main Menu',
        'footer' => 'Footer Links',
    ],
];
```

Workflows would be a thing, I guess, a mix of Action, Trigger, Mailable in this case:

```php
return [
    "workflows" => [
        "StockUpdate" => [
            "applies_to" => ["Product"],
            "event" => "updated",
            "handler" => StockUpdateWorkflow::class
        ],
        "SendNewUserEmail" => [
            "applies_to" => ["User"],
            "event" => "created",
            "handler" => SendWelcomeEmailWorkflow::class
        ]
    ]
];

```

## Docs renderer config

```php

return [
	"Getting Started" => [
		"Introduction" => "/docs/Introduction.md",
		"Installation" => "/docs/Installation.md",
		"Updates" => "/docs/Updates.md",
		"Configuration" => "/docs/Configuration.md"
	],
	"Core Concepts" => [
		"Introduction" => "/docs/Core.md",
		"Entities" => "/docs/Packages/Core/Entity.md",
		"Items" => "/docs/Packages/Items",
		"Taxonomies" => "/docs/Packages/Core/Taxonomy.md",
		"Modules" => "/docs/Packages/Core/Module.md",
		"Workflows" => "/docs/Packages/Core/Workflow.md"
	],
	"Base Packages" => [
		"Introduction" => "/docs/BasePackages.md",
		"Search" => "/docs/Packages/Search"
		"Localization" => "/docs/Packages/Localization",
		"Frontend" => "/docs/Packages/Frontend",
		"Slugs" => "/docs/Packages/Slugs",
		"Navigation" => "/docs/Packages/Navigation",
		"Media" => "/docs/Packages/Media",
		"Packages" => "/docs/Packages/Packages",
		"JSON" => "/docs/Packages/Json",
		"Markdown" => "/docs/Packages/Markdown"
	],
	"Theming" => [
		"Introduction" => "/docs/Theming.md",
		"Themes" => "/docs/Packages/Themes",
		"Theme Light" => "/docs/Packages/ThemeLight",
		"Admin Theme" => "/docs/Packages/AdminTheme"
	],
	"Content" => [
		"Introduction" => "/docs/Content.md",
		"Pages" => "/docs/Packages/Page",
		"Posts" => "/docs/Packages/Posts",
		"News" => "/docs/Packages/News",
		"Taxonomy" => "/docs/Packages/Taxonomy",
		"Category" => "/docs/Packages/Category",
		"Tags" => "/docs/Packages/Tag",
	],
	"Press" => [
		"Introduction" => "/docs/Press.md",
		"Press" => "/docs/Packages/Press",
		"Press Wiki" => "/docs/Packages/PressWiki",
		"Press Trainings" => "/docs/Packages/PressTrainings"
	],
	"Community" => [
		"Introduction" => "/docs/Community.md",
		"Comment" => "/docs/Packages/Comment",
		"Rating" => "/docs/Packages/Rating",
		"Review" => "/docs/Packages/Review",
		"Discussion" => "/docs/Packages/Discussion",
		"Wiki" => "/docs/Packages/Wiki"
	],
	"Shop System" => [
		"Introduction" => "/docs/Shop.md",
		"Product" => "/docs/Packages/Product",
		"Virtual Product" => "/docs/Packages/VirtualProduct",
		"Subscription" => "/docs/Packages/Subscription",
		"Bundle" => "/docs/Packages/Bundle",
		"Add On" => "/docs/Packages/AddOn",
		"Category" => "/docs/Packages/ShopCategory",
		"Tag" => "/docs/Packages/ShopTag",
		"Customers" => "/docs/Packages/Customer",
		"Company" => "/docs/Packages/Company",
		"Cart" => "/docs/Packages/Cart",
		"Payment" => "/docs/Packages/Payment",
		"Wishlist" => "/docs/Packages/Wishlist"
	],
	"Users" => [
		"Introduction" => "/docs/Users.md",
		"User" => "/docs/Packages/User",
		"User Session" => "/docs/Packages/UserSession",
		"User Device" => "/docs/Packages/UserDevice",
		"Security" => "/docs/Packages/Security",
		"Login Link" => "/docs/Packages/LoginLink",
		"Passkey" => "/docs/Packages/Passkey",
		"Permission" => "/docs/Packages/Permission"
	],
	"Sending" => [
		"Introduction" => "/docs/Sending.md",
		"Mails" => "/docs/Packages/Mail",
		// Mail Templates, Monitor, ...
		"Notifications" => "/docs/Packages/Notification"
	],
	"System" => [
		"Introduction" => "/docs/System.md",
		"Audit" => "/docs/Packages/Audit",
		"Jobs" => "/docs/Packages/Jobs"
		"Connect" => "/docs/Packages/Connect",
		"API" => "/docs/Packages/Api"
		"Sync" => "/docs/Packages/Sync",
		"Scheduler" => "/docs/Packages/Scheduler"
	],
	"Tools" => [
		"Introduction" => "/docs/Tools.md",
		"Expiry" => "/docs/Packages/Expiry",
		"Trainings" => "/docs/Packages/Trainings",
		"Calendar" => "/docs/Packages/Calendar",
		"Booking" => "/docs/Packages/Booking",
		"Contact Form" => "/docs/Packages/ContactForm",
		"Contact Cards" => "/docs/Packages/ContactForm",
		"Analytics" => "/docs/Packages/Analytics",
		"Project" => "/docs/Packages/Project"
	],
	"Data" => [
		"Introduction" => "/docs/Data.md",
		"Data" => "/docs/Packages/Data",
		"Data Pro" => "/docs/Pro/Data"
	],
	"Icons" => [
		"Introduction" => "/docs/Icons.md",
		"Flags" => "/docs/Packages/Flags",
		"Files" => "/docs/Packages/Files"
	],
	"DevOps" => [
		"Introduction" => "/docs/Devops.md",
		"Devops" => "/docs/Packages/Devops",
		"GitHub" => "/docs/Packages/Github",
		"Packagist" => "/docs/Packages/Packagist",
		"Package Registry" => "/docs/Packages/PackageRegistry",
		"Forge" => "/docs/Packages/Forge",
		"Backup" => "/docs/Packages/Backup",
		"Restore" => "/docs/Packages/Restore",
		"Health" => "/docs/Packages/Health"
		"Backup Server" => "/docs/Packages/BackupServerUi"
	],
	"AI" => [
		"Introduction" => "/docs/Ai/Introduction.md",
		"Moox AI" => "/docs/packages/Ai",
		"Moox RAG" => "/docs/packages/Rag"
	]
	"Coding" => [
		"Introduction" => "/docs/Coding/Introduction.md",
		"Monorepo" => "/docs/Packages/Monorepo",
		"Builder" => "/docs/Packages/Builder",
		"Skeleton" => "/docs/Packages/Skeleton",
		"Devlink" => "/docs/Packages/Devlink",
		"VS Code" => "/docs/Packages/VSCode"
	],
	"Development" => [
		"Introduction" => "/docs/Development/Introduction.md",
		"Translation" => "/docs/Development/Translation.md",
		"Guidelines" => "/docs/Development/Guidelines.md",
		"Contributors" => "/docs/Development/Contributors.md",
		"Sponsors" => "/docs/Development/Sponsors.md"
	]
];


// Replacements
return [
	"Important" => "ImportantReplacer.php"
];

// Readme Generator
return [
	"Banner" => "BannerGenerator.php",
	"Title" => "TitleGenerator.php",
	"Description" => "DescriptionGenerator.php",
	"Capabilities" => "CapabilitiesGenerator.php",
	"Screenshot" => "ScreenshotGenerator.php",
	"Requirements" => "/docs/GettingStarted/Requirements.md",
	"Installation" => "/docs/GettingStarted/Installation.md",
	"Updating" => "/docs/GettingStarted/Updating.md",
	// can fallback to just print the config
	"Configuration" => "/packages/package/docs/Configuration(.md)",
	"Usage" => "/packages/package/docs/Usage(.md)",
	"Beginner" => "/packages/package/docs/Beginner(.md)",
	"Advanced" => "/packages/package/docs/Advanced(.md)",
	// Everything else would come after advanced, like credits ...
	"Credits" => "/packages/package/docs/Credits(.md)",
	"Classes" => "ClassesGenerator.php",
	"Models" => "ModelsGenerator.php",
	"Moox" => "/docs/GettingStarted/WhatIsMoox.md",
	"Development" => "/docs/Development/Development.md",
	"Changelog" => ...
	"Roadmap" => ...
	"Translation" => "/docs/Development/Translation.md",
	"Contributors" => "/docs/GettingStarted/Contributors.md",
	"Sponsors" => "/docs/GettingStarted/Sponsors.md",
	"Security" => "/docs/GettingStarted/Security.md",
	"License" => "/docs/GettingStarted/License.md"
];


```

https://filamentphp.com/plugins/tgeorgel-table-layout-toggle, Layout Switcher, see Press

-   See Lunar installer, get rid of Filament installer
-   https://filamentphp.com/docs/3.x/panels/installation#deploying-to-production - must implement!!!

```php
class User extends Authenticatable implements FilamentUser

{
	use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

	// rest of code...

	public function canAccessPanel(Panel $panel): bool
	{
		return $this->hasAnyRole(['super_admin', 'filament_user']);
	}
}
```

and ...???

```php
public function isSuperAdmin(): bool
{
	return in_array($this->email, config('auth.super_admins'));
}
```

## Ship Tables?

-   Moox Data Core

    -   Gender (Title, Slug, Icon, Active)
        -   Neutral
    -   Salutation

        -   None
        -   Ms.
        -   Mr.
        -   Mx.

    -   Honorific Title (Title, Slug, Icon, Active) - languages?
        -   Dr.
        -   Prof.
        -   Sir
        -   Dame
        -   Hon.
        -   Rev.

Salutation config

if ($salutation == 'none') {
$greeting = 'Dear ' . $firstname . " " . $lastname;
}

-   https://x.com/mooxphp sollte laufen, irgendwie automatisch mit https://github.com/atymic/twitter oder so? See https://chatgpt.com/c/67c1f1fa-c018-800c-8fae-e9f5f58627ea
-   GitHub schön machen
-   Neue Banner verteilen
-   [Auf der Filament webseite veröffentlichen](https://github.com/filamentphp/filamentphp.com/blob/main/README.md#contributing)
-   https://anystack.sh/ einrichten, 70% bleiben über Filament, sonst 85%

-   Global Installer - feature/installer
    -   Need another test, not audit
    -   But if a plugin fails, it should just turn red, not exit, so audit is good for that
    -   Use category maybe
    -   Category could have a default fallback category maybe, or use another one with seeder like data
    -   Can be merged without pain, if mayor packages don't fail
    -   Can be developed on web then
    -   Must be done in all packages, incl. Skeleton

## Moox jetzt

-   Package - what we don't know yet
    -   Dependencies - what tables are needed, for installer -> migration
    -   What exactly needs to be implemented, Assets, Panel, ....
    -   Installer needs to do that, too!
    -   So packages can implement an Installer class for that?

Pro - Split, Monorepo-Features
Panel für Devops in Moox

Skipping data: no composer.json found or invalid
Skipping localization: no composer.json found or invalid

-   Auto document

    -   Dependencies (php, the tables, npm): hard
    -   Commands: simple
    -   Entities: simple
    -   Other Classes: we'll see

-   Moox Status

    -   Installation Status
        -   PHP Version
        -   Laravel Version
        -   Filament Version
        -   Moox Version
        -   Moox Packages loaded
        -   Moox Packages installed
        -   Moox Packages need db update
    -   Devlink Status
        -   Link Status
        -   Update Status

-   Build
    -   Make Slug, Plural ...?
    -   Copy a SEP or entity
    -   Ask category, must be an enum in core, I guess
    -   Ask parent-theme, must use package service, only for themes
    *   Remove from MooxPackage in SP
        -   templateFor
        -   templateReplace
        -   templateRename
        -   templateSectionReplace
        -   templateRemove
        -   alternatePackage
    -   Do not copy composer.lock, vendor or build.php
    -   Do all in config, try first to port skeleton
    -   then simple and nested taxonomy
    -   then create items with relation
    -   then create module
    -   then copy theme
    -   find MSP only in /packages, not Skeleton
    -   Wire locally
    -   Finalize
-   Build iteration

    -   Delete Fields
        -   ID // needed
        -   Title // needed
        -   Slug // needed
        -   Description // suggested
        -   Image // suggested
        -   Color // optional
        -   Identify and delete in migration, model, resource
    -   Use all available Entities to copy
        -   From config to composer.json
        -   We now know the Entities
        -   config can now define "most wanted"
    -   moox:scaffold - change fields of an entity by a config or JSON
    -   moox:wire - connect relations, taxonomies and modules

-   Items builden
    -   Item
    -   Record
    -   Publish
    -   Draft
    -   Tag
    -   Category
    -   Module
-   Theme packages
    -   Components - Renderless Blade Components for Moox Frontend and Themes.
    -   Theme Base - Our Base Theme uses TailwindCSS and AlpineJS.
    -   Theme Light - Our Light Theme includes Alpine-Ajax and adds some styles.
    -   Theme heco - That is a private theme, we move it out here soon.
    -   Theme Moox - That is the theme for Moox.org.
    -   Slug - manages URL slugs and permalinks
    -   Page - will hold our pages and contents
    -   Frontend - Will tie everything together

1. Wire Themes, test one component renderless, then styled
2. Implement Theme Hierarchy in MooxService Provider
3. Document implementation
4. Build components
    1. https://blade-ui-kit.com/docs/0.x/alert
    2. https://mary-ui.com/docs/components/alert
    3. https://fluxui.dev/docs/installation
5. If packages work so far, build a loooot of packages.
    - Item
        1. ID
        2. Active - Bool
        3. Simple Status
        4. Title - String
        5. Slug - String
        6. Image - Media
        7. Description - Editor
        8. Data - JSON
        9. created_at
        10. updated_at
    - Archive Item from item
        1. ID
        2. Title - String
        3. Slug - String
        4. Image - Media
        5. Abstract - Text
        6. Description - Editor
        7. Open - Bool
        8. Data - JSON
        9. Select Example
        10. Radio Example
        11. Checkbox Example
        12. ULID
        13. created_at
        14. updated_at
        15. deleted_at
    - Publish Item - from publish item
        1. ID
        2. Simple Status
        3. Title - String
        4. Slug - String
        5. Image - Media
        6. Abstract - Text
        7. Description - Editor
        8. Open - Bool
        9. Markdown
        10. Color
        11. Select Example
        12. Radio Example
        13. Checkbox Example
        14. Data - JSON
        15. UUID
        16. ULID
        17. created_at
        18. updated_at
        19. deleted_at
        20. published_at
    - Module from item?
        1. ID
        2. Relation Type
        3. Relation
        4. some example fields from publish
    - Taxonomy
        - Taxonomies, Terms, Term_Taxonomies I guess
    - GitHub
        - Add most GH API fields, some as JSON
    - Packagist
        - Add most Packagist API fields, versions as JSON
    1. Package Registry
        1. ID
    2. Packages
        1. ID
    3. Markdown
    4. Theme Moox
    5. Theme heco
    6. Slug - new
        1. ID
    7. Frontend - new
        1. ID?
    8. Navigation
        1. ID
    9. Page - new
        1. ID
    10. News
        1. ID
    11. Build

-   Item Types

    -   Item - has a Resource with specific config, maybe RelationManager
    -   Taxonomy - has Resource with that create form I guess
    -   Theme - has views, has view loader in SP
    -   Module - has Resource Extender

-   Installer
    -   We need to check for Filament.
        -   Yep, that's good. Filament is installed.
    -   We need to check for Moox
        -   Great. Moox is installed.
    -   We need to check the current system status
        -   Migrations table vs. /migrations - will pam run?
        -   Moox Migrations vs. Real tables?
        -   Missing tables or changes from dependencies - Solution needed!
        -   Now handle all missing or outdated migrations, publish the most current
        -
        -   Whew! Moox is installed but needs an update!
            -   Show package status
            -   Do you want to update now? (skip preset and packages)
        -   Nice. Moox can be freshly installed. Show which packages.
            -   Show package status
            -   Do you want to add useful packages?
    -   Do you want to install a preset?
        -   Basic - no preset
        -   App - Users, Jobs, ...
        -   Project - App + Project
        -   Website - Page, Media, Users
        -   Shop - Product, Cart, Wishlist
        -   CMS - App, Website & Shop
        -   Press - including WordPress
        -   DevOps - Management Server
        -   Full - All Moox packages, perfect to dive
    -   Do you want to install packages?
        -   Installed
        -   Selected
        -   Suggested
        -   Other
    -   Packages will be installed ...
    -   Do you want to migrate databases?
    -   Databases will be migrated (diff)
    -   Do you want to seed databases?
        -   Static Data
        -   Damo Data
        -   Test Data
    -   Databases will be seeded (diff)
    -   Do you want to register Plugins?
    -   To which panel?
        -   Create a new Panel?
        -   Admin Panel
    -   Installing following Plugins ...

## Laravel 12

-   Fork: https://github.com/stechstudio/filament-impersonate -> Moox Impersonate
-   Fork: https://github.com/ryangjchandler/filament-progress-column -> Moox Progress
-   Fork: codeat3/blade-google-material-design-icons -> Moox Icons
-   Fork: adrolli/slug -> Moox Slug

## moox.json

That file plays a central role. It will be the place where package information is tied together, not just to automate the release process.

## composer.json of a package

```json
{
    "name": "moox/tag",
    "description": "This is my package audit",
    "keywords": ["Laravel", "Filament", "Filament plugin", "Laravel package"],
    "homepage": "https://moox.org/",
    "license": "MIT",
    "authors": [
        {
            "name": "Moox Developer",
            "email": "dev@moox.org",
            "role": "Developer"
        }
    ],
    "require": {
        "moox/core": "*",
        "spatie/laravel-activitylog": "^4.0"
    },
    "require-dev": {
        // use for dev dependencies
    },
    "suggest": {
        // use to suggest packages
    },
    "autoload": {
        "psr-4": {
            "Moox\\Audit\\": "src"
        }
    },
    "extra": {
        "moox": {
            "title": "Moox Tag",
            "released": true,
            "stability": "dev",
            "category": "taxonomy",
            "ships": {
                "item": false,
                "taxonomy": true,
                "module": false,
                "frontend": true,
                "theme": false,
                "specific": false,
                "custom": false
            },
            "used_for": ["system wide tags"],
            "template_for": ["flat taxonomies"],
            "template_rename": {
                "Category": "%%Taxonomy%%",
                "Categories": "%%Taxonomies%%",
                "category": "%%taxonomy%%",
                "categories": "%%taxonomies%%"
            },
            "alternate_packages": ["category", "taxonomy"]
        },
        "laravel": {
            "providers": ["Moox\\Audit\\AuditServiceProvider"]
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

## moox.json

Done by DB now, for release, but moox.json should be generated (with features, capabilities, ships, models, workflows, jobs, mails, classes for the rest..

```json
{
    "current": {
        "release": "4.1.2",
        "capabilities": ["This is a main feature"],
        "features": ["This is a feature"],
        "classes": ["This is a class"],
        "models": ["This is a model"],
        "resources": ["This is a resource"],
        "routes": ["This is a route"],
        "views": ["This is a view"]
    },
    "next": {
        "release": "4.2.0", // by release command
        "capabilities": ["This is a main feature"],
        "features": ["This is a feature"],
        "fixes": ["This is a commit message"],
        "classes": [
            "This is a class" // if changes
        ]
    }
}
```

This file should be used by AI as well as developers, to have an overview, it should be used to render the docs and packages sites of the moox.org website.

moox.json will be changed from

-   Base package info is manually edited: moox.json
-   PR and commit tracker later AI: moox_data.json
-   Statistics tracking is done to: moox_stats.json

Commit tracking is done with GH, yes. But we could do everything with Laravel and webhooks if needed.

-   CommitTracker
    -   Receives a webhook from GH
        -   Commit message
        -   Affected packages array
    -   Writes the result to moox_data.json
-   Release
    -   Can be invoked by ...
    -   Prepares changelogs etc.
    -   Webhooks GitHub
-   AI Collector
    -   Collects everything

AI collector and statistic tracking will run on moox.org. Moox.org can auto-commit these things. The PR and commit tracker could use a webhook and the release process can be invoked by web, cli or a webhook,,too.

## Track PR and commit messages

The title of a commit message or pull request is tracked. This is now done by simple Keywords - and please note the :

Fix: Changed visibility of something
Feature: New Upload-Section
Capability: Great things will happen

CI tracks affected packages.

The result is collected into a json file:

-   all
    -   Mayor: Great things will happen
-   core
    -   Fix: Changed visibility of something
    -   Feature: New Upload-Section
-   press
    -   ...

## DB Sync

To access the data from Moox (means Filament) I would simply mirror moox.json into a database as final step. That package should allow to declare which fields are writable from Filament like Description etc., I guess.

## Release

Releasing is a manual one-step process. Invoked with a command

`pa moox:release` or `pa moox:release --mayor`

that reads the json-file and suggests the packages to be released. It has a mayor option, that suggests the next mayor release number. That selection or mayor decision leads to the array of changed packages that should be released.

-   Add a next->release to the package in moox.json
-   Rename moox.json to moox-release.json
-   move added features, classes etc. from next to current
-   nuke the next section
-   save that as moox.json again.

Trigger the GitHub action, that should do the following:

For all packages in that release:

-   Add an image or media from
    -   /art/news/core-402.jpg
    -   /art/banner/core.jpg
    -   /package.jpg - should exist
    -   fallback to a common Moox Image
-   Craft the changelog entry

```Markdown

# Changelog

We note all changes for the package `Moox Core` here.

# 4.1.1

This minor release contains only bugfixes

Fixes
- Some thing

# 4.1.0

This release contains new features and some bugfixes.

Features
- Some thing

Fixes
- Some thing

# 4.0.0

This mayor release is compatible with Filament 4 and affects all Moox packages. It contains a lot of new features and fixes.

Features
- Some thing

Fixes
- Some thing

```

-   Split packages - Distribute the code into all affected packages.
-   If a package has no features and no fixes, the message should be "This is a compatibility release."
-   Follow the semantic versioning rules and craft a release of all affected packages.
-   Craft a Tweet, news for website, ...

Like these:

New Package Moox Page released ⭐
Use the short desc of the package and link to readme.

Moox Jobs 4.1.1 Release ⚙️
This minor release contains only bugfixes. See Changlog.

Moox Core 4.1 Release 🚀
This release contains new features like This and That. See Changelog.

Moox 4 Release 🚀
This mayor release is compatible with Filament 4 and affects all Moox packages. It contains a lot of new features and fixes. See Changelog.

... and finally delete moox-release.json

## Themes

Explicit or inherited theme hierarchy ... means when I use a parent theme that has already a parent, I am automatically grandchild, but when I define it explicitly in my config to have another theme hierarchy than my parent, I can override that behaviour.

or

Precedence:

-   Frontend (DB) config (can override everything)
-   Taxonomies implementation
-   Taxonomies self-definition

I want to build Frontend components using this stack

Laravel 11 - https://laravel.com/docs/11.x
Alpine JS - https://alpinejs.dev/
Alpine Ajax - https://alpine-ajax.js.org/
TailwindCSS 4 - https://tailwindcss.com/
PenguinUI - https://www.penguinui.com/ - as inspiration

I want to use the Alpine-Ajax preferred method of progressive enhancement. The whole frontend should work with normal page reloads when JS is not available. That allows me to also offer an accessible version that might be much more that just a no-JS version.

Themes should be the Manager for Frontend Themes.

A theme must do following:

-   Define the routing for Moox Frontend (I guess, needed using React?)
-   Generate a frontend including pages, taxonomies and a homepage as minimum req, and things like products etc
-   Define it's CSS or TailwindCSS
-   Have access to Moox Layouts, that ships website layouts, header and footer layouts as well as print layouts

while there could be following Themes available:

-   Light Theme: https://alpine-ajax.js.org/, featherlight
-   Plain Theme: just a basic output in plain HTML
-   Wire Theme: Livewire Default or with https://mary-ui.com/
-   React Theme ... for Laraverse

I am working on Moox. As Moox Builder should be able to generate Frontends, I want to implement this part of the Framework. I would do that in an own package, called Moox Frontend.

Moox Frontend should:

-   provide the frontend routing for resources that use Moox Frontend
    -   Must have a Slug field or use Moox Slug
    -   Must have a uuid field
    -   Must have a frontend configuration
    -   Must use the HasFrontend trait
-   read the config (unique route slug, simple or nested taxonomies, defined theme) to know how to render
-   care for resolving naming clashes when two resources share the same slug, like the empty slug
-   have a Preview feature, to visit unpublished or soft-deleted items using temporary URLs
-   do the Caching, as well as serve Static HTML, finally Publishing on CDN

Packages to create:

-   Moox Frontend
-   Moox Page
-   Moox Slug
-   Moox Theme Light - Alpine-Ajax and TailwindCSS

-   Moox Slug Pro
-   Moox Post
-   Moox Comment
-   Moox Media
-   Moox Locale
-   Moox Localize
-   Moox Layout - Site, Page, Header, Footer, Navigations
-   Moox Blocks - first kind of Block editor just using Filament Builder, see https://filamentphp.com/docs/3.x/forms/fields/builder
-   Moox Blocks Pro
-   Moox Themes

https://github.com/mcamara/laravel-localization

```
https://www.example.com:8080/some/page?search=apple#section2
\______/ \___________/ \__/\______/ \_________/ \_________/
 scheme      host       port path      query      fragment
```

```php

	// frontend.php

	// In TYPO3, I can just add new domains related to pages
	// I want to have that, too. Otherwise fall back here.
	'base' => 'www.moox.org', // ENV APP_URL

	// can be defined, but that would be better in UI / DB
	'canonical' => [
		'moox.org',
	]
	// using languages, should also work in UI / DB
	'canonical' => [
		'de' => 'moox.de',
	]

	// localize.php

	// EN could be en.moox.org, moox.org, moox.org/en
	// DE could be de.moox.org, www.moox.de, moox.org/de
	// or de-ch.moox.org for language and country
	'localize' => [
		'type' => 'path' // domain, subdomain
		'slug' => 'language' // lang-country, country
		'mode' => 'semi-auto' // auto, manual
		'storage' => 'session' // cookie
	]

	// cms.php

	'url_path' => [
		'page' => '/{slug}',
		// posts are categorized (nested)
		'post' => '/{category.slug}/{slug}',
		// scoped caategories, nested
		'post.category' => '/{parent.slug}/{slug}',
		// scoped tags
		'post.tag' => '/tag/{slug}',
		// comments are with their posts
		'post.comment' => '/{post.slug}/comment/{year}/{slug}',
	]

	// tag.php

	'url_path' => [
		// this default would be overwritten?
		'tag' => '/tag/{slug}',
	]

	// comment.php

	'url_path' => [
		// overridden, just for posts, not for products
		'comment' => '/{item}/comment/{year}/{month}/{slug}',
	]

	// shop.php

	'url_path' => [
		'product' => '',
	]

	// these must be handled
	{slug} // the current item's slug
	{item.slug} // the related item's slug
	{year}/{month}/{day} // date related
	{parent.slug} // the parent item's slug
	{category.slug} // refers to a category's slug
	{post.slug} // refers to a post's slug



```
