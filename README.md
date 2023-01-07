<p align="center">
    <img src="https://github.com/usetall/tallui/raw/main/_others/tallui-art/tallui-logo.svg" width="100" alt="TallUI Logo">
    <br><br>
    <img src="https://github.com/usetall/tallui/raw/main//_others/tallui-art/tallui-textlogo.svg" width="110" alt="TallUI Textlogo">
</p><br>



<p align="center">
    <a href="https://github.com/usetall/tallui/actions/workflows/pest.yml">
        <img alt="PEST Tests" src="https://github.com/usetall/tallui/actions/workflows/pest.yml/badge.svg">
    </a>
    <a href="https://github.com/usetall/tallui/actions/workflows/pint.yml">
        <img alt="Laravel PINT PHP Code Style" src="https://github.com/usetall/tallui/actions/workflows/pint.yml/badge.svg">
    </a>
    <a href="https://github.com/usetall/tallui/actions/workflows/phpstan.yml">
        <img alt="PHPStan Level 5" src="https://github.com/usetall/tallui/actions/workflows/phpstan.yml/badge.svg">
    </a>
</p>
<p align="center">
    <a href="https://app.codacy.com/gh/usetall/tallui/dashboard">
        <img src="https://app.codacy.com/project/badge/Grade/2b912412bb6e4892b52688272dec1555" alt="Codacy Code Quality">
    </a>
    <a href="https://app.codacy.com/gh/usetall/tallui/dashboard">
        <img src="https://app.codacy.com/project/badge/Coverage/2b912412bb6e4892b52688272dec1555" alt="Codacy Coverage">
    </a>
    <a href="https://codeclimate.com/github/usetall/tallui/maintainability">
        <img src="https://api.codeclimate.com/v1/badges/1b6dae4442e751fd60b9/maintainability" alt="Code Climate Maintainability">
    </a>
    <a href="https://app.snyk.io/org/adrolli/project/dd7d7d2c-7a0c-4741-ab01-e3d11ea18fa0">
        <img alt="Snyk Security" src="https://img.shields.io/snyk/vulnerabilities/github/usetall/tallui">
    </a>
</p>
<p align="center">
    <a href="https://hosted.weblate.org/engage/tallui/">
        <img src="https://hosted.weblate.org/widgets/tallui/-/svg-badge.svg" alt="Translation status" />
    </a>
    <a href="https://www.laravel.com">
        <img alt="Laravel 9" src="https://img.shields.io/badge/Laravel-9-orange?logo=Laravel&color=FF2D20">
    </a>
    <a href="https://github.com/usetall/tallui-app-components/blob/main/LICENSE.md">
        <img alt="License" src="https://img.shields.io/github/license/usetall/tallui-app-components?color=blue&label=license">
    </a>
    <a href="https://tallui.slack.com/">
        <img alt="Slack" src="https://img.shields.io/badge/Slack-TallUI-blue?logo=slack">
    </a>
    <br>
    <br>
</p>


# TallUI Monorepo

This is the TallUI Monorepo containing all packages and the Laravel dev app.


## Packages

TallUI packages are categorized in 

- [_components](./_components/README.md) - Laravel packages only packed with Blade and Livewire components
- [_data](./_data/README.md) - Laravel packages only used as data-provider (model, migration, seeding)
- [_icons](./_icons/README.md) - Laravel packages only with SVG icons, compatible with Blade Icons
- [_others](./_others/README.md) - Other Laravel packages or assisting repos like TallUI Package Builder
- [_packages](./_packages/README.md) - Full blown Laravel packages like TallUI Core or Admin Panel
- [_themes](./_themes/README.md) - Themes for the admin (backend) or website (frontend)
- [_themes](./_themes/README.md)/[website](./_themes/website/README.md) - Themes for the TallUI Website

Packages are automatically updated to their own read-only repos when pushed to `main`. See the [Builder docs](./docs/builder/README.md) for more information about how to build and publish packages.


## Installation

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


## Custom packages

Our Monorepo is prepared to be a double agent:

- Develop a private or public project
- while contributing to the TallUI project

This is done by supporting custom packages in the development app of our Monorepo. Sounds interesting? Read on [_custom/README.md](_custom/README.md).


## Development

- Do `npm run build` before committing because automated tests on GitHub needs a working vite-manifest
- Do `php artisan migrate --database=sqlite` to reflect changes to the test-database
- Use https://marketplace.visualstudio.com/items?itemName=adrolli.tallui-laravel-livewire-tailwind with VS Code
- Use https://github.com/usetall/tallui-package-builder to create your own packages
- Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## Branching

- ```main``` is the current stable version, branch-protected, auto-commits to all packages, deployed to dev
- ```feature/...``` please prefix all feature-branches, create your pull requests directly to main

Use issue-based branches, prefix them with feature/ (e.g. feature/38-welcome-view) for automated tests and code analysis. 


## Commits

Your commit messages will be merged into Changelog.md, means they become part of the documentation. Please make sure, you

- start with one of these types
    - feat = feature
    - fix = bugfix
    - bump = minor verion change
    - major = major updates
    - lang = translation
    - docs = documentation
    - devops = GH and automation
    - wip = work in progress
- followed by the shortname of the package, in []
    - all - multiple or all packages
    - admin-panel
    - core
    - package-builder
    - form-components
    - app-components
    - web-components
    - chart-components
    - monorepo

- reference an issue, linked by issue number, e. g. #138
- prepare auto-closing the issue by using "Close #138"


#### Valid examples

- `wip[core]: Feature register assets w-i-p #123`
- `fix[all]: Update all packages Close #321`
- `feat[monorepo]: Update dev app Close #22`
- `Dependencies: Minor Updates`
- `Wip[Package Builder]: Blade and Livewire components w-i-p #23`

Read more about [conventional commits](https://www.conventionalcommits.org/).


## Pull requests

Create a PR to `main`. Use conventional commits like explained above. 


### Semver

We use semantic versioning, written like 1.2.3 for

1. Major releases
2. Minor releases
3. Bugfix releases

Visit [Semver.org](https://semver.org/) for more information.


## Releases

Currently done manually, an automatic release feature is on the way.


## Testing

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


## Translation

TallUI is translated with Weblate. More information about the languages, translation status and how to contribute in our [translation documentation](./docs/translation/README.md).

<a href="https://hosted.weblate.org/engage/tallui/">
<img src="https://hosted.weblate.org/widgets/tallui/-/open-graph.png" alt="Translation status" /></a>


## Contributors

TallUI is made by these nice people, and bots ...

<!-- readme: adrolli,Reinhold-Jesse,collaborators,contributors,weblate,laravel-shift,tallui-bot,bots -start -->
<table>
<tr>
    <td align="center">
        <a href="https://github.com/adrolli">
            <img src="https://avatars.githubusercontent.com/u/40421928?v=4" width="100;" alt="adrolli"/>
            <br />
            <sub><b>adrolli</b></sub>
        </a>
    </td>
    <td align="center">
        <a href="https://github.com/reinhold-jesse">
            <img src="https://avatars.githubusercontent.com/u/88349887?v=4" width="100;" alt="reinhold-jesse"/>
            <br />
            <sub><b>reinhold-jesse</b></sub>
        </a>
    </td>
    <td align="center">
        <a href="https://github.com/weblate">
            <img src="https://avatars.githubusercontent.com/u/1607653?v=4" width="100;" alt="weblate"/>
            <br />
            <sub><b>weblate</b></sub>
        </a>
    </td>
    <td align="center">
        <a href="https://github.com/wp1111">
            <img src="https://avatars.githubusercontent.com/u/42349383?v=4" width="100;" alt="wp1111"/>
            <br />
            <sub><b>wp1111</b></sub>
        </a>
    </td>
    <td align="center">
        <a href="https://github.com/azizovic12">
            <img src="https://avatars.githubusercontent.com/u/104441723?v=4" width="100;" alt="azizovic12"/>
            <br />
            <sub><b>azizovic12</b></sub>
        </a>
    </td>
    <td align="center">
        <a href="https://github.com/KimSpeer">
            <img src="https://avatars.githubusercontent.com/u/98323532?v=4" width="100;" alt="KimSpeer"/>
            <br />
            <sub><b>KimSpeer</b></sub>
        </a>
    </td></tr>
<tr>
    <td align="center">
        <a href="https://github.com/laravel-shift">
            <img src="https://avatars.githubusercontent.com/u/15991828?v=4" width="100;" alt="laravel-shift"/>
            <br />
            <sub><b>laravel-shift</b></sub>
        </a>
    </td>
    <td align="center">
        <a href="https://github.com/FMorlock">
            <img src="https://avatars.githubusercontent.com/u/99252924?v=4" width="100;" alt="FMorlock"/>
            <br />
            <sub><b>FMorlock</b></sub>
        </a>
    </td>
    <td align="center">
        <a href="https://github.com/tallui-bot">
            <img src="https://avatars.githubusercontent.com/u/106848579?v=4" width="100;" alt="tallui-bot"/>
            <br />
            <sub><b>tallui-bot</b></sub>
        </a>
    </td>
    <td align="center">
        <a href="https://github.com/janakeks">
            <img src="https://avatars.githubusercontent.com/u/42347662?v=4" width="100;" alt="janakeks"/>
            <br />
            <sub><b>janakeks</b></sub>
        </a>
    </td>
    <td align="center">
        <a href="https://github.com/github-actions[bot]">
            <img src="https://avatars.githubusercontent.com/in/15368?v=4" width="100;" alt="github-actions[bot]"/>
            <br />
            <sub><b>github-actions[bot]</b></sub>
        </a>
    </td>
    <td align="center">
        <a href="https://github.com/dependabot[bot]">
            <img src="https://avatars.githubusercontent.com/in/29110?v=4" width="100;" alt="dependabot[bot]"/>
            <br />
            <sub><b>dependabot[bot]</b></sub>
        </a>
    </td></tr>
<tr>
    <td align="center">
        <a href="https://github.com/renovate[bot]">
            <img src="https://avatars.githubusercontent.com/in/2740?v=4" width="100;" alt="renovate[bot]"/>
            <br />
            <sub><b>renovate[bot]</b></sub>
        </a>
    </td></tr>
</table>
<!-- readme: adrolli,Reinhold-Jesse,collaborators,contributors,weblate,laravel-shift,tallui-bot,bots -end -->


## License

TallUI is free Open-Source software licensed under the [MIT License](LICENSE.md).

Some of the projects we depend on are released under a different license. We do our best to make sure that these licenses allow private as well as commercial use and do not impose any restrictions.

If you notice any problem with TallUI licensing or any dependency, please mail us at dev@tallui.io.


## Security

As mentioned above, we use automated code checks and security audits to ensure that our code is free of security vulnerabilities.

However, if you notice a security vulnerability, please do NOT create an issue for it, but send a mail to dev@tallui.io. This will give us the opportunity to close the vulnerability before others become aware of it and can cause damage.
