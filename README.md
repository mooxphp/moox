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

Add a new package:

- Create a new package from TallUI Package Builder template
- Copy contents into one of the subfolder of the monorepo
- Add the package to the monorepo-split-action that fits the folder
- Add the package to _custom/composer.json-example and composer-tests.json


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

The Monorepo as well as all packages are heavily tested by [PHPStan](https://phpstan.org/) also using [Larastan](https://github.com/nunomaduro/larastan), by [Laravel Pint](https://laravel.com/docs/pint) (Laravel-specific PHP CS Fixer), by [Pest](https://pestphp.com/) and last but not least we use [Scrutinizer](https://scrutinizer-ci.com/g/usetall/tallui/) to see code quality, tests and test coverage as a big picture. 

Please make sure you use the same tools in VS Code, our VS Code Extension Pack covers this. Or do the checks manually like so:

- Use phpstan before committing to the main repo: ```./vendor/bin/phpstan analyse```
- You can do that in every package path, too: ```../../vendor/bin/phpstan analyse```
- Run the Pest tests before committing to the repo: ```./vendor/bin/pest```
- Testing single packages is probably much faster: ```../../vendor/bin/pest```
- Check your code style by running ```./vendor/bin/pint```
- You guess it, in any package use ```../../vendor/bin/pint```


## Todo

- Fix workflows of all packages
    - Fixed tallui-form-components - currently working on Level 4, full project is set to Level 5
    - Fix TestCase in tallui-core 
    - @param object problem see https://github.com/phpstan/phpstan/issues/2147
    - Fix dev-components
- Update builder accordingly
- Check Larastan and https://phpstan.org/user-guide/baseline to get rid of errors
- https://img.shields.io/badge/PHPStan-level%208-brightgreen ... readme like phpstan? ... use banner (see form-components)
- Scaffold admin-package
- Start with Dashboard and Tailwind conf (https://tailwindcss.com/docs/theme, see Theme-docs)
- Create Coming Soon page
- Get all packages running in composer
- Wire the full-app with composer
- Deploy it on Vapor, Cloudways and Shared Hosting
- Save the icons, docs and other stuff


## Ideas

Blade / Livewire-Components
class=“ your_class“ => append attributes to default styles or theme styles
:class=”your_class” => overwrite all default styles and theme styles

See:
https://laracasts.com/discuss/channels/livewire/scoped-css-in-livewire-component
https://laravel.com/docs/9.x/blade#passing-data-to-components
https://laravel-livewire.com/docs/2.x/properties
