# Moox Builder DEVLOG

We work on these tasks in order from top to bottom:

## Tasks

### General (WIP)

From publishable item we learn that TitleWithSlug should implement it's own section, it currently seems to add a comma, why?
Another issue is that the List page is missing the eloquent builder use statement, why?
After fixing that, the list page has other issues too.
Tabs are not generated from the Publish block, should overwrite Tabs block

We need to document the new section API and section classes.

And we need to fix the issue with the FullItem, maybe we need a new way to handle relations like author, user, etc.
Failed to create entity: SQLSTATE[HY000]: General error: 1824 Failed to open the referenced table 'authors' (Connection: mysql, SQL: alter table `preview_preview_full_items` add constraint `preview_preview_full_items_author_id_foreign` foreign key (`author_id`) references `authors` (`id`) on delete cascade)

### Entity

-   [WIP] We currently work on generating Presets in Preview Context and optimize the generated resources
    -   [WIP] PreviewSimItem is working like a charm including actions, filters, bulk actions and taxonomies
        -   [ ] Delete does not delete the record
        -   [ ] Status field is not working as expected, completely weird behavior
        -   [ ] Uniqueness is not implemented or not used?
        -   [ ] Taxonomies needs to be tested, TaxonomyInPages has issues
        -   [ ] Page / AbstractPage generators need section-based API implementation?
        -   [ ] Relations needs
            -   [ ] to be implemented first, because relations is a bit different to taxonomies
            -   [ ] to be generated in the Resource
            -   [ ] to be generated in the Config
    -   [WIP] PreviewPubItem
        -   [ ] We need to bring this on the Simple Item level first
            -   [ ] Tabs needs to be implemented and should replace the simple tabs
            -   [ ] Taxonomies needs to be tested, TaxonomyInPages has issues
            -   [ ] TitleWithSlug should implement it's own section
            -   [ ] Relations
            -   [ ] Actions need to work as expected
        -   [ ] We need to work on the publish feature with custom actions, see https://youtu.be/bjv_RiBUtNs?si=cellheQYyxhiHxRg&t=167
        -   [ ] Then we need to implement the relation feature
    -   [WIP] PreviewFullItem
        -   [ ] We need to bring this on the Publish Item level first
        -   [ ] We need to work on all existing blocks and generate theme here
        -   [ ] Maybe add the three widgets here, needs wiget-generator and template?
        -   [ ] Then we need to implement the relation feature
    -   [ ] PreviewSimTax
        -   [ ] We need to bring this on the Simple Item level first
        -   [ ] Then we need to implement the soft delete feature
        -   [ ] Then it need Tag specific implementation
    -   [ ] PreviewPubTax
        -   [ ] We need to bring this on the Publish Item level first
        -   [ ] Then we need to implement the soft delete feature
        -   [ ] Then it need Category specific implementation with nested set
-   [ ] Iterate over all blocks, presets and contexts to find out if they are working as expected
-   [ ] Moox Core Features need to be refactored to be able to generate them without issues, eliminate methods and move to traits
    -   [ ] Publish feature seems to miss the save method
    -   [ ] Relations, like Taxonomies, but "on the left"
    -   [ ] Relations like Taxonomies, and what about Relationsmanagers?
    -   [ ] Naming convention InModel InResource InPages and Single for single-use traits
    -   [ ] TabsInResource - contains TODO
    -   [ ] TabsInListPage - just getTabs needs to be defined
    -   [ ] TaxonomyInPages - needs that mount method in ViewPage
-   [ ] Refactor DeleteCommand to use new services
-   [ ] Add --migration option to create command
-   [ ] Would Builder now be able to generate itself based on the current migrations?
-   [ ] How would we generate a complete different type of resource, like a Media Manager? The only thing we need is a different table, switching to a grid.

-   All Blocks need to be updated
    -   [ ] Toggleable option like in Text
    -   [ ] Filterable option like in Text, and filterable needs to be implemented in ResourceGenerator (only generate filters if filterable is true)
    -   [ ] The new section API

### Merge and Release

-   [ ] Fix custom package config and translations (see /press)
-   [ ] Merge into main
-   [ ] Test locally and on moox.org
-   [ ] Release core
-   [ ] Release builder and all packages

### Docs and Core Extras

-   [ ] Category / Tag Docs
    -   Provides a powerful hierarchical Category system, based on Nested Set and highly configurable Filament resources to build.
    -   https://github.com/lazychaser/laravel-nestedset
    -   https://github.com/CodeWithDennis/filament-select-tree, does need `php artisan filament:assets
    -   Screens
    -   Usage / Config
-   [ ] Add fields and features: https://chatgpt.com/c/67180a73-d4e8-800c-b37a-0fa822555a11
-   [ ] Meta, see "add fields and features Chat" for JSON, EAV, Polymorphic or [Spatie](https://github.com/spatie/laravel-schemaless-attributes) , currently tending to JSON + Polymorphic
-   [ ] HasSlug has been removed from the model, as long as Moox Slug is not ready, dependency to Spatie slug is where to do?
-   [ ] Item could show last changed etc. on the left ...
-   [ ] Gallery images should be sortable
-   [ ] Bulk restore does not work
-   [ ] Set indices for slug etc, or not?
-   [ ] not Cascade (for taxonomies) specially? Cascade is most of the times not a good idea, configurable?

### Restoring entities

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

### Ideas

-   [ ] Tests
    -   [ ] Write tests for all services
    -   [ ] Write tests for all generators
    -   [ ] Write tests for all commands
    -   [ ] Write build tests using presets, blocks and contexts
    -   [ ] Generate Factories from Blocks
    -   [ ] Generate Tests from Blocks
-   [ ] Implement App Generator command
    -   [ ] Add (Moox) Packages to composer.json
    -   [ ] Create PanelProvider
    -   [ ] Create Installer
    -   [ ] Create Readme
-   [ ] Implement Frontend generator command
-   [ ] We need to implement Sections ... see Chat on that
-   [ ] Author for example needs to know which User model, we need to find out or ask on installation, so the blocks need to have a definition for this
-   [ ] Install Script like Breezy - https://github.com/jeffgreco13/filament-breezy/blob/2.x/src/Commands/Install.php
-   [ ] Permissions - https://laracasts.com/discuss/channels/laravel/policies-in-packages
-   [ ] Dashboard Widgets https://github.com/Flowframe/laravel-trend and https://github.com/leandrocfe/filament-apex-charts
-   [ ] Im and Export, see https://github.com/pxlrbt/filament-excel and https://github.com/eighty9nine/filament-excel-import or https://github.com/konnco/filament-import
-   [ ] PDF see https://laraveldaily.com/post/filament-export-record-to-pdf-two-ways or https://tapansharma.dev/blog/a-guide-to-work-with-pdf-generation-in-filamentphp
-   [ ] Option to generate from Blueprint
    -   [ ] Create BlueprintValidator service
    -   [ ] --blueprint option for CreateCommand
    -   [ ] Document how to use Blueprints
-   [ ] Configurable FeatureSet (dependency free, also for Moox Core)?
-   [ ] Generate Blueprint from Prompt
-   [ ] Add presets for Comments, Media, Users, Roles, Permissions
-   [ ] Add blueprints for Blog, Shop, CRM, PM, Forum, Wiki, CMS, etc.
-   [ ] Extend with AI, use https://docs.larallama.io/
    -   [ ] Use Nikic PHP Parser to update code
    -   [ ] Use Larastan to check the code
-   [ ] Add more Blocks
    -   [ ] https://github.com/lucasgiovanny/filament-multiselect-two-sides - for Builder
    -   [ ] ResourceLinkTable - https://www.youtube.com/watch?v=bjv_RiBUtNs
    -   [ ] TipTap Editor
    -   [ ] Code Editor
    -   [ ] Languages, Currency, Country, Timezone, etc.
    -   [ ] Phone, Address with validation

## Packages

-   [ ] Implement Package Generation
    -   [ ] Implement PackageGenerator service
    -   [ ] Create PrepareAppForPackagesCommand
    -   [ ] Implement package entity activation system
    -   [ ] Add package publishing workflow
-   [ ] Move code to Core
    -   [ ] Installer: use Abstract, Service or Traits ...
    -   [ ] ServiceProvider: Abstract PackageTools to be able to add PanelProvider etc. to main function

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

## Builder UI

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
