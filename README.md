# TallUI Monorepo

This is the TallUI Monorepo containing all packages and the Laravel dev app.


## Packages

Following packages in _components, _data, _icons, _others, _packages and _themes) are automatically updated to their own read-only repos when merging to main.

- TallUI Full App
- TallUI App Components
- TallUI Dev Components
- TallUI Form Components
- TallUI Web Components
- TallUI Web Icons
- TallUI Core
- TallUI Package Builder

### Add a new package:

- Create a new package from TallUI Package Builder template
- Copy contents into one of the _subfolder of the monorepo
- Add the package to the monorepo-split-action that fits the folder
- Add the package to _custom/composer.json-example and composer-tests.json
- Add the package to the list of packages above
- Add the package to _app/***/composer.json, if the package is stable


## Installation

The Laravel dev app is made for instant development with Laravel Sail or Laragon. 

```bash
# Use the prepared composer.json
cp _custom/composer.json-example _custom/composer.json

# Use the prepared environment
cp .env.example .env

# Build
composer install

# Run Sail
./vendor/bin/sail up

# Run Vite (in Ubuntu, not in Sail container)
npm install
npm run dev

# Rebuild the sail config if needed
./vendor/bin/sail down --rmi all -v
php artisan sail:install

# Remove broken symlinks if needed
rm -Rf vendor/usetall
```


## Custom

As you might want to develop with a custom set of TallUI packages or require your own packages, we included a second composer.json. This composer-file requires all TallUI packages and can be easily edited or extended without overwriting the main composer.json.

```bash
cd _custom
cp composer.json-example composer.json
```

To customize the set of TallUI packages, simply delete the packages from the require-section, you don't want to load, ```composer update``` afterwards.

If you want to include custom packages you can clone one or more packages as subrepos into _custom and add them to _custom/composer.json like so:

```json
    "repositories": [
        {
            "type": "path",
            "url": "./_custom/package"
        }
    ],
    "require": {
        "custom/package": "dev-main"
    },
```


## Development

- Do `npm run build` before committing because automated tests on GitHub needs a working vite-manifest
- Do `php artisan migrate --database=sqlite` to reflect changes to the test-database
- Use https://marketplace.visualstudio.com/items?itemName=adrolli.tallui-laravel-livewire-tailwind with VS Code
- Use https://github.com/usetall/tallui-package-builder to create your own packages
- Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


### Branching

- ```main``` is the current stable version, branch-protected, auto-commits to all packages, deployed live
- ```test``` is the branch for tests and Scrutinizer, deployed on staging, merged to main
- ```dev``` active development with tests and code fixing, merged to test
- ```feature/...``` prefix all other dev-branches, merge to dev


## Testing

The Monorepo with all packages is tested by [PHPStan](https://phpstan.org/) using [Larastan](https://github.com/nunomaduro/larastan), by [Laravel Pint](https://laravel.com/docs/pint) (Laravel-specific PHP CS Fixer), by [Pest](https://pestphp.com/) and last but not least we use [Scrutinizer](https://scrutinizer-ci.com/g/usetall/tallui/) to see code quality, tests and test coverage as a big picture. 

Please make sure you use the same tools in VS Code, our VS Code Extension Pack covers this. Or do the checks manually like so:

- Use PHPStan before committing: ```./vendor/bin/phpstan analyse```, from a package: ```../../vendor/bin/phpstan analyse```
- Run Pest before committing: ```./vendor/bin/pest```, from a package: ```../../vendor/bin/pest```
- Run Pint before commiting: ```./vendor/bin/pint```, you guess it: ```../../vendor/bin/pint```


## Todo

- Fix workflows of all packages to Level 8
    - PHPStan tallui-web-components - Level 8
    - PHPStan tallui-form-components - Level 8
    - PHPStan tallui-app-components - Level 8
    - PHPStan tallui-dev-components - Level 8
    - PHPStan tallui-web-icons - no phpstan, rebuild package
    - PHPStan tallui-core - Level 8
    - PHPStan tallui-package-builder - Level 8 (or exclude?)
    - Fix TestCase in tallui-core
- https://img.shields.io/badge/PHPStan-level%208-brightgreen ... readme like phpstan? ... use banner (see form-components)
- Scaffold website-package to output all components
- Scaffold admin-package
- Start with Dashboard and Tailwind conf (https://tailwindcss.com/docs/theme, see Theme-docs)
- Create Coming Soon page
- Get all packages running in composer
- Wire the full-app with composer
- Rebuild icons-package with Workflows, add to builder?
- Deploy it on Vapor, Cloudways and Shared Hosting
- Save the icons, dev-components, docs and other stuff


## Ideas

Blade / Livewire-Components
class=“ your_class“ => append attributes to default styles or theme styles
:class=”your_class” => overwrite all default styles and theme styles

See:
https://laracasts.com/discuss/channels/livewire/scoped-css-in-livewire-component
https://laravel.com/docs/9.x/blade#passing-data-to-components
https://laravel-livewire.com/docs/2.x/properties
