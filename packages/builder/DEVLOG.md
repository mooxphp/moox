# Moox Builder Devlog

This is the current state of the Builder:

-   Moox Builder is currently a GitHub Template Repository (will be removed) and now working as an installed package (will be the future)
-   The current state is in this branch: https://github.com/mooxphp/moox/tree/feature/tag
-   The `php artisan builder:create`command is working, tested with simple and published item yet
-   A Panel is available to Preview: https://moox.test/builder
-   There are 5 test entities in the package, that will be deleted including their config, when builder is able to generate them without errors
    -   https://moox.test/moox/simple-items
    -   https://moox.test/moox/publishable-items
    -   https://moox.test/moox/full-items
    -   https://moox.test/moox/simple-taxonomies
    -   https://moox.test/moox/nested-taxonomies

## Todo

- [ ] The create command has (most probably in the Service Layer) flaws:

  -   [ ] First creation works, but the files array in the build table was empty, we just fixed that. We should use the files array to be aware, which files have been generated before. It would probably be a good idea, to delete the files before creating the new ones, using this array. The same service and config array should be used by the delete entity command and UI delete actions later.

  -   [ ] The generated Resource has no use statements and causes the platform to 500

  -   [ ] After manually deleting the generated files, regeneration fails not finding a build for the entity (but it is there and active)

  -   [ ] We refactored PreviewManager Service (deprecated) to PreviewTableService. The command should use the new service for creating previews

- [ ] We need to refactor the service layer until all services have a clear single responsibility

  -   [ ] MigrationFinder has direct file operations: These should be delegated to FileManager.
  -   [ ] EntityDeleter and FileCleanup overlap, we need to discuss this first, as deletion is a bit more complex ...
  -   [ ] EntityRebuilder isn't extending AbstractEntityService or ContextAwareService, which seems inconsistent with our architecture
  -   [ ] We need to document the new services (like fileformatter, previewtablemanager) and their (single) responsibilty, and remove deleted service classes (like previewmanager, ...)

- [ ] The create command should do preview "migrations" using DB directly, I commented out migrations in the contexts config array

- [ ] The create command should have a new option --migration= to make use of the migration generator

- [ ] We need to implement Sections ... see Chat on that

- [ ] DeleteCommand has flaws, leaves files, and the db? Not as described in README, it should delete all empty folders to stay clean

- [ ] Author for example needs to know which User model, we need to find out or ask on installation, so the blocks need to have a definition for this

- [ ] The `AbstractBlock`is a pure mess. As it is used as blueprint for developers, it is a pain to find out how to implement blocks. But simply reordering the class does not work because of inheritance chains in methods.

- [ ] Need to change the installer to scan for installable plugins, done in builder itself and the template, not tested yet

- [ ] builder_entities table has a build_context field that contains preview, app or package ... but we need to handle that a bit different, as an entity can be generated in a package or in app, then developed, means versioned, and previewed. That means we can have the entity built in only one context plus preview. I would add a new field to entities named `previewed`that states a preview is currently active, while the `build_context`field should only reflext app or package.

  When an entity is built in preview context, the previewed field should be true, when we delete the preview false

  For builds in app or package context: when created, fill build context, when deleted, empty build context. Do not allow an entity to be built in app and package at a time.

  There's a big difference between really deleting the entity and removing a built!

- [ ] Configurable FeatureSet

  -   [ ] Filament Core Features

  -   [ ] Moox Core Features

  -   [ ] Community Features (not yet implemented

- [ ] Configurable Presets

  -   [ ] Add Shop, Blog etc.

- [ ] Some of the Blocks are not working as they miss traits, methods ... need to iterate

- [ ] Config (Tabs etc.) and translations are generated, not tested (may be not wired correctly)

- [ ] Need to generate Tabs, Taxonomy and Relations partials, may already work partially

- [ ] The Package Builder is completely prepared (Templates, Config, Generators, Services and Commands), but the last three are mostly empty files. Needs to be implemented.

- [ ] Require Pint, what about Larastan?

- [ ] Add more Moox Blocks

  -   [ ] https://github.com/lucasgiovanny/filament-multiselect-two-sides - for Builder
  -   [ ] ResourceLinkTable - https://www.youtube.com/watch?v=bjv_RiBUtNs
  -   [ ] Most wanted like Phone, Address etc.

- [ ] Moox Core Features need to be refactored to be able to generate them without issues, eliminate methods and move to traits
  -   [ ] getResourceName should be auto detected
  -   [ ] Currently new Packages need to register in core to use TranslatableConfig, that was not my best idea
  -   [ ] Relations, like Taxonomies, but "on the left"
  -   [ ] Publish
      -   Publish Button is shown on already published items
      -   Should then be save only
      -   There could be a create new draft for published?
      -   Preview URL feature ... https://youtu.be/bjv_RiBUtNs?si=cellheQYyxhiHxRg&t=167 ... by Spatie
  -   [ ] Relations like Taxonomies, and what about Relationsmanagers?
  -   [ ] Move to Core
      -   Moox Builder Packages should be cleaned up as much as possible
      -   Installer: use Abstract, Service or Traits ...
      -   ServiceProvider: Abstract PackageTools to be able to add PanelProvider etc. to main function

- [ ] Builder needs to be cleaned up after able to generate packages
  -   [ ] Cleanup config
  -   [ ] Remove old entities
  -   [ ] Remove build.php
  -   [ ] Remove GH Template

- [ ] We need to generate factories from blocks to entities

- [ ] We need to generate tests

- [ ] Versions need a concept, needs a table (and UI)

- [ ] Versions vs. Updates (means Maintenance ... if we could update code using PHP Parser, we also could update code in terms of keeping the generated code of builder plugins auto-maintained)

- [ ] Generate the Builder UI, let Builder build itself

- [ ] Generate a Frontend

- [ ] Idea: https://docs.larallama.io/, would be able to generate based on a prompt or add complex features?

- [ ] Idea: https://github.com/nikic/PHP-Parser, would be able to update even custom code?

- [ ] Idea: Install a Builder Platform with lot's packages and Builder. For each user, create a full-fledged PanelProvider as Preview (for Demo, for SaaS?)

- [ ] Core Docs

  -   Naming convention InModel InResource InPages and Single for single-use traits

  -   TabsInResource - contains TODO

  -   TabsInPage - just getTabs needs to be defined

  -   TaxonomyInPages - needs that mount method in ViewPage

  -   AuthorInModel

  -   AuthorInResource

  -   StatusInModel

  -   StatusInResource - WIP

  -   Links to builder or builder doc inside

- [ ] Category / Tag Docs

  -   Provides a powerful hierarchical Category system, based on Nested Set and highly configurable Filament resources to build.
  -   https://github.com/lazychaser/laravel-nestedset
  -   https://github.com/CodeWithDennis/filament-select-tree, does need `php artisan filament:assets
  -   Screens
  -   Usage / Config

- $livewire->saveAndCreateAnother(); error, auch in Tags und Builder?

- Relationships - in builder but like taxonomies

- Add fields and features: https://chatgpt.com/c/67180a73-d4e8-800c-b37a-0fa822555a11

- Meta, see "add fields and features Chat" for JSON, EAV, Polymorphic or [Spatie](https://github.com/spatie/laravel-schemaless-attributes) , currently tending to JSON + Polymorphic

- HasSlug has been removed from the model, as long as Moox Slug is not ready, dependency to Spatie slug is where to do?

- Item could show last changed etc. on the left ...

- Gallery images should be sortable

- Bulk restore does not work

- Set indices for slug etc, or not?

- not Cascade (for taxonomies) specially? Cascade is most of the times not a good idea, configurable?

- If plugin data-language -> migration create_languages_table ->
  SP: ->hasMigration('create_data_languages_table') (correct the -)
  -   Install Script like Breezy - https://github.com/jeffgreco13/filament-breezy/blob/2.x/src/Commands/Install.php
  -   Livewire Frontend
  -   Permissions - https://laracasts.com/discuss/channels/laravel/policies-in-packages
  -   Dashboard Widgets https://github.com/Flowframe/laravel-trend and https://github.com/leandrocfe/filament-apex-charts
  -   Im and Export, see https://github.com/pxlrbt/filament-excel and https://github.com/eighty9nine/filament-excel-import or https://github.com/konnco/filament-import
  -   PDF see https://laraveldaily.com/post/filament-export-record-to-pdf-two-ways or https://tapansharma.dev/blog/a-guide-to-work-with-pdf-generation-in-filamentphp

- Inline-Help



## Packages

This config was in the Package and is currently missing: I depends to Blocks means to Entities, Blocks need to be able to generate config for entities.

    // New Block: Simple Item Types
    
    /*
    |--------------------------------------------------------------------------
    | Item Types
    |--------------------------------------------------------------------------
    |
    | This array contains the types of items entities. You can delete
    | the types you don't need and add new ones. If you don't need
    | types, you can empty this array like this: 'types' => [],
    |
    */
    
    'types' => [
        'post' => 'Post',
        'page' => 'Page',
    ],
    
    // Existing Author Block
    
    /*
    |--------------------------------------------------------------------------
    | Author Model
    |--------------------------------------------------------------------------
    |
    | This sets the user model that can be used as author. It should be an
    | authenticatable model and support the morph relationship.
    | It should have fields similar to Moox User or WpUser.
    |
    */
    
    'author_model' => \Moox\User\Models\User::class,
    
    // Slug Block - not implemented yet
    
    /*
    |--------------------------------------------------------------------------
    | Allow Slug Change - WIP
    |--------------------------------------------------------------------------
    |
    | // TODO: Work in progress.
    |
    */
    
    'allow_slug_change_after_saved' => env('ALLOW_SLUG_CHANGE_AFTER_SAVED', true),
    'allow_slug_change_after_publish' => env('ALLOW_SLUG_CHANGE_AFTER_PUBLISH', false),

Current Status:

-   Files are prepared: Service, Generators, Templates and Commands
-   Config is prepared
-   Install Template and Readme are not finished, as well as their partials
-   All templates could be completely prepared
-   Then we could go for the Generators
-   Then the Services
-   Finally the Commands
-   Test and bring it live ... needs to install in a new Laravel to completely polish

I want to generate Packages using Moox Builder, it should work like this:

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



## The UI

Some early thoughts:

-   Package

    -   Name
    -   Namespace (config, default to moox)
    -   Description (config, default to This is my Laravel package XXX YYY made with Moox Builder.)
    -   Author (config, default to Moox Devs)
    -   Website (config, default to https://www.moox.org)
    -   E-Mail (config, default to devs@moox.org)
    -   Status Development (no Entities, no Preview), Installable (built), Installed (Composer)
    -   HasMany Entities
    -   HasMany Versions

-   Entity
    -   Singular
    -   Plural
    -   Description
    -   BelongsTo Package
    -   Preset (Simple Item, Publishable Item, Full Item, Simple Taxonomy, Nested Taxonomy), prefills Blocks
    -   Relations (like Taxonomies, not implemented yet)
    -   Taxonomies (Categories, Tags, Custom)
    -   HasMany Tabs
    -   HasMany Blocks
    -   BelongsTo Package (nullable, can be App Context)
    -   HasMany Builds
-   Blocks
    -   Title
    -   Description
    -   Options (Required, Toggleable, more?)
    -   Type - Select Block Class
    -   BelongsTo Entity
-   Versions
    -   BelongsTo Package
    -   Data (could be a all fields JSON)
-   Builds
    -   BelongsTo Entity
    -   Version (probably stored, no relation)
    -   Data (could be a all fields JSON)
    -   Files (JSON)

## New Builder

This is a first idea of the future description for Moox Builder:

What do you want to ~~build~~ ship today?

From idea to a working App in Minutes. No coding.

Start from scratch, use a Preset or an existing migration.

Moox Builder is a Laravel Package and Filament UI to build

-   Filament Resources, complete Entities including
    -   Migration
    -   Model
    -   Filament Resource
    -   Filament Resource Pages
    -   Configuration
    -   Translations
    -   Factory
    -   Pest Test
    -   Features like Softdelete, Publish, Author
    -   Relations can be simply configured, no coding in Model
    -   Support for simple, nested and custom Taxonomies
    -   Extremely simple to extend using Metadata (JSON)
    -   Or with an Entity-Attribute-Value (EAV, like WP does)
    -   Or with an direct Extender (adding fields to the table)
-   Laravel Packages, that can hold these Entities, including
    -   ServiceProvider
    -   README, LICENSE, SECURITY MD-files
    -   Composer.json
    -   Publishable Config
    -   Installer
    -   Gitignore
    -   TestCase, ArchTest, Package Test
    -   Translations

You can preview Entities instantly and then build them in a Package or directly in the App.

All generated code is

-   Typesave?
-   Strict?
-   PHP Stan Level?
-   Pint Fixed
-   Pest Tested?

So pushing this code into a Repository with highest Quality Gates will work without tears.

Remove Builder, stay with working Code. No dependency.

Create your own Builder: add own Templates, Generators, Presets, Blocks and modifiy the Builder config to your needs.
