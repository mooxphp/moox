# TallUI Monorepo

This is the TallUI Monorepo containing all packages and the Laravel dev app.

Packages are automatically tested when committing to the dev branch and updated to their own read-only repos when merging to main.


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
- ```test``` is the branch for final testing, deployed on staging, can be merged to main
- ```dev``` active development with code fixing, can be merged to test
- ```feature-...``` refer to an issue, can be merged to dev


## Todo

- Check Larastan and https://phpstan.org/user-guide/baseline to get rid of errors
- Fix workflows of all packages
- Fix dev-components
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
