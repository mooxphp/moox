<p align="center">
    <br>
  	<img src="https://github.com/mooxphp/moox/raw/main/art/moox-logo.png" width="200" alt="Moox Logo">
    <br>
</p><br>

<p align="center">
    <a href="https://github.com/mooxphp/moox/actions/workflows/pest.yml"><img alt="PEST Tests" src="https://github.com/mooxphp/moox/actions/workflows/pest.yml/badge.svg"></a>
    <a href="https://github.com/mooxphp/moox/actions/workflows/pint.yml"><img alt="Laravel PINT PHP Code Style" src="https://github.com/mooxphp/moox/actions/workflows/pint.yml/badge.svg"></a>
    <a href="https://github.com/mooxphp/moox/actions/workflows/phpstan.yml"><img alt="PHPStan Level 5" src="https://github.com/mooxphp/moox/actions/workflows/phpstan.yml/badge.svg"></a>
</p>
<p align="center">
    <a href="https://www.tailwindcss.com"><img alt="TailwindCSS 3" src="https://img.shields.io/badge/TailwindCSS-v3-orange?logo=tailwindcss&color=06B6D4"></a>
    <a href="https://www.alpinejs.dev"><img alt="AlpineJS 3" src="https://img.shields.io/badge/AlpineJS-v3-orange?logo=alpine.js&color=8BC0D0"></a>
    <a href="https://www.laravel.com"><img alt="Laravel 10" src="https://img.shields.io/badge/Laravel-v10-orange?logo=Laravel&color=FF2D20"></a>
    <a href="https://www.laravel-livewire.com"><img alt="Laravel Livewire 2" src="https://img.shields.io/badge/Livewire-v3-orange?logo=livewire&color=4E56A6"></a>
</p>
<p align="center">
    <a href="https://app.codacy.com/gh/mooxphp/moox/dashboard"><img src="https://app.codacy.com/project/badge/Grade/2b912412bb6e4892b52688272dec1555" alt="Codacy Code Quality"></a>
    <a href="https://app.codacy.com/gh/mooxphp/moox/dashboard"><img src="https://app.codacy.com/project/badge/Coverage/2b912412bb6e4892b52688272dec1555" alt="Codacy Coverage"></a>
    <a href="https://codeclimate.com/github/mooxphp/moox/maintainability"><img src="https://api.codeclimate.com/v1/badges/567a02eb37ff53d02f5c/maintainability" alt="Code Climate Maintainability"></a>
    <a href="https://snyk.io/test/github/mooxphp/moox"><img alt="Snyk Security" src="https://snyk.io/test/github/mooxphp/moox/badge.svg"></a>
</p>
<p align="center">
    <a href="https://github.com/mooxphp/moox/issues/94"><img src="https://img.shields.io/badge/renovate-enabled-brightgreen.svg" alt="Renovate" /></a>
    <a href="https://hosted.weblate.org/engage/moox/"><img src="https://hosted.weblate.org/widgets/moox/-/svg-badge.svg" alt="Translation status" /></a>
    <a href="https://github.com/mooxphp/moox-app-components/blob/main/LICENSE.md"><img alt="License" src="https://img.shields.io/github/license/mooxphp/moox?color=blue&label=license"></a>
    <a href="https://mooxphp.slack.com/"><img alt="Slack" src="https://img.shields.io/badge/Slack-Moox-blue?logo=slack"></a>
    <br>
    <br>
</p>

# Moox Monorepo

Welcome to the Moox project. This is the Moox Monorepo. It is an installable Laravel App meant for development of our Filament Plugins aka Laravel Packages. We are in an early stage of development but there are already some plugins you might consider useful:

## Packages

-   [Moox Jobs](jobs/README.md), manage Job Queues, Failed Jobs and Batches in Filament
-   [Moox Builder](builder/README.md), is our Skeleton Package to create new Filament Plugins

All others are in the making. Stay tuned.

## Installation

The Laravel dev app in the root-folder of the Moox Monorepo is made for instant development with Laravel Valet, Laravel Sail or Laragon.

```bash
# Create a .env file and adjust to your needs
cp .env.example .env

# Don't forget to create the database according .env

# Install via Composer
composer install

# Migrate and seed
php artisan migrate:fresh --seed

# Use Vite (for Laravel Sail on Windows: do it in Ubuntu, not inside the Sail container)
npm install
npm run dev
```

Optional things:

```bash
# You can create a user then
php artisan make:filament-user

# You can use the custom composer.json
cp _custom/composer.json-example _custom/composer.json

# Run Sail (alternatively start Herd,Laragon or Valet)
./vendor/bin/sail up

# Rebuild the sail config if needed
./vendor/bin/sail down --rmi all -v
php artisan sail:install

# Remove broken symlinks if needed
# switching from Laragon to Sail for example
rm -Rf vendor/mooxphp
```

The Moox Admin is now available at /moox, e. g. https://moox.test/moox

## Custom packages

Our Monorepo is prepared to be a double agent:

-   Develop a private or public project
-   while contributing to the Moox project

This is done by supporting custom packages in the development app of our Monorepo. Sounds interesting? Read on [\_custom/README.md](_custom/README.md).

## Development

-   Do `npm run build` before committing because automated tests on GitHub needs a working vite-manifest
-   Do `php artisan migrate --database=sqlite` to reflect changes to the test-database
-   Use https://marketplace.visualstudio.com/items?itemName=adrolli.tallui-laravel-livewire-tailwind with VS Code
-   Use https://github.com/mooxphp/builder to create your own packages
-   Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Branching

-   `main` is the current stable version, branch-protected, auto-commits to all packages, deployed to li
-   `feature/...` please prefix all feature-branches, create your pull requests directly to main

Use issue-based branches, prefix them with feature/ (e.g. feature/38-welcome-view) for automated tests and code analysis.

## Commits

Your commit messages will be merged into Changelog.md, means they become part of the documentation. Please make sure, you

-   start with one of these types
    -   Bump = minor version change, major if used as Bump!
    -   Clean = deleting old stuff or unused code
    -   Deps = changing dependencies
    -   Devops = GH and automation
    -   Docs = documentation
    -   Feat = feature
    -   Fix = bugfix
    -   Lang = translation
    -   Tests = writing tests
    -   Wip = work in progress
-   for breaking changes add "!" to any type to craft a major release
-   followed by the shortname of the package, in []
    -   All - multiple or all packages
    -   AdminPanel
    -   Core
    -   PackageBuilder
    -   IconsBuilder
    -   AppComponents
    -   ChartComponents
    -   FormComponents
    -   WebComponents
    -   Monorepo
-   reference an issue, linked by issue number, e. g. #138
-   prepare auto-closing the issue by using "Close #138"

### Valid examples

-   `Wip[Core]: Feature register assets w-i-p #123`
-   `Fix[All]: Update all packages Close #321`
-   `Feat[Monorepo]: Update dev app Close #22`
-   `Bump![ChartComponents]: Major Updates`
-   `Feat![PackageBuilder]: This will become a major release #23`

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

We test Moox using:

-   Monorepo
    -   [Larastan](https://github.com/nunomaduro/larastan), [PHPStan](https://phpstan.org/) Level 5
    -   [Laravel Pint](https://laravel.com/docs/pint), PHP CS Fixer
    -   [Codacy](https://app.codacy.com/gh/mooxphp/moox/) and [Code climate](https://codeclimate.com/github/mooxphp/moox)
-   Packages
    -   [Orchestra Testbench](https://orchestraplatform.readme.io/docs/testbench)
    -   [Larastan](https://github.com/nunomaduro/larastan), [PHPStan](https://phpstan.org/) Level 5
    -   [Laravel Pint](https://laravel.com/docs/pint), PHP CS Fixer
    -   [Pest](https://pestphp.com/)

Please make sure you use the same tools in VS Code (our [VS Code Extension Pack](https://marketplace.visualstudio.com/items?itemName=adrolli.tallui-laravel-livewire-tailwind) covers this) or do the checks manually before committing to the dev-branch:

-   PHPStan: `composer analyse ` or `./vendor/bin/phpstan analyse`, for packages `../../vendor/bin/phpstan analyse`
-   Pest: `composer test ` or `./vendor/bin/pest`, for packages `../../vendor/bin/pest`
-   Coverage: `composer test-coverage ` or `./vendor/bin/pest --coverage`, for packages `../../vendor/bin/pest --coverage`
-   Pint: `composer format ` or `./vendor/bin/pint`, for packages `../../vendor/bin/pint`

## Contributors

Moox is made by these nice people, and bots ...

<!-- readme: adrolli,collaborators,contributors,mooxbot,weblate,laravel-shift,bots,comradekingu,milotype,Givx,OccTherapist,somzoli -start -->
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
    </td>
    <td align="center">
        <a href="https://github.com/mooxbot">
            <img src="https://avatars.githubusercontent.com/u/106848579?v=4" width="100;" alt="mooxbot"/>
            <br />
            <sub><b>mooxbot</b></sub>
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
        <a href="https://github.com/Kim-the-Diamond">
            <img src="https://avatars.githubusercontent.com/u/93331309?v=4" width="100;" alt="Kim-the-Diamond"/>
            <br />
            <sub><b>Kim-the-Diamond</b></sub>
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
        <a href="https://github.com/somzoli">
            <img src="https://avatars.githubusercontent.com/u/34423715?v=4" width="100;" alt="somzoli"/>
            <br />
            <sub><b>somzoli</b></sub>
        </a>
    </td>
    <td align="center">
        <a href="https://github.com/renovate[bot]">
            <img src="https://avatars.githubusercontent.com/in/2740?v=4" width="100;" alt="renovate[bot]"/>
            <br />
            <sub><b>renovate[bot]</b></sub>
        </a>
    </td></tr>
<tr>
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
    </td>
    <td align="center">
        <a href="https://github.com/comradekingu">
            <img src="https://avatars.githubusercontent.com/u/13802408?v=4" width="100;" alt="comradekingu"/>
            <br />
            <sub><b>comradekingu</b></sub>
        </a>
    </td>
    <td align="center">
        <a href="https://github.com/milotype">
            <img src="https://avatars.githubusercontent.com/u/43657314?v=4" width="100;" alt="milotype"/>
            <br />
            <sub><b>milotype</b></sub>
        </a>
    </td>
    <td align="center">
        <a href="https://github.com/givx">
            <img src="https://avatars.githubusercontent.com/u/1196652?v=4" width="100;" alt="givx"/>
            <br />
            <sub><b>givx</b></sub>
        </a>
    </td>
    <td align="center">
        <a href="https://github.com/occtherapist">
            <img src="https://avatars.githubusercontent.com/u/28587659?v=4" width="100;" alt="occtherapist"/>
            <br />
            <sub><b>occtherapist</b></sub>
        </a>
    </td></tr>
</table>
<!-- readme: adrolli,collaborators,contributors,mooxbot,weblate,laravel-shift,bots,comradekingu,milotype,Givx,OccTherapist,somzoli -end -->

## Contribute

We welcome every contribution! It would be awesome, if you:

-   Create an Issue in the Repo that fits best and add information about the problem or idea. We'll reply within a couple of days.
-   Create a Pull Request in this Monorepo. Please do not PR to our read-only repos, they are not prepared for code changes. Only the monorepo has quality gates and automated tests.
-   Translate Moox using [Weblate](https://hosted.weblate.org/engage/moox/).
-   Tell other people about Moox or link to us.
-   Consider a [donation or sponsorship](https://github.com/sponsors/mooxphp).

## Translation

Moox is translated with Weblate. Of course you can also directly edit the translation files in the packages, but using a full-featured translation platform like Weblate might be more convenient.

<a href="https://hosted.weblate.org/engage/moox/">
<img src="https://hosted.weblate.org/widgets/moox/-/open-graph.png" alt="Translation status" /></a>

## License

Moox is free Open-Source software licensed under the [MIT License](LICENSE.md).

Some of the projects we depend on are released under a different license. We do our best to make sure that these licenses allow private as well as commercial use and do not impose any restrictions.

If you notice any problem with Moox licensing or any dependency, please mail us at dev@moox.org.

## Security

As mentioned above, we use automated code checks and security audits to ensure that our code is free of security vulnerabilities.

Read our [Security Policy](SECURITY.md) to learn more about security or report a potential vulnerability. Please DO NOT use the issue tracker for reporting security-related issues.
