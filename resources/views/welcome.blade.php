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
    </head>
    <body class="bg-[#001829] bg-[url('/public/img/bg.jpg')] bg-no-repeat bg-right-top text-[#0e9bdc]">
        <head>
            <nav class="flex justify-between m-8">
                <img  src="img/logo.png">
                <div class="flex items-center text-3xl">
                    <a class="px-4 font-normal" href="/">Home</a>
                    <a href="https://github.com/usetall/tallui">
                        <svg viewbox="0 0 16 16" class="w-7 h-7" fill="#0e9adc"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"></path></svg>
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

            <div class="flex justify-center">
                <a href="/custom/alf" class="hover:text-[#69bce2] mx-5 px-5 py-2 border-[#002945] bg-[#002945] text-2xl font-extrabold border-2 rounded">ADR</a>
                <a href="/custom/reinhold" class="hover:text-[#69bce2] mx-5 px-5 py-2 border-[#002945] bg-[#002945] text-2xl font-extrabold border-2 rounded">RJE</a>
                <a href="/custom/kim" class="hover:text-[#69bce2] mx-5 px-5 py-2 border-[#002945] bg-[#002945] text-2xl font-extrabold border-2 rounded">KSP</a>

            </div>
        </div>
    </body>
</html>
