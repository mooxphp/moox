<x-html class="font-sans border-t-8 border-scarlet-500" :title="isset($title) ? $title . ' - ' . config('app.name') : ''">
    <x-slot name="head">
        <x-social-meta
            :title="$component->title()"
            description="A set of renderless components to utilise in your Laravel Blade views. Built for the TALL stack. Completely open-source."
            :image="asset('/images/socialcard.png')"
        />

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

        @production
            <!-- Fathom - beautiful, simple website analytics -->
            <script src="https://boom.laravel.io/script.js" site="SVSHYYIX" defer></script>
            <!-- / Fathom -->
        @endproduction
    </x-slot>

    {{ $slot }}

    @livewireScripts
    @bukScripts
</x-html>
