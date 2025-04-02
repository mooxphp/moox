<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

        @featherlightAssets
        @stack('styles')
    </head>
    <body class="font-sans antialiased text-gray-900 bg-gray-50">
        <div class="flex flex-col min-h-screen">
            <!-- Header -->
            <header class="bg-white border-b border-gray-200">
                <div class="container px-4 py-4 mx-auto">
                    <div class="flex items-center justify-between">
                        <div>
                            <a href="{{ route(Route::has('featherlight.welcome') ? 'featherlight.welcome' : 'welcome') }}" class="text-xl font-bold text-indigo-600">
                                {{ config('app.name', 'Laravel') }}
                            </a>
                        </div>
                        <nav>
                            <ul class="flex space-x-6">
                                <li><a href="#" class="text-gray-600 hover:text-gray-700">Features</a></li>
                                <li><a href="#" class="text-gray-600 hover:text-gray-700">Pricing</a></li>
                                <li><a href="#" class="text-gray-600 hover:text-gray-700">About</a></li>
                                <li><a href="#" class="text-gray-600 hover:text-gray-700">Contact</a></li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-grow py-8">
                <div class="container px-4 mx-auto">
                    @yield('content')
                </div>
            </main>

            <!-- Footer -->
            <footer class="py-6 bg-gray-100 border-t border-gray-200">
                <div class="container px-4 mx-auto">
                    <div class="flex flex-col items-center justify-between md:flex-row">
                        <div class="mb-4 text-sm text-gray-600 md:mb-0">
                            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                        </div>
                        <div class="flex space-x-4">
                            <a href="#" class="text-gray-600 hover:text-gray-700">Privacy Policy</a>
                            <a href="#" class="text-gray-600 hover:text-gray-700">Terms of Service</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>

        @stack('scripts')
    </body>
</html>
