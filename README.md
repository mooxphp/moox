# TALLUI Monorepo

This is the Monorepo for TALLUI containing the following

## TALLUI App

Laravel app (in root)

- **TALL**UI Dev App - a Laravel App wired locally, use with Laravel Sail

## TALLUI Packages

Installable packages

- **TALL**UI AdminPanel - Lean Admin and Dashboard with simple API to build new Modules
- **TALL**UI Analytics - Integrates Open Web Analytics for privacy-compliant user tracking
- **TALL**UI Blocks - Block-Editor and blocks for Markdown-like editing experience
- **TALL**UI Brand Icons
- **TALL**UI Components - Our component-manager and base set of components
- **TALL**UI Config - Config Manager
- **TALL**UI Cookie - Consent Manager
- **TALL**UI Core - Core Package for building the TALL-Stack
- **TALL**UI Data - Manage static Data like languages, countries, currencies
- **TALL**UI Docs - Documentation module
- **TALL**UI File Icons - offers SVG file icons
- **TALL**UI Flag Icons
- **TALL**UI Google Fonts - offers all 1400 Google Fonts without Google Fonts CDN. Depends on [Laravel Google Fonts](https://github.com/spatie/laravel-google-fonts) from Spatie.
- **TALL**UI Logviewer - Logfile Explorer
- **TALL**UI Maps - Google Maps implementation
- **TALL**UI Material Icons
- **TALL**UI Media - Media-Manager
- **TALL**UI Packages - Composer-compatible Package Manager
- **TALL**UI Routes - Route Manager
- **TALL**UI Theme - Theme for tallui.io
- **TALL**UI Themes - Theme manager for website and admin-themes
- **TALL**UI Users - User manager

## Other stuff

There is some other stuff here

- **TALL**UI Art - Creative Artwork

## Installation

https://github.com/usetall/tallui.git



Probably required at first-install:

```shell
cd storage/
mkdir -p framework/{sessions,views,cache}
chmod -R 775 framework
```

## Installation (tbd)

Simple packages like components can ship their own configurations and assets. They don't depend on Core or any other package, but you have to care for the main dependencies, means the TALL-Stack.

**TALL**UI itself depends on on Laravel Jetstream.

```php
composer require usetall/tallui-adminpanel
```

will install Laravel Jetstream, **TALL**UI Core, Users and the main component libraries.

```php
composer require usetall/tallui-cms
```

will install **TALL**UI AdminPanel, Pages, Blog, Tools and System-Modules.

```php
composer require usetall/tallui-full
```

will install all **TALL**UI packages. The best option to explore **TALL**UI.

```javascript
{
    "repositories": [
        {
            "type": "path",
            "url": "../tallui/tallui-adminpanel"
        }
    ],
    "require": {
        "my/package": "*"
    }
}
```

You should now install the Livewire-Stack for Jetstream

```shell
php artisan jetstream:install livewire
```

run Laravel Mix

```shell
npm install && npm run dev
```

and run the migrations

```shell
php artisan migrate
```

## Github Workflows

- Split Action - https://github.com/marketplace/actions/monorepo-split

## Objects

- Blocks
- Modules
- Components
- Data
- Docs
- Packages
- Themes

### Packages to consider

- https://blade-ui-kit.com/

- https://randallwilk.dev/docs/laravel-form-components

- https://github.com/tomsix/laravel-components-library

- https://github.com/protonemedia/laravel-form-components


### Places to find components:

- https://tailwindui.com/components
- https://github.com/rappasoft/laravel-livewire-tables
- https://avada.theme-fusion.com/website-builder-features/header-layouts/
- https://www.elegantthemes.com/marketplace/divi-headers-pack/
- https://divi.express/divi-headers/
- https://codyhouse.co/ds/components/
- Tailwindcomponents, Alptail

# Configuration



## Main Configuration

Logo-File:

Base-Layout:

Header-Layout:

Header-Navigation:

Footer-Layout:



Features:

search-provider: Meilisearch

languages: en_US, de_DE, ru_RU

dark-mode: on / off

animation: on / off

animation-theme: crisp

scroll: on / off

scroll-theme: smooth

social-login

social-login-provider?



## Color Arrays

primary

secondary



## Fonts



## Roundings



# TALL**UI**

Is a set of Laravel packages, most of them are working independently. You don't need to install the whole application, if you only want to use parts of it in your own app. But if you install all of them you get

- a CMS build on top of Laravel and the TALL-Stack
- a shop system or eCommerce platform
- an admin panel, backend and dashboard
- a CRUD-generator or no-code platform
- features like SEO, multilingual contents, multidomain

free of charge and open-source.

## TALLUI Installer

Install TALL**UI** without CLI, Composer and Laravel knowledge. The Installer is no package but a single PHP file that:

- Checks your hosting environment, fails with information
- Gathers information in a multistep form, writes a json-file
- Installs Laravel and all TALLUI packages
- Allows you to make basic customizations

**Credits:**

- [Laravel Web Installer](https://github.com/venturedrake/laravel-installer) ([see Video](https://www.youtube.com/watch?v=Jput5doFYLg), [read Discussion](https://laracasts.com/discuss/channels/laravel/laravel-web-installer))
- [Official Laravel Installer](https://github.com/laravel/installer) ([see Docs](https://laravel.com/docs/9.x/installation#the-laravel-installer))
- [Building an installer Video Course](https://www.youtube.com/watch?v=xKvoYGDNRCU) (route fallback to installer, better GUI)

## TALL**UI** Bundles

We packed some Bundles for you to fast-start your project:

### TALL**UI** Full Bundle

Install every free TALL**UI** package and component. A full blown CMS, Blog (News and Comments), Webshop (Products, Cart, Wishlist), API (ReST, GraphQL). This might be a good option, if you want to learn about all features TALL**UI** offers. Ships with a child theme boilerplate and optionally seeds a couple of pages, contents, blog articles and products.

### TALL**UI** CMS Bundle

CMS with Pages, Blog (News and Comments) without shop, ReST and GraphQL API. Ships with a child theme boilerplate and optionally seeds a couple of pages and contents.

### TALL**UI** Admin Bundle

Admin Panel, Users and Login. Perfect boilerplate for building a web application without web frontend.

## TALL**UI** Extensions

You can skip the bundles and simply install the packages you need or extend a bundle with single packages. Extensions are Laravel or PHP packages with additional config for TALL**UI**. In many cases a TALL**UI** Extension is a wrapper - requires an existing package - with an Admin Module, that is basically a menu entry and a page to manage entries.

Here they are.

### TALL**UI** Core

Brings the TALL-Stack - TailwindCSS, Alpine.js, Laravel and Livewire - together with some additions like Turbolinks, Charts and Editors. As the Core does neither load these libraries nor render output, it is up to the underlying packages to do this.

**Requires**

- [PHP 8.1](https://www.php.net/)
- [Laravel 9](https://laravel.com/)
- [Alpine.js V3](https://alpinejs.dev/)
- [Laravel Livewire 2](https://laravel-livewire.com/)
- [TailwindCSS v3.0](https://tailwindcss.com/)
- [Turbo Laravel 1.1](https://github.com/tonysm/turbo-laravel)

**Discuss**

- Editor

- Block Editor - MarkdownX (Nachbau) vor EditorJS

- JS Libs

  - [Apache ECharts v5](https://echarts.apache.org/) (or [Laravel Charts](https://charts.erik.cat/) / [Laravel ChartJs](https://github.com/fxcosta/laravel-chartjs) with [Chart.js](https://www.chartjs.org/) and [Tailwind](https://tailwindcomponents.com/component/chart-widget))

  - [mo.js](https://mojs.github.io/) 

  - [Sal.js](https://github.com/mciastek/sal)
  - [Animate.css](https://animate.style/)
  - [Interact.js](https://interactjs.io/)
  - [Popmotion](https://popmotion.io/)
  - [ScrollReveal](https://scrollrevealjs.org/)
  - [Smooth Scroll](https://www.w3schools.com/howto/howto_css_smooth_scroll.asp)
  - [Intersection Observer](https://www.mediaevent.de/javascript/intersection-observer.html) ([Use cases](https://css-tricks.com/a-few-functional-uses-for-intersection-observer-to-know-when-an-element-is-in-view/) / [see Josh.js](https://github.com/mamunhpath/josh.js) / [Small tutorial](https://coolcssanimation.com/how-to-trigger-a-css-animation-on-scroll/))

### TALL**UI** Admin

Lightweight admin panel with login, made with the TALL-Stack: Tailwind, Alpine.js, Laravel, Livewire. 

### TALL**UI** Users

Provides user management including groups, teams, roles and registration with double opt-in. - https://github.com/LaravelDaily/laravel-roles-permissions-manager

### TALL**UI** Packages

Package manager for Laravel. Displays all recommended, installed and activated packages as well as PHP and Laravel versions, distinguishes between:

- PHP
- Laravel
- PHP Packages
- Laravel Packages
- TALL**UI** Extensions
- TALL**UI** Themes
- TALL**UI** Admin Themes
- TALL**UI** Components

Config:

- Composer Only - disables installation and deinstallation of packages.

### TALL**UI** Config

Gives you GUI access to all configuration files: PHP, Laravel and packages.

## TALL**UI** Themes

Same with Themes

## TALL**UI** Admin Themes

Same with Themes for the Admin Panel.

## TALL**UI** Components

Components can be used in the Admin or for the Website. Components are special packages that contain:

- A single renderless component
- An editor view for the block editor (optional)
- All styles to render the component beautifully (optional)



- TALL**UI** Users - User management including groups, teams, roles and registration with double opt-in are configurable options.
  - TALL**UI** 2FA - Two factor authentication for TALL**UI** Users. Can be set as mandatory for admins or all users, to improve security. 
  - TALL**UI** Social Login - Social login for TALL**UI** Users. Based on Sociallite so hundreds of Socialite Providers can be added easily.
- TALL**UI** Dashboard - User-drag-and-droppable Dashboard displaying widgets with forms, charts and other stuff.
- 
- TALL**UI** Themes - Install, use and manage multiple Themes, Admin Themes and Child Themes in TALL**UI**.
- 

- TALL**UI** CMS - Is needed for frontend (website) output. Can be installed solely without the backend to improve security and performance.

- - TALL**UI** Default Theme - Readymade website based on TailwindCSS, Alpine.js and Laravel Livewire. Installable as single theme or with TALL**UI** Themes.

  - TALL**UI** Site Cache - Static file cache with configuration per page or pagetree. Makes your site blazingly fast with generated HTML files, stored locally or on any CDN.

- TALL**UI** Routes - Manage routes, slugs, redirects and error pages. Allows multidomain configuration and provides auto 404 handling and logging, per domain or page.


  - TALL**UI** Languages - Add languages to TALL**UI**, includes translator access (limit to languages)

  - TALL**UI** Media - Upload and manage assets, files, documents.

  - TALL**UI** Components

    - TALL**UI** File Upload Component

    - TALL**UI** Form Component

    - TALL**UI** TreeView Component

    - TALL**UI** Table Component

    - TALL**UI** Grid Component

    - TALL**UI** Section Divider - find https://www.google.com/search?q=css+divider+generator

      TALL**UI** ... siehe Blade UI ...

  - TALL**UI** Pages

  - TALL**UI** Posts

  - TALL**UI** Products

  - TALL**UI** Cart - Add products to a cart.

  - TALL**UI** Wish list - Let customers manage favorite products.

  - TALL**UI** Stages - Manage live, dev, preview and feature stages.

  - TALL**UI** SEO

  - TALL**UI** Config - Manage Laravel configuration files.

  - TALL**UI** Caching - Manage route cache, file cache

  - TALL**UI** Static File Cache - Generate static HTML. - https://laravel-news.com/page-cache



Config

- composer dependency and information
- what is a theme, package or component? composer extra





- https://github.com/davejamesmiller/laravel-route-browser
- https://github.com/douma/laravel-database-routes
- https://laravelviews.com/table-view?sortOrder=asc









Modules (Admin):

- Dashboard

- Pages

- Blog

  - Posts
  - Categories
  - Tags

  - Comments

- Commerce

  - Products
  - Customers
  - Carts
  - Wishlists
  - Payments

- Media

- Packages

  - Extensions
  - Themes (Admin Themes, Site Themes, Hybrid Themes)
  - Components (Frontend Components, Backend Components)

- Users

  - Permissions
  - Groups
  - Teams

- Staging

- Routing (Redirects, Sites and Domains)

- Caches

- Tools

  - System
  - Designer
  - Updater
  - Importer
  - Exporter
  - Backups
  - Scheduler
  - Analytics
  - Reporting

- Config

  - Main Config
  - Extensions
  - Languages

- Docs

  - User docs
  - Admin docs
  - Designer docs
  - Developer docs





## Installer

The TALL**UI** installer is a graphical installation wizard written in a single PHP file. You can simply upload (FTP) the PHP file to your webserver and run it from the browser (e. g. https://www.yoursite.com/installer.php).

### Prerequisites:

- Webserver (Apache, Nginx) with PHP 8.1
- Database (MySQL, MariaDB, Postgres, SQLite or SQL Server)

### Considerations:

Installing with a smart wizard is pretty comfortable. The TALL**UI** installer is even smarter, as it installs a complete Laravel App and leaves everything intact, that makes developing fun.

#### TODO: 

The installation starting point should, security-wise, not be within web-root ... how to handle this? See ...

- https://medium.com/laravel-news/the-simple-guide-to-deploy-laravel-5-application-on-shared-hosting-1a8d0aee923e
- http://novate.co.uk/deploy-laravel-5-on-shared-hosting-from-heart-internet/
- https://laracasts.com/discuss/channels/servers/installing-laravel-outside-of-the-httpdocs-folder

Very best way (if directories outside of webroot are accessible by the installer):

- Copy the installer to your webroot
- Point your browser to https://yourdomain.com/installer.php
- Choose /var/www as approot and /var/www/html as webroot (TALL**UI** will suggest this)

Another good way:

1. Create a subdomain for the installer (e. g. https://install.yourdomain.com => /var/www)
2. Copy the installer.php to /var/www
3. Point your browser to https://install.yourdomain.com/installer.php
4. Choose /var/www as approot and /var/www/html as webroot
5. Configure your domain for webroot (e. g. https://www.yourdomain.com => /var/www/html)

If you're not able to create subdomains or configure a domain-record to a folder outside webroot, you can:

1. Copy the installer.php to your webroot
2. Point your browser to https://yourdomain.com/installer.php
3. Leave the current directory as approot and webroot (TALLUI will manage this by .htaccess AND/OR individual scaffolding)



After installing you can develop with Git, Composer, Webpack and of course the Artisan command. You need Git, Composer and CLI-Access like SSH then.

Without Composer and Git you can use TALL**UI** Packages - our package manager with web-UI - to manage all TALL**UI** Extensions, Components and Themes. Installing and managing Laravel packages or PHP packages not already listed in the TALL**UI** Repository requires Composer.

So, if you want a CMS like WordPress or TYPO3 (we know both platforms deeply and tried to get the best of both worlds into TALL**UI**) our Installer and the GUI of TALL**UI** is probably all you need. But if your are a developer, who wants to use TALL**UI** as a starting point, you may want to use our installer for convenience and proceed with all the devtools Laravel offers then.







Install protocol

- Downloading TALLUI Core as ZIP file from https://github.com/usetall/tallui-core/archive/refs/tags/v1.0.zip
- Extracting tallui-v1.0.zip and installing TALLUI Core to webroot 
- Downloading TALLUI Admin



Components

- Progress Bar
- Loading Spinner
- Before / After



Packages

- Theme Directory
- Theme Lite
- Theme Pure
- Theme Vivid
- Package Directory
- Component Directory
- Dev Components / code view ...
- Icon Search
- Designer - creates a child-theme $
- No Code - creates a package with CRUD $
- Shop
- Blog
- Gallery
- Icon Search
- Data Browser
- Demo App
- Full App
- CMS App
- Socialite
- Search

# Todo



```
php artisan make:livewire components.livewire-component --test

```



## Monorepo:

- Craft a new version
  - create version files in each package (and builder)
  - read version https://github.com/marketplace/actions/read-file or better https://github.com/marketplace/actions/get-json-property
  - craft version https://github.com/marketplace/actions/create-release

release.yml - package

```yaml
name: Release

on:
  push:
    paths:
      - 'tui-release.json'
    
jobs:
	build:
		runs-on: ubuntu-latest
		steps: 
          - name: Checkout code
            uses: actions/checkout@v3
            with:
              ref: ${{ github.head_ref }}
          - name: Read version from JSON file
          	id: read_version
          	uses: notiz-dev/github-action-json-property@release
          	with:
          		path: 'tui-release.json'
        		prop_path: 'Release.Version'
          - name: Read release title from JSON file
          	id: read_title
          	uses: notiz-dev/github-action-json-property@release
          	with:
          		path: 'tui-release.json'
        		prop_path: 'Release.Title'					
          - name: Read release notes from JSON file
          	id: read_notes
          	uses: notiz-dev/github-action-json-property@release
          	with:
          		path: 'tui-release.json'
        		prop_path: 'Release.Notes'
          - name: Create the release
          	id: create_release
        	uses: ncipollo/release-action@v1
      		with:
				tag: ${{steps.read_version.outputs.prop}}
				name: ${{steps.read_title.outputs.prop}}
				body: ${{steps.read_notes.outputs.prop}}
                token: ${{ secrets.GITHUB_TOKEN }}

            # TODO body = output a line for each prop[value]
            # TODO run: echo ${{steps.read-version.outputs.prop}}
```

releases.yml - root

```yaml
 name: Releases

on:
  push:
    paths:
      - 'tui-release.json'
    
jobs:
	build:
		runs-on: ubuntu-latest
		steps: 
            - name: Checkout code
              uses: actions/checkout@v3
              with:
              ref: ${{ github.head_ref }}
            - name: Read version from JSON file
              id: read_packages
              uses: notiz-dev/github-action-json-property@release
              with:
              path: 'tui-release-matrix.json'
              prop_path: 'Packages'

			# TODO should be a foreach ... needs a matrix made from json
			# see https://github.com/JoshuaTheMiller/conditional-build-matrix
			
            - name: copy files
              id: copy_files
              uses: canastro/copy-file-action@master
              with:
                source: "tui-release.json"
                target: "prop-path/package/tui-release.json"
                
            # TODO needs the matrix too, should also matrix over folders
			- name: split repos
              id: split_repos
              if: "!startsWith(github.ref, 'refs/tags/')"
              uses: "symplify/monorepo-split-github-action@2.1"
              with:
                tag: ${GITHUB_REF#refs/tags/}

                package_directory: '_apps/${{ matrix.package }}'

                repository_organization: 'usetall'
                repository_name: '${{ matrix.package }}'

                user_name: "adrolli"
                user_email: "alf@alf-drollinger.com"
                
            - name: Create the release
              id: create_release
              uses: ncipollo/release-action@v1
              with:
              tag: ${{steps.read_version.outputs.prop}}
              name: ${{steps.read_title.outputs.prop}}
              body: ${{steps.read_notes.outputs.prop}}
              token: ${{ secrets.GITHUB_TOKEN }}

            # TODO body = output a line for each prop[value]
            # TODO run: echo ${{steps.read-version.outputs.prop}}
                
```



tui-release.json

```json
{
	"Release": {
		"Version": "0.0.1",
        "Prerelease": true,
		"Title": "Test Release"
	}
}
```



maybe it is better to do notes in Markdown

tui-release-notes.md // tui-release-notes-template.md

```markdown
# Release Notes

These are example release notes, please enter notable changes here:

![Dashboard](idontknowwhere/dashboard.png)

- First change, Github Issue #23
- Second change, Git Commit-Hash
- Third change, made by @adrolli
- Forth change, linked (here)[https://tallui.io]
```

Then read them (copy them like json), use them as bodyfile

Afterwards delete them, and replace them with the above template

Insert a check, if the template was edited. If not, cancel the release



## Releases:

There are two ways to craft a new release:

- Craft a minor release (0.1.19 -> 0.1.20) to a single package
  - Edit the release.json file in one of the packages
  - The Github Action will craft a new release for this package
  - The Github Action will edit the version 
- Craft a major release (0.1.0 -> 0.2.0) to all packages
  - Edit the release.json file in project root
  - The Github Action will update all release-files according to release matrix
  - The Github Action will craft a new release for the tallui-monorepo
  - All packages will be released accordingly

tui-release-matrix.json

```json
{
	"Packages": [
		"tallui-core"
	],
	"Components": [
		"tallui-web-components",
		"tallui-app-components",
		"tallui-form-components"
	],
	"Apps": [
		"tallui-full"
	]
}
```

Can also invoke the split

```yaml
name: 'Split Components'

on:
    push:
        branches:
            - main
        tags:
            - '*'

env:
    GITHUB_TOKEN: ${{ secrets.SPLIT_TOKEN }}

jobs:
    packages_split:
        runs-on: ubuntu-latest

        strategy:
            fail-fast: false
            matrix:
                package:
                  - tallui-full
        steps:
            -   uses: actions/checkout@v2

            -
                if: "!startsWith(github.ref, 'refs/tags/')"
                uses: "symplify/monorepo-split-github-action@2.1"
                with:
                    tag: ${GITHUB_REF#refs/tags/}

                    package_directory: '_apps/${{ matrix.package }}'

                    repository_organization: 'usetall'
                    repository_name: '${{ matrix.package }}'

                    user_name: "adrolli"
                    user_email: "alf@alf-drollinger.com"

```







- Read with https://github.com/GrahamCampbell/Laravel-Markdown or write php, too (more efficient, otherwise in next step you need versions of all deps too)



## Builder:

- Builder does not register sp, why? Seems to work
- Fix problem with asset loading, mix error, see https://blade-ui-kit.com/docs/0.x/installation (Production) ... test with blade-ui-kit, when unsure if it works mixing 3rd party assets. 
- How to include all assets as original sources? Better than 3rd party CDN?
- Add languages for translation to builder
- Banner static or https://banners.beyondco.de/
- Remove most of dependencies, add livewire to dev-deps
- Fix testing problems
- Improve Readme
- Add docs and tests for components
- Improve Configure
  - Theme, Components or Package? Strip everything you don't need
  - TallUI Ascii Art, like https://patorjk.com/software/taag/#p=display&f=Small%20Slant&t=TALLUI



## Form Components:

- Create form components from blade-ui, change everything to livewire, if possible, see
  -  https://forum.laravel-livewire.com/t/is-there-a-way-to-use-blades-attribute-forwarding-in-livewire-components/1138/5
  - https://laravel-livewire.com/screencasts/nesting
  - https://laracasts.com/series/livewire-basics/episodes/3

Admin Dashboard

- file:///C:/Users/alfdr/GitHub/tallui/_packages/tallui-adminpanel/dashboard.html -> Dashboard

Icons

- Fork https://github.com/blade-ui-kit/blade-icons-template to icon-builder
- Add Scaffold Command wiring the package
- Create all iconsets

## Core:

- Move asset logic to core: 
  core should be able to load and mix all assets, own assets as well as assets of all registered packages with own assets. Asset loading should be done as follows:
  - Mix all global assets to core.css and core.js - core does only tall-stack, no own assets
  - Mix all theme-specific assets to theme.css and theme.js - theme depends on own tailwind-conf?
  - Load (or mix and merge) all component-specific assets (to page.css and page.js)
- Move loading logic to core
  - Core (as well as a backup class for loading components without core) should be able to load ...
    - Backend Modules
      - route
      - view
      - menu (own class)
    - Editor Blocks
      - Backend Component, to be invented

- extend PackageServiceProvider in core
  - hasModule(TalluiCoreModule::class)
  - hasWidget('tallui-core_widget')
  - hasBlock('tallui-core_block')
  - hasAdminTheme('tallui-core_admin_theme')
  - hasTheme('tallui-core_website_theme');
  - hasAssets?
  - use https://github.com/tonysm/tailwindcss-laravel



## Editor


- TEST https://github.com/VanOns/laraberg





## Fresh installation (docs)

curl -s https://laravel.build/example-app | bash

Add TallUI VCS-Repositories and Dependencies to composer.json, soon to be available on Packagist

php artisan jetstream:install livewire

npm install && npm run dev

vendor/bin/sail up -d

vendor/bin/sail shell

php artisan migrate





## Testing:

- Scrutinizer
- https://github.com/laracasts/cypress
- https://docs.cypress.io/guides/overview/why-cypress#What-you-ll-learn

Helper scripts:

- Update PHP: Scrutinizer, Composer, ...



