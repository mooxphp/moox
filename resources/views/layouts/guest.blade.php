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
        <header>
            <nav class="flex justify-between m-8">
                <img  src="img/logo.png">
                <div class="flex items-center text-3xl">
                    <a class="px-4 text-lg hover:text-[#69bce2]" href="/">Home</a>
                    <a class="px-4 text-lg hover:text-[#69bce2]" href="/packages">Packages</a>
                    <a class="px-4 text-lg hover:text-[#69bce2]" href="/components">Components</a>
                    <a class="px-4 text-lg hover:text-[#69bce2]" href="/custom">Custom</a>
                    <a class="px-4 text-lg hover:text-[#69bce2]" href="/dashboard">Admin</a>
                    <a class="has-tooltip" href="https://github.com/usetall/tallui">
                        <img class="h-8 px-4" style="filter: drop-shadow(0px 3px 3px #0C9ADC);" src="{{ asset('img/octocat.png') }} " alt="Code on GitHub">
                        <span class='text-xs ml-3 tooltip p-1 rounded bg-sky-900'>GitHub</span>
                    </a>
                </div>
            </nav>
        </header>

        <div id="content" class="mb-10">

            @yield('content')

        </div>

    </body>
</html>
