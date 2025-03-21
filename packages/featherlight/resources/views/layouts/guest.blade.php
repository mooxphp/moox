<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: false }" :class="{ 'dark': darkMode }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Exo:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        @vite(['resources/src/app.css', 'resources/src/app.js'], 'build/featherlight')

        @stack('styles')
    </head>
    <body class="font-sans antialiased bg-base-100 text-base-content">
        <div class="flex flex-col min-h-screen">
            <header class="h-[var(--header-height)] border-b border-base-200">
                <div class="container flex items-center justify-between h-full">
                    <div class="flex items-center gap-x-4">
                        <a href="{{ route(Route::has('featherlight.welcome') ? 'featherlight.welcome' : 'welcome') }}" class="text-2xl font-bold">
                            {{ config('app.name', 'Laravel') }}
                        </a>
                        <nav class="items-center hidden md:flex gap-x-4">
                            <a href="#" class="transition-colors hover:text-primary">Features</a>
                            <a href="#" class="transition-colors hover:text-primary">Pricing</a>
                            <a href="#" class="transition-colors hover:text-primary">About</a>
                        </nav>
                    </div>
                    <div class="flex items-center gap-x-4">
                        <button x-on:click="darkMode = !darkMode" class="btn btn-ghost">
                            <span x-show="!darkMode" class="i-heroicons-moon-20-solid"></span>
                            <span x-show="darkMode" class="i-heroicons-sun-20-solid"></span>
                        </button>
                    </div>
                </div>
            </header>

            <main class="flex-1">
                @yield('content')
            </main>

            <footer class="h-[var(--footer-height)] border-t border-base-200">
                <div class="container flex items-center justify-between h-full">
                    <div class="text-sm text-neutral">
                        © {{ date('Y') }} {{ config('app.name', 'Laravel') }}. All rights reserved.
                    </div>
                    <div class="flex items-center gap-x-4">
                        <a href="#" class="transition-colors text-neutral hover:text-primary">
                            <span class="i-heroicons-github-20-solid"></span>
                        </a>
                        <a href="#" class="transition-colors text-neutral hover:text-primary">
                            <span class="i-heroicons-twitter-20-solid"></span>
                        </a>
                    </div>
                </div>
            </footer>
        </div>
        @stack('scripts')
    </body>
</html>
