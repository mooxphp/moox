<p align="center">
    <br>
  	<img src="packages/brand/public/logo/moox-logo.png" width="200" alt="Moox Logo">
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
    <a href="https://www.laravel.com"><img alt="Laravel 12" src="https://img.shields.io/badge/Laravel-v12-orange?logo=Laravel&color=FF2D20"></a>
    <a href="https://www.laravel-livewire.com"><img alt="Laravel Livewire 2" src="https://img.shields.io/badge/Livewire-v3-orange?logo=livewire&color=4E56A6"></a>
    <a href="https://www.filamentphp.com"><img alt="Filament 3" src="https://img.shields.io/badge/Filament-v4-orange?logo=filament&color=4E56A6"></a>
</p>
<p align="center">
    <a href="https://app.codacy.com/gh/mooxphp/moox/dashboard"><img src="https://app.codacy.com/project/badge/Grade/2b912412bb6e4892b52688272dec1555" alt="Codacy Code Quality"></a>
    <a href="https://app.codacy.com/gh/mooxphp/moox/dashboard"><img src="https://app.codacy.com/project/badge/Coverage/2b912412bb6e4892b52688272dec1555" alt="Codacy Coverage"></a>
    <a href="https://snyk.io/test/github/mooxphp/moox"><img alt="Snyk Security" src="https://snyk.io/test/github/mooxphp/moox/badge.svg"></a>
    <a href="https://github.com/mooxphp/moox/issues/94"><img src="https://img.shields.io/badge/renovate-enabled-brightgreen.svg" alt="Renovate" /></a>
</p>
<p align="center">
    <a href="https://hosted.weblate.org/engage/moox/"><img src="https://hosted.weblate.org/widgets/moox/-/svg-badge.svg" alt="Translation status" /></a>
    <a href="https://allcontributors.org/"><img alt="All Contributors" src="https://img.shields.io/github/all-contributors/mooxphp/moox"></a>
    <a href="https://github.com/mooxphp/moox-app-components/blob/main/LICENSE.md"><img alt="License" src="https://img.shields.io/github/license/mooxphp/moox?color=blue&label=license"></a>
    <a href="https://mooxphp.slack.com/"><img alt="Slack" src="https://img.shields.io/badge/Slack-Moox-blue?logo=slack"></a>
    <br>
    <br>
</p>

# Moox

This is the Monorepo of the Moox Project. It is home of our ecosystem of Laravel packages and Filament plugins that are developed to form a CMS, Shop platform or other website or app.

If you want to install and use Moox, please refer to any of our packages or directly install a Bundle using Moox Core.

## Packages

| Package                                                                | Composer               | Free | Pro | State  |
| ---------------------------------------------------------------------- | ---------------------- | ---- | --- | ------ |
| [Moox Core](https://github.com/mooxphp/core)                           | moox/core              | x    |     | Stable |
| [Moox Jobs](https://github.com/mooxphp/jobs)                           | moox/jobs              | x    |     | Stable |
| [Moox Skeleton](https://github.com/mooxphp/skeleton)                   | moox/skeleton          | x    |     | Stable |
| [Moox Flag Icons Circle](https://github.com/mooxphp/flag-icons-circle) | moox/flag-icons-circle | x    |     | Stable |
| [Moox Flag Icons Origin](https://github.com/mooxphp/flag-icons-origin) | moox/flag-icons-origin | x    |     | Stable |
| [Moox Flag Icons Square](https://github.com/mooxphp/flag-icons-square) | moox/flag-icons-square | x    |     | Stable |
| [Moox Flag Icons Rect](https://github.com/mooxphp/flag-icons-rect)     | moox/flag-icons-rect   | x    |     | Stable |
| [Moox Laravel Icons](https://github.com/mooxphp/laravel-icons)         | moox/laravel-icons     | x    |     | Stable |
| [Moox Media](https://github.com/mooxphp/media)                         | moox/media             | x    |     | Beta   |
| [Moox Data](https://github.com/mooxphp/data)                           | moox/data              | x    |     | Beta   |
| [Moox Localization](https://github.com/mooxphp/localization)           | moox/localization      | x    |     | Beta   |
| [Moox Press](https://github.com/mooxphp/press)                         | moox/press             | x    |     | Beta   |

All others are under hard development.

## Requirements

| Moox Version | Laravel Version | Filament Version | PHP Version |
| ------------ | --------------- | ---------------- | ----------- |
| 2.x          | \> 9.x          | 2.x              | \> 8.0      |
| 3.x          | \> 10.x         | 3.x              | \> 8.1      |
| 4.x          | \> 11.x         | 4.x              | \> 8.2      |

Moox Press packages require WordPress Version 6.7, password hashing is currently not compatible with newer versions. We will fix that soon.

## Installation

Install and use the Monorepos ...

```bash
git clone https://github.com/mooxphp/moox
composer create-project laravel/laravel mooxdev
composer require moox/devlink
php artisan vendor:publish --tag="devlink-config"
php artisan moox:devlink
```

There is another option for running our CI ...

```bash
# Installs a fresh Laravel app and all packages
php ci.php
# or to have a special Laravel version running
php ci.php -l=11.0
# and to clean up the Laravel app
php ci.php -d
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
      <td align="center" valign="top" width="14.28%"><a href="https://allcontributors.org"><img src="https://avatars.githubusercontent.com/u/46410174?v=4?s=100" width="100px;" alt="All Contributors"/><br /><sub><b>All Contributors</b></sub></a><br /><a href="#tool-all-contributors" title="Tools">🔧</a> <a href="https://github.com/mooxphp/moox/commits?author=all-contributors" title="Documentation">📖</a></td>
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
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/flashadvocate"><img src="https://avatars.githubusercontent.com/u/7848492?v=4?s=100" width="100px;" alt="Guybrush"/><br /><sub><b>Guybrush</b></sub></a><br /><a href="https://github.com/mooxphp/moox/commits?author=flashadvocate" title="Code">💻</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/momostafa"><img src="https://avatars.githubusercontent.com/u/12662539?v=4?s=100" width="100px;" alt="momostafa"/><br /><sub><b>momostafa</b></sub></a><br /><a href="#question-momostafa" title="Answering Questions">💬</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/chris-ware"><img src="https://avatars.githubusercontent.com/u/19684457?v=4?s=100" width="100px;" alt="Chris Ware"/><br /><sub><b>Chris Ware</b></sub></a><br /><a href="https://github.com/mooxphp/moox/commits?author=chris-ware" title="Code">💻</a></td>
    </tr>
    <tr>
      <td align="center" valign="top" width="14.28%"><a href="https://www.morphsites.com/"><img src="https://avatars.githubusercontent.com/u/13981922?v=4?s=100" width="100px;" alt="morphsites®"/><br /><sub><b>morphsites®</b></sub></a><br /><a href="https://github.com/mooxphp/moox/commits?author=morphsites-limited" title="Code">💻</a></td>
    </tr>
  </tbody>
</table>

<!-- markdownlint-restore -->
<!-- prettier-ignore-end -->

<!-- ALL-CONTRIBUTORS-LIST:END -->

## License

This repository and all packages are commercial software under the [MIT License](./LICENSE.md).

## Security

Before reporting a security issues, please read our [Security Policy](./SECURITY.md).
