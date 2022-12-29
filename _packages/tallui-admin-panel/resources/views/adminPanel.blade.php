<!DOCTYPE html>
<html class="h-full">

<head>
    <title>TallUI Monorepo - Dev App</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=exo-2:400,600,800" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#001829] bg-no-repeat bg-right-top text-[#0e9bdc] h-full"
    style="background-image: url('{{ asset('img/bg.jpg') }}')">

    <div>
        <!-- Off-canvas menu for mobile, show/hide based on off-canvas menu state. -->

        <div class="relative z-40 md:hidden" role="dialog" aria-modal="true">
            <!--
                    Off-canvas menu backdrop, show/hide based on off-canvas menu state.

                    Entering: "transition-opacity ease-linear duration-300"
                      From: "opacity-0"
                      To: "opacity-100"
                    Leaving: "transition-opacity ease-linear duration-300"
                      From: "opacity-100"
                      To: "opacity-0"
                  -->
            <div class="fixed inset-0 bg-gray-600 bg-opacity-75"></div>

            <div class="fixed inset-0 z-40 flex">
                <!--
                      Off-canvas menu, show/hide based on off-canvas menu state.

                      Entering: "transition ease-in-out duration-300 transform"
                        From: "-translate-x-full"
                        To: "translate-x-0"
                      Leaving: "transition ease-in-out duration-300 transform"
                        From: "translate-x-0"
                        To: "-translate-x-full"
                    -->
                <div class="relative flex flex-col flex-1 w-full max-w-xs pt-5 pb-4 bg-sky-700">
                    <!--
                        Close button, show/hide based on off-canvas menu state.

                        Entering: "ease-in-out duration-300"
                          From: "opacity-0"
                          To: "opacity-100"
                        Leaving: "ease-in-out duration-300"
                          From: "opacity-100"
                          To: "opacity-0"
                      -->
                    <div class="absolute top-0 right-0 pt-2 -mr-12">
                        <button type="button"
                            class="flex items-center justify-center w-10 h-10 ml-1 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                            <span class="sr-only">Close sidebar</span>
                            <!-- Heroicon name: outline/x-mark -->
                            <svg class="w-6 h-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex items-center flex-shrink-0 px-4">
                        <img class="w-auto h-8" src="{{ asset('img/logo.png') }}">

                    </div>
                    <div class="flex-1 h-0 mt-5 overflow-y-auto">
                        <nav class="px-2 space-y-1">
                            <!-- Current: "bg-sky-800 text-white", Default: "text-sky-100 hover:bg-sky-600" -->
                            <a href="#"
                                class="flex items-center px-2 py-2 text-base font-medium text-white rounded-md bg-sky-800 group">
                                <svg class="flex-shrink-0 w-6 h-6 mr-4 text-sky-300" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                    aria-hidden="true">
                                    <path
                                        d="M10.75 15.2q.475.45 1.2.413.725-.038 1.075-.513l5.25-7.325-7.375 5.2q-.5.35-.55 1.05-.05.7.4 1.175ZM12 5q1.375 0 2.512.338 1.138.337 2.263 1.012l-.925.625q-.875-.475-1.825-.725T12 6Q8.675 6 6.338 8.337 4 10.675 4 14q0 1.05.287 2.075Q4.575 17.1 5.1 18h13.8q.575-.95.838-1.975Q20 15 20 13.9q0-.9-.237-1.9-.238-1-.738-1.85l.625-.925q.75 1.25 1.05 2.362.3 1.113.3 2.313 0 1.275-.288 2.4-.287 1.125-.912 2.25-.125.2-.362.325Q19.2 19 18.9 19H5.1q-.275 0-.512-.137-.238-.138-.388-.388-.5-.875-.85-1.975T3 14q0-1.85.7-3.488.7-1.637 1.913-2.862 1.212-1.225 2.862-1.937Q10.125 5 12 5Zm.05 6.95Z" />
                                </svg>
                                Dashboard
                            </a>

                            <a href="#"
                                class="flex items-center px-2 py-2 text-base font-medium rounded-md text-sky-100 hover:bg-sky-600 group">
                                <!-- Heroicon name: outline/users -->
                                <svg class="flex-shrink-0 w-6 h-6 mr-4 text-sky-300" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                </svg>
                                Team
                            </a>

                            <a href="#"
                                class="flex items-center px-2 py-2 text-base font-medium rounded-md text-sky-100 hover:bg-sky-600 group">
                                <!-- Heroicon name: outline/folder -->
                                <svg class="flex-shrink-0 w-6 h-6 mr-4 text-sky-300" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                                </svg>
                                Projects
                            </a>

                            <a href="#"
                                class="flex items-center px-2 py-2 text-base font-medium rounded-md text-sky-100 hover:bg-sky-600 group">
                                <!-- Heroicon name: outline/calendar -->
                                <svg class="flex-shrink-0 w-6 h-6 mr-4 text-sky-300" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                </svg>
                                Calendar
                            </a>

                            <a href="#"
                                class="flex items-center px-2 py-2 text-base font-medium rounded-md text-sky-100 hover:bg-sky-600 group">
                                <!-- Heroicon name: outline/inbox -->
                                <svg class="flex-shrink-0 w-6 h-6 mr-4 text-sky-300" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 00-2.15-1.588H6.911a2.25 2.25 0 00-2.15 1.588L2.35 13.177a2.25 2.25 0 00-.1.661z" />
                                </svg>
                                Documents
                            </a>

                            <a href="#"
                                class="flex items-center px-2 py-2 text-base font-medium rounded-md text-sky-100 hover:bg-sky-600 group">
                                <!-- Heroicon name: outline/chart-bar -->
                                <svg class="flex-shrink-0 w-6 h-6 mr-4 text-sky-300" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                    aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                </svg>
                                Reports
                            </a>
                        </nav>
                    </div>
                </div>

                <div class="flex-shrink-0 w-14" aria-hidden="true">
                    <!-- Dummy element to force sidebar to shrink to fit close icon -->
                </div>
            </div>
        </div>

        <!-- Static sidebar for desktop -->
        <div class="hidden md:fixed md:inset-y-0 md:flex md:w-64 md:flex-col">
            <!-- Sidebar component, swap this element with another sidebar if you like -->
            <div class="flex flex-col flex-grow pt-5 overflow-y-auto">
                <div class="flex items-center flex-shrink-0 px-4">
                    <img class="w-auto h-10" src="{{ asset('img/logo.png') }}">
                </div>
                <div class="flex flex-col flex-1 mt-5">
                    <nav class="flex-1 px-2 pb-4 space-y-1">
                        <!-- Current: "bg-sky-800 text-white", Default: "text-sky-100 hover:bg-sky-600" -->
                        <a href="#"
                            class="flex items-center px-2 py-2 text-sm font-medium text-white rounded-md bg-sky-800 group">
                            <svg class="flex-shrink-0 w-6 h-6 mr-3 text-sky-300" xmlns="http://www.w3.org/2000/svg"
                                fill="currentColor" viewBox="0 0 40 40" stroke-width="1.5" stroke="currentColor"
                                aria-hidden="true">
                                <path
                                    d="M17.458 25.875q.875.875 2.375.813 1.5-.063 2.209-1.105l8.875-13.166-13.125 8.916Q16.75 22 16.667 23.5q-.084 1.5.791 2.375Zm2.5-18.375q2.334 0 4.563.667 2.229.666 4.354 2.166L27 11.667q-1.667-1.042-3.521-1.542-1.854-.5-3.521-.5-5.708 0-9.708 4.021t-4 9.771q0 1.791.5 3.604.5 1.812 1.417 3.396h23.541q.959-1.542 1.438-3.396.479-1.854.479-3.729 0-1.625-.437-3.459-.438-1.833-1.521-3.375L33 14.583q1.417 2.209 2.042 4.292.625 2.083.708 4.292.042 2.291-.5 4.333-.542 2.042-1.625 3.917-.333.666-.833.875-.5.208-1.167.208H8.25q-.583 0-1.125-.292-.542-.291-.833-.833-.959-1.708-1.542-3.667-.583-1.958-.583-4.291 0-3.25 1.229-6.146t3.375-5.063q2.146-2.166 5.041-3.437Q16.708 7.5 19.958 7.5Zm-.125 12.708Z" />
                            </svg> Dashboard
                        </a>

                        <a href="#"
                            class="flex items-center px-2 py-2 text-sm font-medium rounded-md text-sky-100 hover:bg-sky-600 group">
                            <!-- Heroicon name: outline/users -->
                            <svg class="flex-shrink-0 w-6 h-6 mr-3 text-sky-300" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                            Team
                        </a>

                        <a href="#"
                            class="flex items-center px-2 py-2 text-sm font-medium rounded-md text-sky-100 hover:bg-sky-600 group">
                            <!-- Heroicon name: outline/folder -->
                            <svg class="flex-shrink-0 w-6 h-6 mr-3 text-sky-300" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                            </svg>
                            Projects
                        </a>

                        <a href="#"
                            class="flex items-center px-2 py-2 text-sm font-medium rounded-md text-sky-100 hover:bg-sky-600 group">
                            <!-- Heroicon name: outline/calendar -->
                            <svg class="flex-shrink-0 w-6 h-6 mr-3 text-sky-300" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                            </svg>
                            Calendar
                        </a>

                        <a href="#"
                            class="flex items-center px-2 py-2 text-sm font-medium rounded-md text-sky-100 hover:bg-sky-600 group">
                            <!-- Heroicon name: outline/inbox -->
                            <svg class="flex-shrink-0 w-6 h-6 mr-3 text-sky-300" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 00-2.15-1.588H6.911a2.25 2.25 0 00-2.15 1.588L2.35 13.177a2.25 2.25 0 00-.1.661z" />
                            </svg>
                            Documents
                        </a>

                        <a href="#"
                            class="flex items-center px-2 py-2 text-sm font-medium rounded-md text-sky-100 hover:bg-sky-600 group">
                            <!-- Heroicon name: outline/chart-bar -->
                            <svg class="flex-shrink-0 w-6 h-6 mr-3 text-sky-300" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                            Reports
                        </a>
                    </nav>
                </div>
            </div>
        </div>
        <div class="flex flex-col flex-1 md:pl-64">
            <div class="sticky top-0 z-10 flex flex-shrink-0 h-16">
                <button type="button"
                    class="px-4 text-gray-500 border-r border-gray-200 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-sky-500 md:hidden">
                    <span class="sr-only">Open sidebar</span>
                    <!-- Heroicon name: outline/bars-3-bottom-left -->
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" />
                    </svg>
                </button>
                <div class="flex justify-between flex-1 px-4">
                    <div class="flex flex-1">
                        <form class="flex w-full md:ml-0" action="#" method="GET">
                            <label for="search-field" class="sr-only">Search</label>
                            <div class="relative w-full text-gray-400 focus-within:text-gray-600">
                                <div class="absolute inset-y-0 left-0 flex items-center pointer-events-none">
                                    <!-- Heroicon name: mini/magnifying-glass -->
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                        fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd"
                                            d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <input id="search-field"
                                    class="block w-full h-full p-3 py-2 pl-8 pr-3 text-gray-900 placeholder-gray-500 bg-transparent border-transparent focus:border-transparent focus:placeholder-gray-400 focus:outline-none focus:ring-0 sm:text-sm"
                                    placeholder="Search" type="search" name="search">
                            </div>
                        </form>
                    </div>
                    <div class="flex items-center ml-4 md:ml-6">
                        <button type="button"
                            class="p-1 text-gray-400 bg-white rounded-full hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">
                            <span class="sr-only">View notifications</span>
                            <!-- Heroicon name: outline/bell -->
                            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                        </button>

                        <!-- Profile dropdown -->
                        <div x-data="{ isOpen: false }" @click.away="isOpen = false" class="relative ml-3">
                            <div>
                                <button type="button" @click="isOpen = !isOpen"
                                    class="flex items-center max-w-xs text-sm bg-white rounded-full focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2"
                                    id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                                    <span class="sr-only">Open user menu</span>
                                    <img class="w-8 h-8 rounded-full"
                                        src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                                        alt="">
                                </button>
                            </div>

                            <div x-show="isOpen" x-transition:enter="transition ease-out duration-100 transform"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75 transform"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute right-0 w-56 mt-2 origin-top-right rounded-md shadow-lg">
                                <div class="absolute right-0 z-10 w-48 py-1 mt-2 origin-top-right bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                    role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button"
                                    tabindex="-1">
                                    <!-- Active: "bg-gray-100", Not Active: "" -->
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700" role="menuitem"
                                        tabindex="-1" id="user-menu-item-0">Your Profile</a>

                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700" role="menuitem"
                                        tabindex="-1" id="user-menu-item-1">Settings</a>

                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700" role="menuitem"
                                        tabindex="-1" id="user-menu-item-2">Sign out</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <main>
                <div class="py-4">
                    <div class="px-4 mx-auto max-w-7xl sm:px-6 md:px-8">
                        <h1 class="text-2xl font-semibold text-sky-200">Dashboard</h1>
                    </div>
                    <div class="px-4 mx-auto max-w-7xl sm:px-6 md:px-8">
                        <div class="sm:flex sm:items-center">
                            <div class="sm:flex-auto">
                                <h1 class="text-xl font-semibold text-gray-900">Users</h1>
                                <p class="mt-2 text-sm text-gray-700">A list of all the users in your account
                                    including their name, title, email and role.</p>
                            </div>
                            <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                                <button type="button"
                                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white border border-transparent rounded-md shadow-sm bg-sky-800 hover:bg-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 sm:w-auto">Add
                                    user</button>
                            </div>
                        </div>
                        <div class="flex flex-col mt-8">
                            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                                <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                                        <table class="min-w-full divide-y divide-gray-300">
                                            <thead class="bg-sky-900">
                                                <tr>
                                                    <th scope="col"
                                                        class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-sky-100 sm:pl-6">
                                                        Name</th>
                                                    <th scope="col"
                                                        class="px-3 py-3.5 text-left text-sm font-semibold text-gray-100">
                                                        <a href="#" class="inline-flex group">
                                                            Title
                                                            <!-- Active: "bg-gray-200 text-gray-900 group-hover:bg-gray-300", Not Active: "invisible text-gray-400 group-hover:visible group-focus:visible" -->
                                                            <span
                                                                class="flex-none ml-2 text-gray-900 bg-gray-200 rounded group-hover:bg-gray-300">
                                                                <!-- Heroicon name: mini/chevron-down -->
                                                                <svg class="w-5 h-5"
                                                                    xmlns="http://www.w3.org/2000/svg"
                                                                    viewBox="0 0 20 20" fill="currentColor"
                                                                    aria-hidden="true">
                                                                    <path fill-rule="evenodd"
                                                                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                                                        clip-rule="evenodd" />
                                                                </svg>
                                                            </span>
                                                        </a>
                                                    </th>
                                                    <th scope="col"
                                                        class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                                        Status</th>
                                                    <th scope="col"
                                                        class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                                        Role</th>
                                                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                                        <span class="sr-only">Edit</span>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 bg-sky-200">
                                                <tr>
                                                    <td class="py-4 pl-4 pr-3 text-sm whitespace-nowrap sm:pl-6">
                                                        <div class="flex items-center">
                                                            <div class="flex-shrink-0 w-10 h-10">
                                                                <img class="w-10 h-10 rounded-full"
                                                                    src="https://images.unsplash.com/photo-1517841905240-472988babdf9?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                                                                    alt="">
                                                            </div>
                                                            <div class="ml-4">
                                                                <div class="font-medium text-gray-900">Lindsay
                                                                    Walton</div>
                                                                <div class="text-gray-500">
                                                                    lindsay.walton@example.com</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-3 py-4 text-sm text-gray-500 whitespace-nowrap">
                                                        <div class="text-gray-900">Front-end Developer</div>
                                                        <div class="text-gray-500">Optimization</div>
                                                    </td>
                                                    <td class="px-3 py-4 text-sm text-gray-500 whitespace-nowrap">
                                                        <span
                                                            class="inline-flex px-2 text-xs font-semibold leading-5 text-green-800 bg-green-100 rounded-full">Active</span>
                                                    </td>
                                                    <td class="px-3 py-4 text-sm text-gray-500 whitespace-nowrap">
                                                        Member</td>
                                                    <td
                                                        class="relative py-4 pl-3 pr-4 text-sm font-medium text-right whitespace-nowrap sm:pr-6">
                                                        <a href="#"
                                                            class="text-sky-600 hover:text-sky-900">Edit<span
                                                                class="sr-only">, Lindsay Walton</span></a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="py-4 pl-4 pr-3 text-sm whitespace-nowrap sm:pl-6">
                                                        <div class="flex items-center">
                                                            <div class="flex-shrink-0 w-10 h-10">
                                                                <img class="w-10 h-10 rounded-full"
                                                                    src="https://images.unsplash.com/photo-1517841905240-472988babdf9?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                                                                    alt="">
                                                            </div>
                                                            <div class="ml-4">
                                                                <div class="font-medium text-gray-900">Lindsay
                                                                    Walton</div>
                                                                <div class="text-gray-500">
                                                                    lindsay.walton@example.com</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-3 py-4 text-sm text-gray-500 whitespace-nowrap">
                                                        <div class="text-gray-900">Front-end Developer</div>
                                                        <div class="text-gray-500">Optimization</div>
                                                    </td>
                                                    <td class="px-3 py-4 text-sm text-gray-500 whitespace-nowrap">
                                                        <span
                                                            class="inline-flex px-2 text-xs font-semibold leading-5 text-green-800 bg-green-100 rounded-full">Active</span>
                                                    </td>
                                                    <td class="px-3 py-4 text-sm text-gray-500 whitespace-nowrap">
                                                        Member</td>
                                                    <td
                                                        class="relative py-4 pl-3 pr-4 text-sm font-medium text-right whitespace-nowrap sm:pr-6">
                                                        <a href="#"
                                                            class="text-sky-600 hover:text-sky-900">Edit<span
                                                                class="sr-only">, Lindsay Walton</span></a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="py-4 pl-4 pr-3 text-sm whitespace-nowrap sm:pl-6">
                                                        <div class="flex items-center">
                                                            <div class="flex-shrink-0 w-10 h-10">
                                                                <img class="w-10 h-10 rounded-full"
                                                                    src="https://images.unsplash.com/photo-1517841905240-472988babdf9?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                                                                    alt="">
                                                            </div>
                                                            <div class="ml-4">
                                                                <div class="font-medium text-gray-900">Lindsay
                                                                    Walton</div>
                                                                <div class="text-gray-500">
                                                                    lindsay.walton@example.com</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-3 py-4 text-sm text-gray-500 whitespace-nowrap">
                                                        <div class="text-gray-900">Front-end Developer</div>
                                                        <div class="text-gray-500">Optimization</div>
                                                    </td>
                                                    <td class="px-3 py-4 text-sm text-gray-500 whitespace-nowrap">
                                                        <span
                                                            class="inline-flex px-2 text-xs font-semibold leading-5 text-green-800 bg-green-100 rounded-full">Active</span>
                                                    </td>
                                                    <td class="px-3 py-4 text-sm text-gray-500 whitespace-nowrap">
                                                        Member</td>
                                                    <td
                                                        class="relative py-4 pl-3 pr-4 text-sm font-medium text-right whitespace-nowrap sm:pr-6">
                                                        <a href="#"
                                                            class="text-sky-600 hover:text-sky-900">Edit<span
                                                                class="sr-only">, Lindsay Walton</span></a>
                                                    </td>
                                                </tr>
                                                <!-- More people... -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </main>
        </div>
    </div>

</body>

</html>
