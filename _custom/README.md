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
    <a href="https://www.tailwindcss.com">
        <img alt="TailwindCSS 3" src="https://img.shields.io/badge/TailwindCSS-v3-orange?logo=tailwindcss&color=06B6D4">
    </a>
    <a href="https://www.alpinejs.dev">
        <img alt="AlpineJS 3" src="https://img.shields.io/badge/AlpineJS-v3-orange?logo=alpine.js&color=8BC0D0">
    </a>
    <a href="https://www.laravel.com">
        <img alt="Laravel 10" src="https://img.shields.io/badge/Laravel-v10-orange?logo=Laravel&color=FF2D20">
    </a>
    <a href="https://www.laravel-livewire.com">
        <img alt="Laravel Livewire 2" src="https://img.shields.io/badge/Livewire-v2-orange?logo=livewire&color=4E56A6">
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
    <a href="https://github.com/usetall/tallui/issues/94">
        <img src="https://img.shields.io/badge/renovate-enabled-brightgreen.svg" alt="Renovate" />
    </a>
    <a href="https://hosted.weblate.org/engage/tallui/">
        <img src="https://hosted.weblate.org/widgets/tallui/-/svg-badge.svg" alt="Translation status" />
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

# TallUI Custom

To develop your own packages, public or private, while contributing to TAllUI, we included a second composer.json. This composer-file can be edited without overwriting the main composer.json.


## Custom composer.json

```bash
cp _custom/composer.json-example _custom/composer.json
```

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

Do a ```composer update``` afterwards.


## Custom views and routes

Then you can use following environment variables in .env to create custom views and custom routes without touching existing blade views or routes/web.php:

```shell
CUSTOM_VIEWS="one, two"
CUSTOM_ROUTES="one, two"
```

The last step is to 

```bash
cp resources/views/custom/example.blade.php resources/views/custom/one.blade.php
```

and / or

```bash
cp routes/custom_example.php routes/custom_two.php
```

and use them as custom views or custom routes. You may route into the gitignored subfolders of ```/resources/views/custom``` or your custom package.


## Share custom repos

Keep all files together in "your-repo" (yep, you can call it whatever you want) and share it with other people that develop with TallUI while contributing to the Monorepo.

Execute 

```bash
_custom/publish.sh your_repo
```

to copy all

- php-files prefixed with ```custom_``` from ```/_custom/your_repo/custom/routes``` to ```/routes```
- blade-views from ```/_custom/your_repo/custom/views``` to ```/resources/views/custom```


## Reminder

Don't forget .env
