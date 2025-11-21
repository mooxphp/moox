<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        (function() {
            const sessionTheme = sessionStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            if (sessionTheme === 'dark' || (!sessionTheme && prefersDark)) {
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>

</head>

<body class="bg-slate-200 text-slate-700 dark:bg-slate-900 dark:text-slate-300" x-data="{
    init() {
            const sessionTheme = sessionStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            this.isDark = sessionTheme === 'dark' || (!sessionTheme && prefersDark);
        },

        isDark: false,

        toggle() {
            this.isDark = !this.isDark;
            document.documentElement.setAttribute('data-theme', this.isDark ? 'dark' : 'light');
            sessionStorage.setItem('theme', this.isDark ? 'dark' : 'light');
        }
}">

    <div class="px-5 border-b border-slate-300 dark:border-slate-700 py-7">
        <div class="flex items-center justify-end gap-5">
            <button @click.prevent="toggle()"
                class="flex items-center gap-2 px-4 py-2 transition hover:text-teal-500 dark:hover:text-teal-400"
                aria-label="Toggle Dark Mode">
                <!-- Sun Icon -->
                <svg x-show="isDark" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                    stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 3v1m0 16v1m8.49-8.49h1M3.51 12H2.5m15.364 6.364l.707.707M5.636 5.636l-.707-.707m12.728 0l.707-.707M5.636 18.364l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>

                <!-- Moon Icon -->
                <svg x-cloak x-show="!isDark" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor"
                    viewBox="0 0 24 24">
                    <path d="M21 12.79A9 9 0 1111.21 3a7 7 0 109.79 9.79z" />
                </svg>
            </button>
        </div>
    </div>


    <div class="container justify-between px-3 mx-auto py-14 md:flex gap-7">


        <div class="md:w-8/12 ">

            @yield('content')

        </div>


        <div class="w-3/12">

            @yield('aside')

            <iframe src="https://time.heco.si" class="w-full h-full">
            </iframe>
        </div>


    </div>


</body>

</html>
