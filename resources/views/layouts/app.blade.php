<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Moox') }}</title>

        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Exo:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        <link rel="icon" href="{{ asset('img/moox-icon.png') }}" type="image/png">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        <x-gradient-background/>

        <div class="min-h-screen">
            <header class="max-w-6xl mx-auto mt-10 mb-20">
                <div class="flex justify-between items-center">
                    <x-logo/>
                    <x-main-nav/>
                </div>
            </header>

            <!-- Page Content -->
            <main class="max-w-6xl mx-auto">
                @yield('content')
            </main>
        </div>

        @stack('modals')
        @livewireScripts
    </body>
</html>
