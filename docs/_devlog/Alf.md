# Devlog

## Todo

Build now uses the new API from SP:
That is the rest of the old config:

```php
'entity_files' => [
    'config/publish-item.php',
    'database/factories/PublishItemFactory.php',
    'database/migrations/create_publish_items_table.php.stub',
    'resources/lang/*/translations.php',
    'src/PublishItemPlugin.php',
    'src/Models/PublishItem.php',
    'src/Resources/PublishItemResource.php',
    'src/Resources/PublishItemResource\Pages\CreatePublishItem.php',
    'src/Resources/PublishItemResource\Pages\EditPublishItem.php',
    'src/Resources/PublishItemResource\Pages\ListPublishItems.php',
    'src/Resources/PublishItemResource\Pages\ViewPublishItem.php',
    // TODO: Add RelationManager, if exists
],
```

-   [ ] Implement Frontend class, abstract? See Frontend/Idea.md
-   [ ] Build Skeleton -> Item
-   [ ] Refactor core traits to base classes
-   [ ] Create Item
    -   [ ] with new base classes
    -   [ ] Implement Relations class
    -   [ ] Implement Module class
    -   [ ] Implement Frontend class
-   [ ] Build ...
    -   [ ] Ask category, must be an enum in core, I guess
    -   [ ] Ask parent-theme, must use package service, only for themes
    -   [ ] Do not copy composer.lock, vendor or build.php
    -   [ ] Do all in config, try first to port skeleton
    -   [ ] then simple and nested taxonomy
    -   [ ] then create items with relation
    -   [ ] then create module
    -   [ ] then copy theme
    -   [ ] find MSP only in /packages, not Skeleton
    -   [ ] Wire locally
    -   [ ] Finalize
-   [ ] Web: Skipping data: no composer.json found or invalid
-   [ ] Web: Skipping localization: no composer.json found or invalid
-   [ ] See Lunar and Filament before doing commands
    -   [ ] Refactor traits to Has and Can, see Filament
    -   [ ] Install filament with our own user, see Lunar
    -   [ ] Add our own Panels, not the default from Filament
-   [ ] Global Installer - feature/installer
    -   [ ] Need another test, not audit
    -   [ ] But if a plugin fails, it should just turn red, not fail, so audit is good for that
    -   [ ] We need to find all dependencies, all Laravel, all with migrations and seeders
    -   [ ] We need to check for Filament (e. g. canAccessPanel and isSuperAdmin, see [here](https://filamentphp.com/docs/3.x/panels/installation#deploying-to-production))
    -   [ ] Packages might need an installer class? What needs to be installed?
        -   [x] Migrations and seeders
        -   [ ] Needed Configuration?
        -   [ ] Needed Assets?
        -   [ ] Routes to register
        -   [ ] Views ...
-   [ ] Split by moox.json
-   [ ] Moox Pro: Split and Monorepo-Features
-   [ ] Panel für Devops in Moox
-   [ ] Move Forge from DevOps, DevOps just require for now
-   [ ] Add [Table Layouts](https://filamentphp.com/plugins/tgeorgel-table-layout-toggle) to Media
-   [ ] Fork: https://github.com/stechstudio/filament-impersonate -> Moox Impersonate
-   [ ] Fork: https://github.com/ryangjchandler/filament-progress-column -> Moox Progress
-   [ ] Fork: codeat3/blade-google-material-design-icons -> Moox Icons
-   [ ] Fork: adrolli/slug -> Moox Slug
-   [ ] Build iteration
    -   [ ] Delete Fields
        -   [ ] ID, Title, Slug are needed
        -   [ ] Description, Image are suggested
        -   [ ] Color is optional
        -   [ ] Identify and delete in migration, model, resource
    -   [ ] Use all available Entities to copy
        -   [ ] From config to composer.json
        -   [ ] We now know the Entities
        -   [ ] config can now define "most wanted"
    -   [ ] Wire Themes, test one component renderless, then styled
    -   [ ] Implement Theme Hierarchy in MooxService Provider
    -   [ ] Document implementation
    -   [ ] Build components
        -   [ ] https://blade-ui-kit.com/docs/0.x/alert
        -   [ ] https://mary-ui.com/docs/components/alert
        -   [ ] https://fluxui.dev/docs/installation
-   [ ] Moox Commands
    -   [ ] moox:status
        -   [ ] PHP Version
        -   [ ] Laravel Version
        -   [ ] Filament Version
        -   [ ] Moox Version
        -   [ ] Moox Packages loaded
        -   [ ] Moox Packages installed
        -   [ ] Moox Packages need db update
        -   [ ] Devlink Status / Tables need verbose
    -   [ ] moox:wire - connect relations, taxonomies and modules
    -   [ ] moox:scaffold - change fields of an entity by a config or JSON
    -   [ ] moox:release or UI - auto release, see notes
        -   [ ] We need to see if we use moox.json, DB or both.

## Relations

-   I ship with polymorphic support, rarely used in Moox, but not much overhead
-   I use a Stub Model in Core as Default Placeholder when `/** @var \App\Models\Product $product */` does not already fix the problem
-   I may provide **helper functions like `Moox::getItemConfig('Product')`**, which abstracts the complexity and makes it feel **Laravel-native**. But my focus is on the Filament layer, I might not need.
-   I could use:
    `composer require --dev barryvdh/laravel-ide-helper`
    `php artisan ide-helper:models`
    but why should I, when the problem is fixed without ;-)

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

## Workflows

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

## Items

-   Item
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
-   Archive Item from item
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
-   Publish Item - from publish item
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

## Installer

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

## Auto Release

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

## Localization

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
