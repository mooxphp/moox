# Moox Builder Devlog

This is the current state of the Builder:

- Moox Builder is currently a GitHub Template Repository (will be removed) and partly working as an installed package (will be the future)
- The current state is in this branch: https://github.com/mooxphp/moox/tree/feature/tag
- The `php artisan builder:create`command is working, tested with simple and published item yet
- A Panel is available to Preview: https://moox.test/builder
- There are 5 test entities in the package
  - https://moox.test/moox/simple-items
  - https://moox.test/moox/publishable-items
  - https://moox.test/moox/full-items
  - https://moox.test/moox/simple-taxonomies
  - https://moox.test/moox/nested-taxonomies
- The current Task is finishing these as Presets for Builder, and ...

## Todo

- [ ] The create command is not 100% as described in README
- [ ] The delete command is just partly working (not deleteing the plugin and migration) and not as described in README
- [ ] The migration created does not have a timestamp, should it be prefixed?
- [ ] The create command does not migrate, throws an error
- [ ] Some of the Blocks are not working as they miss traits, methods ... need to iterate
- [ ] Moox Core Features need to be refactored to be able to generate them without issues, eliminate methods and move to traits
  - [ ] getResourceName should be auto detected
  - [ ] Currently new Packages need to register in core to use TranslatableConfig, that was not my best idea
- [ ] We need to generate packages
  - [ ] Skeleton files
  - [ ] Generators
  - [ ] Commands
  - [ ] Package Manager first iteration using Composer commands
- [ ] We need to generate the config for entities and packages to get everything working, also set Taxonomies and Relations (not implemented yet)
- [ ] Builder needs to be cleaned up after able to generate packages
  - [ ] Cleanup config
  - [ ] Remove old entities
  - [ ] Remove build.php
  - [ ] Remove GH Template
- [ ] We need to generate factories from blocks to entities
- [ ] What about translations?
- [ ] We need to generate tests
- [ ] Generate the Builder UI, let Builder build itself
- [ ] Generate a Frontend
- [ ] Just an idea: https://docs.larallama.io/
- [ ] Just an idea: https://github.com/nikic/PHP-Parser

## Todo in Core

- Publish
	- Publish Button is shown on already published items
	- Should then be save only
	- There could be a create new draft for published?
- Core Docs
	- Naming convention InModel InResource InPages and Single for single-use traits
	- TabsInResource - contains TODO
	- TabsInPage - just getTabs needs to be defined
	- TaxonomyInPages - needs that mount method in ViewPage
	- AuthorInModel
	- AuthorInResource
	- StatusInModel
	- StatusInResource - WIP
	- Links to builder or builder doc inside
- Category / Tag Docs
	- Provides a powerful hierarchical Category system, based on Nested Set and highly configurable Filament resources to build.
	- https://github.com/lazychaser/laravel-nestedset
	- https://github.com/CodeWithDennis/filament-select-tree, does need `php artisan filament:assets 
	- Screens
	- Usage / Config
- Remove commented code in build.php
- $livewire->saveAndCreateAnother(); error, auch in Tags und Builder?
- Relationships - in builder but like taxonomies
- Add fields and features: https://chatgpt.com/c/67180a73-d4e8-800c-b37a-0fa822555a11
- Meta, see "add fields and features Chat" for JSON, EAV, Polymorphic or [Spatie](https://github.com/spatie/laravel-schemaless-attributes) , currently tending to JSON + Polymorphic
- Eloquent/Builder becomes Eloquent/Tag ... Resource and Edit
- "moox/core": "*" ... version in builder should not be set?
- workbench as build?
- HasSlug has been removed from the model, as long as Moox Slug is not ready, dependency to Spatie slug is where to do?
- Item could show last changed etc. on the left ...
- Gallery images should be sortable
- Bulk restore does not work
- Set indices for slug etc, or not?
- not Cascade (for taxonomies) specially? Cascade is most of the times not a good idea, configurable?
-  If plugin data-language -> migration create_languages_table -> 
	  SP: ->hasMigration('create_data_languages_table') (correct the -)
	- Install Script like Breezy - https://github.com/jeffgreco13/filament-breezy/blob/2.x/src/Commands/Install.php
	- Model BuilderItem -> table builder_items (plural!)
		- Must not be THE plural, ie. Blog -> posts
	- Crazy - Multiple Items - Fields
		- Migration, Model, Resource, Frontend
		- Set Config
		- Create Fields
	- Livewire Frontend
	- Ship Permissions - NEXT! - https://laracasts.com/discuss/channels/laravel/policies-in-packages
	- Ship Dashboard Widgets https://github.com/Flowframe/laravel-trend and https://github.com/leandrocfe/filament-apex-charts
	- Ship Im and Export, see https://github.com/pxlrbt/filament-excel and https://github.com/eighty9nine/filament-excel-import or https://github.com/konnco/filament-import 
	- Ship PDF see https://laraveldaily.com/post/filament-export-record-to-pdf-two-ways or https://tapansharma.dev/blog/a-guide-to-work-with-pdf-generation-in-filamentphp
	- Ship Config - Pages
		- Global
			- Admin-Only
			- Widgets
			- Activate Resources
			- Features (Like IP-Lock in Users)
		- Resource(s)
			- Widgets
			- Categories
			- Taggable
			- Revisionable
			- Syncable
			- Import
			- Export
			- Pruning
			- Printing
			- Verbable - crazy - https://verbs.thunk.dev/
	  		Builder, next Iteration:
- Item
	- Translatable
	- Syncable
	- Authorable - isses, aber noch nicht abschaltbar
	- Seoable
	- Revisionable
	- Orderable
- Inline-Help, Config dafür

## Packages

Current Status:

- Files are prepared: Service, Generators, Templates and Commands
- Config is prepared
- Install Template and Readme are not finished, as well as their partials
- All templates could be completely prepared
- Then we could go for the Generators
- Then the Services
- Finally the Commands
- Test and bring it live ... needs to install in a new Laravel to completely polish

I want to generate Packages using Moox Builder, it should work like this:

- We need Preparation to be able to install packages locally, `PrepareAppForPackagesCommand`
  - Create a /packages directory
  - Paste `composerrepos.stub` into composer.json
- Generate an empty package, where we are able to generate Entities in package context with the `CreatePackageCommand`, it uses the `PackageGenerator` Service that iterates over the new `package_generator` config key, that conntects the Generators and the Templates.

- Now we can `Generate Entities` into that package
  - Generate the Entity in Package context
  - Generate the Resource part in the config, like wired in the `package_entity_enabler`config key
  - Generate the parts into the installer, like wired in the `package_entity_enabler`config key
  - Generate the part into the README, like wired in the `package_entity_enabler`config key
- For activation of packages, I also created a config key `package_entity_activator`, that just wires the `PackageActivator`Service used by the `ActivatePackageCommand`
  - We need to require the package using composer
  - We need to run php artisan package:install
- Finally the `PackagePublisher` service used by the `PublishPackageCommand`
  - Create a Git repo
  - Publish to GitHub
  - Add the package to Moox Monorepo
  - Publish to Packagist - https://packagist.org/apidoc#create-package
- Later we'll need a `RemovePackageCommand` that uses the `PackageRemover`service
- I am not sure, if we should extend a new `AbstractPackageService`or use the existing



## The UI

Some early thoughts:

- Package
  
  - Fields
  
    - Name
    - Description
    - Version
    - License
    - Author
    - Website
    - E-Mail
    - E-Mail Security related
    - Status
      - Draft
      - Built - finalize (SP, pp)
      - Installed
      - Active
    - Actions
         - Create
             - View
             - Edit
             - Delete
             - Build
             - Install
             - Activate - per entity per panel
  
    
  
    
  
    - Entity
    - Fields
  
  - Template - Select, preset preselects features
      - Simple
      - Item
      - Taxonomy
  
      - Name
      - Description
      - Package (or App) - Select
      - Features - https://filamentphp.com/docs/3.x/forms/fields/checkbox-list
          - View
          - Create
          - Edit
          - Delete
          - Soft Delete
          - Publish
          - Author
          - Widgets
      - Taxonomies - https://github.com/lucasgiovanny/filament-multiselect-two-sides
          - Categories
          - Tags
          - ...
      - Tabs - https://github.com/lucasgiovanny/filament-multiselect-two-sides
      - Fields - https://filamentphp.com/docs/3.x/forms/fields/repeater or https://filamentphp.com/docs/3.x/forms/fields/builder
          - Title
          - Type - Select
              - String
              - Text
              - Image
              - ...
          - Features - https://filamentphp.com/docs/3.x/forms/fields/checkbox-list
              - View
              - Create
              - Edit
              - Delete
              - Soft Delete
              - Publish