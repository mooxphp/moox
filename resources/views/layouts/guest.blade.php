<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Moox') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Exo+2:wght@400;500;700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

        <link rel="icon" href="{{ asset('img/moox-icon.png') }}" type="image/png">

        @vite(['resources/css/app.css'])
        @livewireStyles
        @stack('styles')
    </head>
    <body class="text-gray-100 p-6 bg-gray-900"
    style="
    background: radial-gradient(at right center, rgba(10, 16, 173, 0.5), rgba(0, 5, 27, 0.75)), url('{{ asset('web/space.jpg') }}');
    background-position: center;
    min-height: 100vh;
    ">
        <header class="max-w-6xl mx-auto mt-10 mb-20">
            <div class="flex justify-between items-center">
                <x-logo/>
                <x-main-nav/>
            </div>
        </header>

        <main class="max-w-6xl mx-auto">
            @yield('content')
        </main>

        <footer class="max-w-6xl mx-auto mt-20 mb-10">
            <x-footer/>
        </footer>

        @livewireScripts
        @vite(['resources/js/app.js'])
        @stack('scripts')
    </body>
</html>
