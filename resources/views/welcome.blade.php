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
            <a href="https://www.tallui.io"
                class="hover:text-[#69bce2] mx-5 px-5 py-2 border-[#002945] bg-[#002945] text-2xl font-extrabold border-2 rounded">tallui.io</a>
        </div>
    </div>

    <footer class="mt-105">
        <div class="flex items-center justify-center gap-3 mt-10">

            <a
                href="https://www.codacy.com/gh/usetall/tallui/dashboard?utm_source=github.com&utm_medium=referral&utm_content=usetall/tallui&utm_campaign=Badge_Grade"><img
                    src="https://app.codacy.com/project/badge/Grade/2b912412bb6e4892b52688272dec1555"
                    alt="Codacy Code Quality" /></a>
            <a
                href="https://www.codacy.com/gh/usetall/tallui/dashboard?utm_source=github.com&utm_medium=referral&utm_content=usetall/tallui&utm_campaign=Badge_Coverage"><img
                    src="https://app.codacy.com/project/badge/Coverage/2b912412bb6e4892b52688272dec1555"
                    alt="Codacy Coverage" /></a>
            <a href="https://codeclimate.com/github/usetall/tallui/maintainability"><img
                    src="https://api.codeclimate.com/v1/badges/1b6dae4442e751fd60b9/maintainability"
                    alt="Code Climate Maintainability" /></a>
            <a href="https://scrutinizer-ci.com/g/usetall/tallui/"><img
                    src="https://scrutinizer-ci.com/g/usetall/tallui/badges/quality-score.png?b=main"
                    alt="Scrutinizer Code Quality" /></a>
            <a href="https://github.com/usetall/tallui/actions/workflows/pest.yml"><img alt="PEST Tests"
                    src="https://github.com/usetall/tallui/actions/workflows/pest.yml/badge.svg"></a>
            <a href="https://github.com/usetall/tallui/actions/workflows/pint.yml"><img alt="Laravel PINT PHP Code Style"
                    src="https://github.com/usetall/tallui/actions/workflows/pint.yml/badge.svg"></a>
            <a href="https://github.com/usetall/tallui/actions/workflows/phpstan.yml"><img alt="PHPStan Level 9"
                    src="https://github.com/usetall/tallui/actions/workflows/phpstan.yml/badge.svg"></a>
            <a href="https://hosted.weblate.org/engage/tallui/"><img
                    src="https://hosted.weblate.org/widgets/tallui/-/svg-badge.svg" alt="Weblate Translation status" /></a>

        </div>
    </footer>
@stop
