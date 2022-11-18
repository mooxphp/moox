<!DOCTYPE html>
<html>
    <head>
        <title>TallUI is coming soon</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=exo-2:400,600,800" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body {
                font-family: 'Exo 2', sans-serif;
            }
        </style>
    </head>
    <body class="bg-[#001829] bg-[url('/public/img/bg.jpg')] bg-no-repeat bg-right-top text-[#0e9adc]">
        <head>
            <nav class="flex justify-between m-8">
                <img  src="img/logo.png">
                <div class="flex items-center text-3xl">
                    <a class="px-4 font-normal" href="/">Home</a>
                    <a href="https://github.com/usetall/tallui">
                        <img class="h-8 px-4" src="{{ asset('img/octocat.png') }} " alt="git">

                    </a>
                </div>
            </nav>
        </head>
        <div class="flex flex-col justify-center">
            <h1 class="m-12 font-sans text-4xl text-center">
                The <b>UI</b> for Laravel<br>
                and the <b>TALL</b>-Stack
            </h1>

            <p class="m-12 text-lg leading-6 text-center">
                TallUI is currently under active development.<br>
                Our first components will be available shortly.
            </p>
        </div>
    </body>
</html>
