<x-html class="font-sans border-t-8 border-scarlet-500" :title="isset($title) ? $title . ' - ' . config('app.name') : ''">
    <x-slot name="head">


        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('/apple-touch-icon.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('/favicon-16x16.png') }}">
        <link rel="manifest" href="{{ asset('/site.webmanifest') }}">
        <meta name="msapplication-TileColor" content="#b91d47">
        <meta name="theme-color" content="#ffffff">

        <script src="{{ mix('js/app.js') }}" defer></script>

        <link href="{{ mix('css/app.css') }}" rel="stylesheet">

        {{ $head ?? '' }}

        @livewireStyles
        @bukStyles


    </x-slot>

    {{ $slot }}

    @livewireScripts
    @bukScripts
</x-html>
