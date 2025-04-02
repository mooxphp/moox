@extends('featherlight::layouts.guest')

@section('content')
    <div class="text-center">
        <h1 class="text-4xl font-extrabold text-gray-900 sm:text-5xl">
            <span class="block">Welcome to</span>
            <span class="block text-indigo-600">Moox Featherlight</span>
        </h1>
        <p class="max-w-md mx-auto mt-3 text-base text-gray-500 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
            A lightweight, elegant Theme, delivered as a package for Laravel.
            <br><br>
            Featherlight is the base theme for Moox. This theme is used as a template and fallback for
            all other themes. You can trust this theme to be up-to-date and fully compatible
            with all Moox packages. We will ever update this theme before all others, and we
            will never do anything that could break compatibility with Moox packages in this theme.
        </p>
        <div class="max-w-md mx-auto mt-5 sm:flex sm:justify-center md:mt-8">
            <div class="rounded-md shadow">
                <a href="#" class="flex items-center justify-center w-full px-8 py-3 text-base font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 md:py-4 md:text-lg md:px-10">
                    Get started
                </a>
            </div>
            <div class="mt-3 rounded-md shadow sm:mt-0 sm:ml-3">
                <a href="#" class="flex items-center justify-center w-full px-8 py-3 text-base font-medium text-indigo-600 bg-white border border-transparent rounded-md hover:bg-gray-50 md:py-4 md:text-lg md:px-10">
                    Learn more
                </a>
            </div>
        </div>
    </div>

    <div class="py-12 bg-white">
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="lg:text-center">
                <h2 class="text-base font-semibold tracking-wide text-indigo-600 uppercase">Features</h2>
                <p class="mt-2 text-3xl font-extrabold leading-8 tracking-tight text-gray-900 sm:text-4xl">
                    Everything you need to build amazing applications
                </p>
                <p class="test-output">
                    Featherlight provides a clean and organized way to start your Laravel projects.
                </p>
            </div>

            <div class="mt-10">
                <dl class="space-y-10 md:space-y-0 md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-10">
                    <div class="relative">
                        <dt>
                            <div class="absolute flex items-center justify-center w-12 h-12 text-white rounded-md bg-violet-500">
                                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                </svg>
                            </div>
                            <p class="ml-16 text-lg font-medium leading-6 text-gray-900">Highly Customizable</p>
                        </dt>
                        <dd class="mt-2 ml-16 text-base text-gray-500">
                            Easily customize and extend to fit your project requirements.
                        </dd>
                    </div>

                    <div class="relative">
                        <dt>
                            <div class="absolute flex items-center justify-center w-12 h-12 text-white rounded-md bg-violet-500">
                                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <p class="ml-16 text-lg font-medium leading-6 text-gray-900">Lightning Fast</p>
                        </dt>
                        <dd class="mt-2 ml-16 text-base text-gray-500">
                            Optimized for performance with minimal overhead.
                        </dd>
                    </div>

                    <div class="relative">
                        <dt>
                            <div class="absolute flex items-center justify-center w-12 h-12 text-white bg-indigo-500 rounded-md">
                                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                </svg>
                            </div>
                            <p class="ml-16 text-lg font-medium leading-6 text-gray-900">Well Documented</p>
                        </dt>
                        <dd class="mt-2 ml-16 text-base text-gray-500">
                            Comprehensive documentation to help you get started quickly.
                        </dd>
                    </div>

                    <div class="relative">
                        <dt>
                            <div class="absolute flex items-center justify-center w-12 h-12 text-white bg-indigo-500 rounded-md">
                                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01" />
                                </svg>
                            </div>
                            <p class="ml-16 text-lg font-medium leading-6 text-gray-900">Modern Architecture</p>
                        </dt>
                        <dd class="mt-2 ml-16 text-base text-gray-500">
                            Built with modern best practices and design patterns.
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
@endsection
