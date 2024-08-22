@extends('layouts.guest')

@section('content')

    <div class="flex flex-col justify-center p-5">
        <h1 class="m-10 font-sans text-4xl text-center">
            The <b>UI</b> for Laravel<br>
            and the <b>TALL</b>-Stack
        </h1>

        <p class="m-5 text-lg leading-6 text-center">
            Welcome to the TallUI DevApp.<br>
            Have a nice dev today!
        </p>

        <div class="flex justify-center m-5">
            <a
                href="https://www.moox.org"
                class="hover:text-[#69bce2] mx-5 px-5 py-2 border-[#002945] bg-[#002945] text-2xl font-extrabold border-2 rounded"
            >moox.org</a>
        </div>
    </div>
    <footer class="mt-105">

        <div class="text-center mb-10">
            <p>Some colorful badges</p>
        </div>

        <div class="flex flex-row items-center justify-center gap-3 mt-3">

            <a href="https://github.com/mooxphp/moox/actions/workflows/pest.yml"><img alt="PEST Tests" src="https://github.com/mooxphp/moox/actions/workflows/pest.yml/badge.svg"></a>
            <a href="https://github.com/mooxphp/moox/actions/workflows/pint.yml"><img alt="Laravel PINT PHP Code Style" src="https://github.com/mooxphp/moox/actions/workflows/pint.yml/badge.svg"></a>
            <a href="https://github.com/mooxphp/moox/actions/workflows/phpstan.yml"><img alt="PHPStan Level 5" src="https://github.com/mooxphp/moox/actions/workflows/phpstan.yml/badge.svg"></a>

        </div>
        <div class="flex flex-row items-center justify-center gap-3 mt-3">

            <a href="https://www.tailwindcss.com"><img alt="TailwindCSS 3" src="https://img.shields.io/badge/TailwindCSS-v3-orange?logo=tailwindcss&color=06B6D4"></a>
            <a href="https://www.alpinejs.dev"><img alt="AlpineJS 3" src="https://img.shields.io/badge/AlpineJS-v3-orange?logo=alpine.js&color=8BC0D0"></a>
            <a href="https://www.laravel.com"><img alt="Laravel 11" src="https://img.shields.io/badge/Laravel-v11-orange?logo=Laravel&color=FF2D20"></a>
            <a href="https://www.laravel-livewire.com"><img alt="Laravel Livewire 2" src="https://img.shields.io/badge/Livewire-v3-orange?logo=livewire&color=4E56A6"></a>

        </div>
        <div class="flex flex-row items-center justify-center gap-3 mt-3">

            <a href="https://app.codacy.com/gh/mooxphp/moox/dashboard"><img src="https://app.codacy.com/project/badge/Grade/2b912412bb6e4892b52688272dec1555" alt="Codacy Code Quality"></a>
            <a href="https://app.codacy.com/gh/mooxphp/moox/dashboard"><img src="https://app.codacy.com/project/badge/Coverage/2b912412bb6e4892b52688272dec1555" alt="Codacy Coverage"></a>
            <a href="https://codeclimate.com/github/mooxphp/moox/maintainability"><img src="https://api.codeclimate.com/v1/badges/567a02eb37ff53d02f5c/maintainability" alt="Code Climate Maintainability"></a>
            <a href="https://snyk.io/test/github/mooxphp/moox"><img alt="Snyk Security" src="https://snyk.io/test/github/mooxphp/moox/badge.svg"></a>

        </div>
        <div class="flex flex-row items-center justify-center gap-3 mt-3">

            <a href="https://github.com/mooxphp/moox/issues/94"><img src="https://img.shields.io/badge/renovate-enabled-brightgreen.svg" alt="Renovate" /></a>
            <a href="https://hosted.weblate.org/engage/moox/"><img src="https://hosted.weblate.org/widgets/moox/-/svg-badge.svg" alt="Translation status" /></a>
            <a href="https://allcontributors.org/"><img alt="All Contributors" src="https://img.shields.io/github/all-contributors/mooxphp/moox"></a>

        </div>
        <div class="flex flex-row items-center justify-center gap-3 mt-3">

            <a href="https://github.com/mooxphp/moox-app-components/blob/main/LICENSE.md"><img alt="License" src="https://img.shields.io/github/license/mooxphp/moox?color=blue&label=license"></a>
            <a href="https://mooxphp.slack.com/"><img alt="Slack" src="https://img.shields.io/badge/Slack-Moox-blue?logo=slack"></a>

        </div>

    </footer>
@stop
