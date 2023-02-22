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

# TallUI Chart Components

TallUI Chart Components is a collection of Blade and Livewire components for TallUI made with [Apache ECharts](https://echarts.apache.org/), a first-class charting library licensed under Apache License. You can use all of our components without further requirements for development of your own Laravel app or package.

## Components

Work-in-progress. There are no components yet ...

-   W-I-P!

## Requirements

-   [PHP 8.1](https://www.php.net/)
-   [Laravel 10](https://laravel.com/)
-   [Laravel Livewire 2](https://laravel-livewire.com/)
-   [TailwindCSS v3](https://tailwindcss.com/)
-   [Alpine.js v3](https://alpinejs.dev/)

A really good starting point to have the TALL-Stack up and running right away is [Laravel Jetstream](https://jetstream.laravel.com/):

```bash
composer require laravel/jetstream
php artisan jetstream:install livewire
npm install
npm run build
php artisan migrate
```

## Installation

```bash
composer require usetall/tallui-app-components
```
