<h1 align="center">
    <img src="_others/tallui-art/tallui-logo.svg" width="100" alt="TallUI Logo">
    <br><br>
    <img src="_others/tallui-art/tallui-textlogo.svg" width="110" alt="TallUI Textlogo">
    <br><br>
</h1>

<p align="center">
    <a href="https://github.com/usetall/tallui/actions/workflows/pest.yml">
        <img alt="PEST Tests" src="https://img.shields.io/github/workflow/status/usetall/tallui/Pest?label=PestPHP">
    </a>
    <a href="https://github.com/usetall/tallui/actions/workflows/pint.yml">
        <img alt="Laravel PINT PHP Code Style" src="https://img.shields.io/github/workflow/status/usetall/tallui/Pint?label=Laravel Pint">
    </a>
    <a href="https://github.com/usetall/tallui/actions/workflows/phpstan.yml">
        <img alt="PHPStan Level 5" src="https://img.shields.io/github/workflow/status/usetall/tallui/PHPStan?label=PHPStan">
    </a>
</p>
<p align="center">
    <a href="https://app.codacy.com/gh/usetall/tallui/dashboard">
        <img src="https://app.codacy.com/project/badge/Grade/2b912412bb6e4892b52688272dec1555">
    </a>
    <a href="https://app.codacy.com/gh/usetall/tallui/dashboard">
        <img src="https://app.codacy.com/project/badge/Coverage/2b912412bb6e4892b52688272dec1555">
    </a>
    <a href="https://scrutinizer-ci.com/g/usetall/tallui/?branch=main">
        <img alt="Scrutinizer Code Quality" src="https://scrutinizer-ci.com/g/usetall/tallui/badges/quality-score.png?b=main">
    </a>
    <a href="https://hosted.weblate.org/engage/tallui/">
        <img src="https://hosted.weblate.org/widgets/tallui/-/svg-badge.svg" alt="Translation status" />
    </a>
    <a href="https://github.com/usetall/tallui-app-components/blob/main/LICENSE.md">
        <img alt="License" src="https://img.shields.io/github/license/usetall/tallui-app-components?color=blue&label=license">
    </a>
    <br>
    <br>
</p>


## TallUI Monorepo

This is the TallUI Monorepo containing all packages and the Laravel dev app.


### Packages

TallUI packages are categorized in 

- [_components](./_components/README.md) - Laravel packages only packed with Blade and Livewire components
- [_data](./_data/README.md) - Laravel packages only used as data-provider (model, migration, seeding)
- [_icons](./_icons/README.md) - Laravel packages only with SVG icons, compatible with Blade Icons
- [_others](./_others/README.md) - Other Laravel packages or assisting repos like TallUI Package Builder
- [_packages](./_packages/README.md) - Full blown Laravel packages like TallUI Core or Admin Panel
- [_themes](./_themes/README.md) - Themes for the admin (backend) or website (frontend)
- [_themes](./_themes/README.md)/[website](./_themes/website/README.md) - Themes for the TallUI Website

Packages are automatically updated to their own read-only repos when pushed to [main]. See the [Builder docs](./docs/builder/README.md) for more information about how to build and publish packages.


### Installation

The Laravel dev app in the root-folder of the TallUI Monorepo is made for instant development with Laravel Sail or Laragon. 

```bash
# Use the prepared composer.json
cp _custom/composer.json-example _custom/composer.json

# Use the matching environment for sail or laragon
cp .env.sail .env
cp .env.laragon .env

# Build
composer install

# Run Sail, alternatively start Laragon
./vendor/bin/sail up

# Run Vite (in Ubuntu, not in Sail container)
npm install
npm run dev

# Rebuild the sail config if needed
./vendor/bin/sail down --rmi all -v
php artisan sail:install

# Remove broken symlinks 
# switching from Laragon to Sail for example
rm -Rf vendor/usetall
```


### Custom packages

As you might want to develop with a custom set of TallUI packages or require your own packages, we included a second composer.json. This composer-file requires all TallUI packages and can be easily edited or extended without overwriting the main composer.json.

```bash
cd _custom
cp composer.json-example composer.json
```

To customize the set of TallUI packages, simply delete the packages you don't want to load from the require-section, ```composer update``` afterwards.

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


### Development

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


### Testing

We test TallUI using:

- Monorepo
  - [Larastan](https://github.com/nunomaduro/larastan), [PHPStan](https://phpstan.org/) Level 5
  - [Laravel Pint](https://laravel.com/docs/pint), PHP CS Fixer
  - [Scrutinizer](https://scrutinizer-ci.com/g/usetall/tallui/), [Codacy](https://app.codacy.com/gh/usetall/tallui/) and [Code climate](https://codeclimate.com/github/usetall/tallui) (testing)
- Packages
  - [Orchestra Testbench](https://orchestraplatform.readme.io/docs/testbench)
  - [Larastan](https://github.com/nunomaduro/larastan), [PHPStan](https://phpstan.org/) Level 5
  - [Laravel Pint](https://laravel.com/docs/pint), PHP CS Fixer
  - [Pest](https://pestphp.com/)

Please make sure you use the same tools in VS Code (our [VS Code Extension Pack](https://marketplace.visualstudio.com/items?itemName=adrolli.tallui-laravel-livewire-tailwind) covers this) or do the checks manually before committing to the dev-branch:

- PHPStan: ```composer analyse ``` or ```./vendor/bin/phpstan analyse```, for packages ```../../vendor/bin/phpstan analyse```
- Pest: ```composer test ``` or ```./vendor/bin/pest```, for packages ```../../vendor/bin/pest```
- Coverage: ```composer test-coverage ``` or ```./vendor/bin/pest --coverage```, for packages ```../../vendor/bin/pest --coverage```
- Pint: ```composer format ``` or ```./vendor/bin/pint```, for packages ```../../vendor/bin/pint```

### Translation

TallUI is translated with Weblate. More information about the languages, translation status and how to contribute in our [translation documentation](./docs/translation/README.md).

<a href="https://hosted.weblate.org/engage/tallui/">
<img src="https://hosted.weblate.org/widgets/tallui/-/open-graph.png" alt="Translation status" /></a>

### Contributors

TallUI is made by these nice people, and bots ...

<!-- readme: adrolli,Reinhold-Jesse,KimSpeer,azizovic12,collaborators,contributors,weblate,laravel-shift,tallui-bot,bots -start -->
<!-- readme: adrolli,Reinhold-Jesse,KimSpeer,azizovic12,collaborators,contributors,weblate,laravel-shift,tallui-bot,bots -end -->
