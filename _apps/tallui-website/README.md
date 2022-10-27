<p align="center">
    <img src="../../_others/tallui-art/tallui-logo.svg" width="100" alt="TallUI Logo">
    <br><br>
    <img src="../../_others/tallui-art/tallui-textlogo.svg" width="110" alt="TallUI Textlogo">
</p>


<br>

<p align="center">
    <a href="https://github.com/usetall/tallui/actions/workflows/run-tests.yml">
        <img alt="PEST Tests" src="https://img.shields.io/github/workflow/status/usetall/tallui/run-tests?label=PestPHP">
    </a>
    <a href="https://github.com/usetall/tallui/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain">
        <img alt="Laravel PINT PHP Code Style" src="https://img.shields.io/github/workflow/status/usetall/tallui/Fix%20PHP%20code%20style%20issues?label=Laravel Pint">
    </a>
    <a href="https://github.com/usetall/tallui/actions?query=workflow%3A"PHPStan"+branch%3Amain">
        <img alt="PHPStan Level 5" src="https://img.shields.io/github/workflow/status/usetall/tallui/PHPStan?label=PHPStan">
    </a>
    <a href="https://scrutinizer-ci.com/g/usetall/tallui/?branch=main">
        <img alt="Scrutinizer Code Quality" src="https://scrutinizer-ci.com/g/usetall/tallui/badges/quality-score.png?b=main">
    </a>
    <a href="https://github.com/usetall/tallui/blob/main/LICENSE.md">
        <img alt="License" src="https://img.shields.io/github/license/usetall/tallui">
    </a>
</p>


# TallUI Website

This is the TallUI Website. You can clone this repo and run it locally to see, how our website was built.

## Requirements

Prior to installing Laravel 9 you need

- PHP 8.1
- Git
- Composer
- Node.js / NPM
- A webserver like Apache
- A database server like MySQL

We use and recommend Laragon (Windows) or Laravel Sail (MacOS, Linux, Windows using WSL).

## Installation

```bash
# clone the repo
git clone usetall/tallui-website

# Default environment suits Laragon
# use DB_HOST=mysql for Sail
cp .env.example .env

# Build
composer install

# Run Sail (or start Laragon)
./vendor/bin/sail up

# Run Vite development  
npm run dev
# OR build for production
npm run build
```

Have fun surfin' on http://localhost

