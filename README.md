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
    <a href="https://www.laravel.com"><img alt="Laravel 11" src="https://img.shields.io/badge/Laravel-v11-orange?logo=Laravel&color=FF2D20"></a>
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
    <a href="https://allcontributors.org/"><img alt="All Contributors" src="https://img.shields.io/github/all-contributors/mooxphp/moox"></a>
</p>
<p align="center">
    <a href="https://github.com/mooxphp/moox-app-components/blob/main/LICENSE.md"><img alt="License" src="https://img.shields.io/github/license/mooxphp/moox?color=blue&label=license"></a>
    <a href="https://mooxphp.slack.com/"><img alt="Slack" src="https://img.shields.io/badge/Slack-Moox-blue?logo=slack"></a>
    <br>
    <br>
</p>

# Moox Project

Welcome to the Moox Project. This is a Monorepo and installable Laravel App to develop our Filament Plugins aka Laravel Packages. We are in an early stage of development but there are already some plugins you might consider useful:

## Packages

-   [Moox Skeleton](packages/skeleton/README.md), our Skeleton Package to create new Filament Plugins
-   [Moox Builder](packages/builder/README.md), our Builder Package to create Filament Resources
-   [Moox Core](packages/core/README.md), required by all of our packages, ships common things
-   [Moox Jobs](packages/jobs/README.md), manage Job Queues, Failed Jobs and Batches in Filament

Some are in productive use but not yet documented:

-   [Moox Expiry](packages/expiry/README.md), define and automate the expiry of your records
-   [Moox User Device](packages/user-device/README.md), manage your users' devices and decide how to handle unknown
-   [Moox User Session](packages/session/README.md), manage your users' session (also in context of devices)

All other packages are under hard development:

-   [Moox Audit](packages/audit/README.md), logging and auditing, security-related and model-related
-   [Moox Flags](packages/flags/README.md), Flags contains Blade Icons for countries, languages and more
-   [Moox Locate](packages/locate/README.md), countries, languages, currencies, country prefixes, timezones
-   [Moox Login Link](packages/login-link/README.md), send Login-Links (aka magic links) to your users
-   [Moox Page](packages/page/README.md), content management, currently abandoned as we use Press
-   [Moox Passkey](packages/passkey/README.md), give users the ability to use Passkeys (Webauthn)
-   [Moox Permission](packages/permission/README.md), manage roles and permissions using Spatie Permission
-   [Moox Press](packages/press/README.md), use WordPress without using WordPress, in Filament
-   [Moox Redis Model](packages/redis-model/README.md), use Redis as plug-in replacement for your models
-   [Moox Security](packages/security/README.md), manage your password security and other security features
-   [Moox Sync](packages/sync/README.md), sync records from server to server, add logic and transformers
-   [Moox Trainings](packages/trainings/README.md), automate trainings, invitations and self-validation
-   [Moox User](packages/user/README.md), manage your users in Filament and give users access to their profile

And there is some other stuff NOT in this repo:

-   [Moox DevOps](https://github.com/mooxphp/moox-server/tree/main/packages/devops), Manage your Forge-Servers, Sites and Deployments
-   [Moox Backup Server UI](https://github.com/mooxphp/moox-server/tree/main/packages/backup-server-ui), Filament UI for Spatie Laravel Backup Server
-   [Moox VS Code Extensions](https://github.com/mooxphp/vscode), our VS code extension pack for Filament devs

## Installation

The Laravel dev app in the root-folder of the Moox Monorepo is made for instant development with Laravel Herd, Laravel Valet, Laravel Sail or Laragon.

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

## Moox Press

To install Moox Press, you need a WordPress running in /public/wp (or another subdirectory, configured in .env).

### Install a fresh WordPress

To install a fresh WordPress, we provide an artisan command, shipped with the Moox Press package:

```bash
php artisan mooxpress:wpinstall
```

The command needs some optimization and runs only "half" on Windows.

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
    -   Repo - Monorepo things
    -   Core
    -   Builder
    -   Jobs
    -   ...
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
    -   [Larastan](https://github.com/nunomaduro/larastan), [PHPStan](https://phpstan.org/) Level 5
    -   [Laravel Pint](https://laravel.com/docs/pint), PHP CS Fixer
    -   [Pest](https://pestphp.com/)

Please make sure you use the same tools in VS Code (our [VS Code Extension Pack](https://marketplace.visualstudio.com/items?itemName=adrolli.tallui-laravel-livewire-tailwind) covers this) or do the checks manually before committing to the dev-branch:

-   PHPStan: `composer analyse ` or `./vendor/bin/phpstan analyse`, for packages `../../vendor/bin/phpstan analyse`
-   Pest: `composer test ` or `./vendor/bin/pest`, for packages `../../vendor/bin/pest`
-   Coverage: `composer test-coverage ` or `./vendor/bin/pest --coverage`, for packages `../../vendor/bin/pest --coverage`
-   Pint: `composer format ` or `./vendor/bin/pint`, for packages `../../vendor/bin/pint`

## Admin Navigation

Titles and sorting in the AdminPanel can be adjusted in the packages configs, but this is the default sorting that keeps everything in place:

```
- Dashboard
- Main - 1000
    - Expiry - 1100
    - Notifications - 1800
- Content - 2000
    - Posts - 2100
    - Pages - 2200
    - Media - 2300
    - Categories - 2400
    - Tags - 2500
    - Comments - 2600
- Custom - 3000
    - ...
- Meta - 4000
    - Wp Meta...
- Custom - 5000
    - ...
- Users - 6000
    - App users - 6010 (Moox Users, Moox Press Users 6015)
    - Site users - 6020
    - Customers - 6030
    - Registrations - 6100
    - Roles - 6200
    - Permissions - 6201
    - Devices - 6300
    - Sessions - 6400
    - Login-Links - 6500
    - Password-Tokens - 6600
    - Passkeys - 6700
- System - 7000
    - Audit - 7500
    - Options (Press) - 7900
- Jobs - 8000
    - Job manager - 8001
    - ...
- Tools - 9000
    - Sync - 9500
    - Backup - 9800
    - Builder - 9990
```

## Contributors

Moox is made by these nice people, and bots ...

<!-- ALL-CONTRIBUTORS-LIST:START - Do not remove or modify this section -->
<!-- prettier-ignore-start -->
<!-- markdownlint-disable -->
<table>
  <tbody>
    <tr>
      <td align="center" valign="top" width="14.28%"><a href="https://alf-drollinger.com"><img src="https://avatars.githubusercontent.com/u/40421928?v=4?s=100" width="100px;" alt="Alf Drollinger"/><br /><sub><b>Alf Drollinger</b></sub></a><br /><a href="#infra-adrolli" title="Infrastructure (Hosting, Build-Tools, etc)">🚇</a> <a href="https://github.com/mooxphp/moox/commits?author=adrolli" title="Code">💻</a> <a href="#design-adrolli" title="Design">🎨</a> <a href="#security-adrolli" title="Security">🛡️</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/AzGasim"><img src="https://avatars.githubusercontent.com/u/104441723?v=4?s=100" width="100px;" alt="Aziz Gasim"/><br /><sub><b>Aziz Gasim</b></sub></a><br /><a href="https://github.com/mooxphp/moox/commits?author=AzGasim" title="Code">💻</a> <a href="#security-AzGasim" title="Security">🛡️</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/KimSpeer"><img src="https://avatars.githubusercontent.com/u/98323532?v=4?s=100" width="100px;" alt="KimSpeer"/><br /><sub><b>KimSpeer</b></sub></a><br /><a href="https://github.com/mooxphp/moox/commits?author=KimSpeer" title="Code">💻</a> <a href="#security-KimSpeer" title="Security">🛡️</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://weblate.org/hosting/"><img src="https://avatars.githubusercontent.com/u/1607653?v=4?s=100" width="100px;" alt="Weblate (bot)"/><br /><sub><b>Weblate (bot)</b></sub></a><br /><a href="#translation-weblate" title="Translation">🌍</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://moox.org/bot"><img src="https://avatars.githubusercontent.com/u/106848579?v=4?s=100" width="100px;" alt="Moox Bot"/><br /><sub><b>Moox Bot</b></sub></a><br /><a href="#tool-mooxbot" title="Tools">🔧</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://design-developer.de/"><img src="https://avatars.githubusercontent.com/u/88349887?v=4?s=100" width="100px;" alt="Reinhold Jesse"/><br /><sub><b>Reinhold Jesse</b></sub></a><br /><a href="https://github.com/mooxphp/moox/commits?author=Reinhold-Jesse" title="Code">💻</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/FMorlock"><img src="https://avatars.githubusercontent.com/u/99252924?v=4?s=100" width="100px;" alt="FMorlock"/><br /><sub><b>FMorlock</b></sub></a><br /><a href="#content-FMorlock" title="Content">🖋</a> <a href="#data-FMorlock" title="Data">🔣</a></td>
    </tr>
    <tr>
      <td align="center" valign="top" width="14.28%"><a href="https://www.gutenberg.blog"><img src="https://avatars.githubusercontent.com/u/42349383?v=4?s=100" width="100px;" alt="Sam Bola"/><br /><sub><b>Sam Bola</b></sub></a><br /><a href="#ideas-wp1111" title="Ideas, Planning, & Feedback">🤔</a> <a href="https://github.com/mooxphp/moox/commits?author=wp1111" title="Code">💻</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/Kim-the-Diamond"><img src="https://avatars.githubusercontent.com/u/93331309?v=4?s=100" width="100px;" alt="Kim Speer"/><br /><sub><b>Kim Speer</b></sub></a><br /><a href="https://github.com/mooxphp/moox/commits?author=Kim-the-Diamond" title="Code">💻</a> <a href="#security-Kim-the-Diamond" title="Security">🛡️</a> <a href="https://github.com/mooxphp/moox/commits?author=Kim-the-Diamond" title="Tests">⚠️</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://laravelshift.com"><img src="https://avatars.githubusercontent.com/u/15991828?v=4?s=100" width="100px;" alt="Laravel Shift"/><br /><sub><b>Laravel Shift</b></sub></a><br /><a href="#tool-laravel-shift" title="Tools">🔧</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/mikagrich"><img src="https://avatars.githubusercontent.com/u/161597019?v=4?s=100" width="100px;" alt="mikagrich"/><br /><sub><b>mikagrich</b></sub></a><br /><a href="https://github.com/mooxphp/moox/commits?author=mikagrich" title="Code">💻</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/somzoli"><img src="https://avatars.githubusercontent.com/u/34423715?v=4?s=100" width="100px;" alt="somogyi.zoltan"/><br /><sub><b>somogyi.zoltan</b></sub></a><br /><a href="https://github.com/mooxphp/moox/commits?author=somzoli" title="Code">💻</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://igorclauss.de"><img src="https://avatars.githubusercontent.com/u/28587659?v=4?s=100" width="100px;" alt="Igor Clauss"/><br /><sub><b>Igor Clauss</b></sub></a><br /><a href="https://github.com/mooxphp/moox/commits?author=occtherapist" title="Code">💻</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/Givx"><img src="https://avatars.githubusercontent.com/u/1196652?v=4?s=100" width="100px;" alt="Greg RG"/><br /><sub><b>Greg RG</b></sub></a><br /><a href="https://github.com/mooxphp/moox/commits?author=givx" title="Code">💻</a></td>
    </tr>
    <tr>
      <td align="center" valign="top" width="14.28%"><a href="https://liberapay.com/kingu/"><img src="https://avatars.githubusercontent.com/u/13802408?v=4?s=100" width="100px;" alt="Allan Nordhøy"/><br /><sub><b>Allan Nordhøy</b></sub></a><br /><a href="#translation-comradekingu" title="Translation">🌍</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://catch-life.com"><img src="https://avatars.githubusercontent.com/u/42347662?v=4?s=100" width="100px;" alt="Jana Brot"/><br /><sub><b>Jana Brot</b></sub></a><br /><a href="#business-janakeks" title="Business development">💼</a></td>
      <td align="center" valign="top" width="14.28%"><a href="http://milotype.de/"><img src="https://avatars.githubusercontent.com/u/43657314?v=4?s=100" width="100px;" alt="Milo Ivir"/><br /><sub><b>Milo Ivir</b></sub></a><br /><a href="#translation-milotype" title="Translation">🌍</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/Mikazil"><img src="https://avatars.githubusercontent.com/u/94830731?v=4?s=100" width="100px;" alt="Mika"/><br /><sub><b>Mika</b></sub></a><br /><a href="https://github.com/mooxphp/moox/commits?author=mikazil" title="Code">💻</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://allcontributors.org"><img src="https://avatars.githubusercontent.com/u/46410174?v=4?s=100" width="100px;" alt="All Contributors"/><br /><sub><b>All Contributors</b></sub></a><br /><a href="#tool-all-contributors" title="Tools">🔧</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://freek.dev"><img src="https://avatars.githubusercontent.com/u/483853?v=4?s=100" width="100px;" alt="Freek Van der Herten"/><br /><sub><b>Freek Van der Herten</b></sub></a><br /><a href="#ideas-freekmurze" title="Ideas, Planning, & Feedback">🤔</a> <a href="https://github.com/mooxphp/moox/commits?author=freekmurze" title="Code">💻</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/apps/renovate"><img src="https://avatars.githubusercontent.com/in/2740?v=4?s=100" width="100px;" alt="renovate[bot]"/><br /><sub><b>renovate[bot]</b></sub></a><br /><a href="#tool-renovate[bot]" title="Tools">🔧</a></td>
    </tr>
    <tr>
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/apps/github-actions"><img src="https://avatars.githubusercontent.com/in/15368?v=4?s=100" width="100px;" alt="github-actions[bot]"/><br /><sub><b>github-actions[bot]</b></sub></a><br /><a href="#tool-github-actions[bot]" title="Tools">🔧</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/apps/dependabot"><img src="https://avatars.githubusercontent.com/in/29110?v=4?s=100" width="100px;" alt="dependabot[bot]"/><br /><sub><b>dependabot[bot]</b></sub></a><br /><a href="#tool-dependabot[bot]" title="Tools">🔧</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/5mikachu"><img src="https://avatars.githubusercontent.com/u/80130106?v=4?s=100" width="100px;" alt="Mikachu"/><br /><sub><b>Mikachu</b></sub></a><br /><a href="https://github.com/mooxphp/moox/commits?author=5mikachu" title="Code">💻</a></td>
      <td align="center" valign="top" width="14.28%"><a href="http://www.nplob.com"><img src="https://avatars.githubusercontent.com/u/81469659?v=4?s=100" width="100px;" alt="simmon"/><br /><sub><b>simmon</b></sub></a><br /><a href="https://github.com/mooxphp/moox/commits?author=simmon-nplob" title="Code">💻</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/kalpeshmahida"><img src="https://avatars.githubusercontent.com/u/11972372?v=4?s=100" width="100px;" alt="Kalpesh Mahida"/><br /><sub><b>Kalpesh Mahida</b></sub></a><br /><a href="https://github.com/mooxphp/moox/commits?author=kalpeshmahida" title="Code">💻</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/rowlin"><img src="https://avatars.githubusercontent.com/u/9290549?v=4?s=100" width="100px;" alt="rowlin"/><br /><sub><b>rowlin</b></sub></a><br /><a href="https://github.com/mooxphp/moox/commits?author=rowlin" title="Code">💻</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/mvdnbrk"><img src="https://avatars.githubusercontent.com/u/802681?v=4?s=100" width="100px;" alt="Mark van den Broek"/><br /><sub><b>Mark van den Broek</b></sub></a><br /><a href="https://github.com/mooxphp/moox/commits?author=mvdnbrk" title="Code">💻</a></td>
    </tr>
    <tr>
      <td align="center" valign="top" width="14.28%"><a href="https://sebastiandedeyne.com"><img src="https://avatars.githubusercontent.com/u/1561079?v=4?s=100" width="100px;" alt="Sebastian De Deyne"/><br /><sub><b>Sebastian De Deyne</b></sub></a><br /><a href="https://github.com/mooxphp/moox/commits?author=sebastiandedeyne" title="Code">💻</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://pforret.github.io/"><img src="https://avatars.githubusercontent.com/u/474312?v=4?s=100" width="100px;" alt="Peter Forret"/><br /><sub><b>Peter Forret</b></sub></a><br /><a href="https://github.com/mooxphp/moox/commits?author=pforret" title="Code">💻</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/Atalanttore"><img src="https://avatars.githubusercontent.com/u/628474?v=4?s=100" width="100px;" alt="Ettore Atalan"/><br /><sub><b>Ettore Atalan</b></sub></a><br /><a href="#translation-Atalanttore" title="Translation">🌍</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/p-paul"><img src="https://avatars.githubusercontent.com/u/26795401?v=4?s=100" width="100px;" alt="p-paul"/><br /><sub><b>p-paul</b></sub></a><br /><a href="https://github.com/mooxphp/moox/commits?author=p-paul" title="Code">💻</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/salhdev"><img src="https://avatars.githubusercontent.com/u/16446153?v=4?s=100" width="100px;" alt="Salh"/><br /><sub><b>Salh</b></sub></a><br /><a href="https://github.com/mooxphp/moox/commits?author=salhdev" title="Code">💻</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/Filefabrik"><img src="https://avatars.githubusercontent.com/u/84433563?v=4?s=100" width="100px;" alt="Filefabrik"/><br /><sub><b>Filefabrik</b></sub></a><br /><a href="https://github.com/mooxphp/moox/commits?author=Filefabrik" title="Code">💻</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://completecodesolutions.com/"><img src="https://avatars.githubusercontent.com/u/1786783?v=4?s=100" width="100px;" alt="Matt Rabe"/><br /><sub><b>Matt Rabe</b></sub></a><br /><a href="https://github.com/mooxphp/moox/commits?author=mattrabe" title="Code">💻</a></td>
    </tr>
    <tr>
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/rogash"><img src="https://avatars.githubusercontent.com/u/4563683?v=4?s=100" width="100px;" alt="Alexandre Mendes"/><br /><sub><b>Alexandre Mendes</b></sub></a><br /><a href="https://github.com/mooxphp/moox/pulls?q=is%3Apr+reviewed-by%3Arogash" title="Reviewed Pull Requests">👀</a></td>
      <td align="center" valign="top" width="14.28%"><a href="http://www.rodrigoborges.com.br"><img src="https://avatars.githubusercontent.com/u/5695498?v=4?s=100" width="100px;" alt="Rodrigo Borges"/><br /><sub><b>Rodrigo Borges</b></sub></a><br /><a href="https://github.com/mooxphp/moox/pulls?q=is%3Apr+reviewed-by%3Arodrigoborges" title="Reviewed Pull Requests">👀</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/thiago25787"><img src="https://avatars.githubusercontent.com/u/1185968?v=4?s=100" width="100px;" alt="Thiago Almeida"/><br /><sub><b>Thiago Almeida</b></sub></a><br /><a href="https://github.com/mooxphp/moox/pulls?q=is%3Apr+reviewed-by%3Athiago25787" title="Reviewed Pull Requests">👀</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/marcelonogueira"><img src="https://avatars.githubusercontent.com/u/15114097?v=4?s=100" width="100px;" alt="Marcelo Nogueira"/><br /><sub><b>Marcelo Nogueira</b></sub></a><br /><a href="https://github.com/mooxphp/moox/pulls?q=is%3Apr+reviewed-by%3Amarcelonogueira" title="Reviewed Pull Requests">👀</a></td>
    </tr>
  </tbody>
</table>

<!-- markdownlint-restore -->
<!-- prettier-ignore-end -->

<!-- ALL-CONTRIBUTORS-LIST:END -->

This table is generated by [All Contributors Bot](https://allcontributors.org/). To add contributors use the following command in any comment of an issue or PR:

`@all-contributors please add @github-user for code`

We typically use "code" and "translation", but there are other possible types listed on [AllContributers.org](https://allcontributors.org/docs/en/emoji-key). Please do only one request at a time, as we'll run into merge conflicts if you try to add multiple contributors without merging the PR in between.

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
