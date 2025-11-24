<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    {{-- Alpine.js is required for dropdowns and theme switching --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.15.2/dist/cdn.min.js"></script>


    {{-- Package CSS and JS --}}
     {{--  
    @if(isset($templateMinimalAssets))
   
        @foreach($templateMinimalAssets['css'] ?? [] as $css)
            <link rel="stylesheet" href="{{ $css }}">
        @endforeach
        @foreach($templateMinimalAssets['js'] ?? [] as $js)
            <script type="module" src="{{ $js }}"></script>
        @endforeach
        
    @else
    --}}
        {{-- Fallback: Try to use @vite if assets are not available --}}
        @vite(['resources/css/app.css'], $templateMinimalBuildPath ?? '../packages/template-minimal/public/build')
    {{-- @endif --}}

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
            this.themeMode = sessionTheme ? (sessionTheme === 'dark' ? 'dark' : 'light') : 'system';
        },

        isDark: false,
        themeMode: 'system',

        toggle() {
            this.isDark = !this.isDark;
            document.documentElement.setAttribute('data-theme', this.isDark ? 'dark' : 'light');
            sessionStorage.setItem('theme', this.isDark ? 'dark' : 'light');
        }
}">


    <!--auth nav top-->
    <div class="py-2 text-sm px-7 bg-slate-800 text-slate-200">
        <div class="flex items-center justify-between">
            <ul class="flex items-center gap-4">
                <!--
      <li><a href="#" class="hover:text-slate-400">Home</a></li>
      <li><a href="#" class="hover:text-slate-400">About</a></li>
      <li><a href="#" class="hover:text-slate-400">Contact</a></li>
      -->
                <li>
                    <x-moox-dropdown>
                        <div tabindex="0" role="button"class="flex items-center gap-1 cursor-pointer">
                            Neu
                            <svg class="inline w-3 h-3 ml-1" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path d="M6 9l6 6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        <x-moox-dropdown-liste tabindex="-1">
                            <li><a href="#"
                                    class="flex items-center px-3 py-2 text-sm rounded-lg gap-x-3 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-slate-100 dark:text-neutral-400 dark:hover:bg-slate-800 dark:focus:bg-slate-800">Beitrag</a>
                            </li>
                            <li><a href="#"
                                    class="flex items-center px-3 py-2 text-sm rounded-lg gap-x-3 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-slate-100 dark:text-neutral-400 dark:hover:bg-slate-800 dark:focus:bg-slate-800">Seite</a>
                            </li>
                            <li><a href="#"
                                    class="flex items-center px-3 py-2 text-sm rounded-lg gap-x-3 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-slate-100 dark:text-neutral-400 dark:hover:bg-slate-800 dark:focus:bg-slate-800">Schulung</a>
                            </li>
                        </x-moox-dropdown-liste>
                    </x-moox-dropdown>
                </li>
            </ul>


            <x-moox-dropdown class="relative">
                <button type="button" role="button"  class="flex items-center gap-2 cursor-pointer">
                    <img class="rounded-full shrink-0 size-7"
                        src="https://heco.in/wp/wp-content/uploads/2023/03/Jesse_Reinhold_03_web_243x243.jpg"
                        alt="Avatar"><span>Reinhold Jesse</span>
                </button>
                <x-moox-dropdown-liste
                    class="absolute right-0 z-10 w-40 mt-1 overflow-hidden bg-white border rounded-md shadow-lg text-slate-800 border-slate-200 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">
                    <li>
                        <!-- Switch/Toggle -->
                        <div class="flex items-center justify-between gap-2">
                            <span
                                class="flex-1 text-sm cursor-pointer text-slate-600 dark:text-neutral-400">Theme</span>
                            <div
                                class="p-0.5 w-full flex justify-between items-center gap-2 cursor-pointer bg-slate-100 rounded-full dark:bg-slate-800 dark:hover:bg-slate-700">
                                <!-- Theme: Light -->
                                <button type="button"
                                    class="flex items-center justify-center rounded-full size-7 text-slate-800 dark:text-neutral-200"
                                    :class="{ 'ring-2 ring-primary-500': !isDark && themeMode!=='system' }"
                                    @click.prevent="isDark = false; themeMode = 'light'; document.documentElement.setAttribute('data-theme', 'light'); sessionStorage.setItem('theme', 'light')">
                                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24"
                                        height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="4"></circle>
                                        <path d="M12 3v1"></path>
                                        <path d="M12 20v1"></path>
                                        <path d="M3 12h1"></path>
                                        <path d="M20 12h1"></path>
                                        <path d="m18.364 5.636-.707.707"></path>
                                        <path d="m6.343 17.657-.707.707"></path>
                                        <path d="m5.636 5.636.707.707"></path>
                                        <path d="m17.657 17.657.707.707"></path>
                                    </svg>
                                    <span class="sr-only">Default (Light)</span>
                                </button>
                                <!-- Theme: Dark -->
                                <button type="button"
                                    class="flex items-center justify-center rounded-full size-7 text-slate-800 dark:text-neutral-200"
                                    :class="{ 'ring-2 ring-primary-500': isDark && themeMode!=='system' }"
                                    @click.prevent="isDark = true; themeMode = 'dark'; document.documentElement.setAttribute('data-theme', 'dark'); sessionStorage.setItem('theme', 'dark')">
                                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24"
                                        height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"></path>
                                    </svg>
                                    <span class="sr-only">Dark</span>
                                </button>
                                <!-- Theme: System/Auto -->
                                <button type="button"
                                    class="flex items-center justify-center rounded-full size-7 text-slate-800 dark:text-neutral-200"
                                    :class="{ 'ring-2 ring-primary-500': themeMode === 'system' }"
                                    @click.prevent="
                      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                      isDark = prefersDark;
                      themeMode = 'system';
                      document.documentElement.setAttribute('data-theme', prefersDark ? 'dark' : 'light');
                      sessionStorage.removeItem('theme');
                  ">
                                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24"
                                        height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect width="20" height="14" x="2" y="3" rx="2"></rect>
                                        <line x1="8" x2="16" y1="21" y2="21"></line>
                                        <line x1="12" x2="12" y1="17" y2="21"></line>
                                    </svg>
                                    <span class="sr-only">Auto (System)</span>
                                </button>
                            </div>
                        </div>
                        <!-- End Switch/Toggle -->
                    </li>
                    <li>
                        <a class="flex items-center px-3 py-2 text-sm rounded-lg gap-x-3 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-slate-100 dark:text-neutral-400 dark:hover:bg-slate-800 dark:focus:bg-slate-800"
                            href="#">
                            <svg class="shrink-0 mt-0.5 size-4" xmlns="http://www.w3.org/2000/svg" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            Profile
                        </a>
                    </li>
                    <li>
                        <a class="flex items-center px-3 py-2 text-sm rounded-lg gap-x-3 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-slate-100 dark:text-neutral-400 dark:hover:bg-slate-800 dark:focus:bg-slate-800"
                            href="#">
                            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path
                                    d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z">
                                </path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            Settings
                        </a>
                    </li>
                    <li>
                        <a class="flex items-center px-3 py-2 text-sm rounded-lg gap-x-3 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:pointer-events-none focus:outline-hidden focus:bg-slate-100 dark:text-neutral-400 dark:hover:bg-slate-800 dark:focus:bg-slate-800"
                            href="#">
                            <svg class="shrink-0 mt-0.5 size-4" xmlns="http://www.w3.org/2000/svg" width="24"
                                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m16 17 5-5-5-5"></path>
                                <path d="M21 12H9"></path>
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            </svg>
                            Log out
                        </a>
                    </li>
                </x-moox-dropdown-liste>
            </x-moox-dropdown>
        </div>

    </div>
    <!--auth nav top end-->


    <div class="container mx-auto">
        <!-- header navigation start -->
        <div class="">
            <nav class="flex justify-between py-10 items-centert">
                <div class="flex items-center gap-2">
                    <figure>
                        <img src="https://heco.in/wp/wp-content/uploads/2021/08/cropped-heco-signet-200-67x67.png"
                            alt="" title="" />
                    </figure>
                    <span class="text-2xl">heco Intranet</span>
                </div>

                <ul class="flex items-center gap-7 text-[#0170b9]">
                    <li><a href="" class="hover:text-[#04588f]">Startseite</a></li>
                    <li><a href="" class="hover:text-[#04588f]">Wiki</a></li>
                    <li><a href="" class="hover:text-[#04588f]">Themen</a></li>
                    <li><a href="" class="hover:text-[#04588f]">Prio-App</a></li>
                    <li><a href="" class="hover:text-[#04588f]">Support</a></li>
                    <li><a href="" class="hover:text-[#04588f]">TimeCard</a></li>
                    <li><a href="" class="hover:text-[#04588f]">

                            <svg style="width:24px; margin-top:7px; margin-left:-10px;"
                                xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24"
                                viewBox="0 0 24 24" fill="currentColor" stroke="currentColor">
                                <g>
                                    <path d="M0,0h24v24H0V0z" fill="none"></path>
                                </g>
                                <g>
                                    <path
                                        d="M11.99,2C6.47,2,2,6.48,2,12s4.47,10,9.99,10C17.52,22,22,17.52,22,12S17.52,2,11.99,2z M15.29,16.71L11,12.41V7h2v4.59 l3.71,3.71L15.29,16.71z">
                                    </path>
                                </g>
                            </svg></a></li>

                    <li><a href="" class="hover:text-[#04588f]">
                            <svg xmlns="http://www.w3.org/2000/svg" height="27px" viewBox="0 -960 960 960"
                                width="24px" fill="currentColor" stroke="currentColor">
                                <style>
                                    .sq1,
                                    .sq2,
                                    .sq3,
                                    .sq4 {
                                        opacity: 0;
                                        animation: showup 1s linear forwards;
                                    }

                                    .sq1 {
                                        animation-delay: 0.5s;
                                    }

                                    .sq2 {
                                        animation-delay: 1s;
                                    }

                                    .sq3 {
                                        animation-delay: 1.5s;
                                    }

                                    .sq4 {
                                        animation-delay: 2s;
                                    }

                                    @keyframes showup {
                                        to {
                                            opacity: 1;
                                        }
                                    }

                                    @keyframes loop {

                                        0%,
                                        100% {
                                            opacity: 1;
                                        }

                                        25%,
                                        75% {
                                            opacity: 0;
                                        }
                                    }
                                </style>
                                <path class="sq1" d="M111.87-520v-328.13H440V-520H111.87Z"></path>
                                <path class="sq2" d="M111.87-111.87V-440H440v328.13H111.87Z"></path>
                                <path class="sq3" d="M520-520v-328.13h328.13V-520H520Z"></path>
                                <path class="sq4" d="M520-111.87V-440h328.13v328.13H520Z"></path>
                            </svg>
                        </a></li>
                </ul>
            </nav>
        </div>
        <!-- header navigation end -->


        <div class="justify-between px-3 py-14 md:flex gap-7">

            <div class="md:w-9/12">
                @yield('content')
            </div>

            <div class="w-3/12">

                @yield('aside')

                <iframe src="https://time.heco.si" class="w-full h-full">
                </iframe>
            </div>
        </div>
    </div>


    <!-- footer start -->
    <div class="bg-slate-300">
        <div class="container mx-auto py-14">
            <ul class="flex items-center justify-center gap-14">
                <li><a href="#" class="flex items-center gap-2 text-[#557dbc]">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" fill="currentColor"
                            stroke="currentColor" class="size-8">
                            <path
                                d="M400 32H48A48 48 0 0 0 0 80v352a48 48 0 0 0 48 48h137.25V327.69h-63V256h63v-54.64c0-62.15 37-96.48 93.67-96.48 27.14 0 55.52 4.84 55.52 4.84v61h-31.27c-30.81 0-40.42 19.12-40.42 38.73V256h68.78l-11 71.69h-57.78V480H400a48 48 0 0 0 48-48V80a48 48 0 0 0-48-48z">
                            </path>
                        </svg>
                        <span>Facebook</span>
                    </a></li>
                <li><a href="#" class="flex items-center gap-2 text-[#7acdee]">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="currentColor"
                            stroke="currentColor" class="size-8">
                            <path
                                d="M459.37 151.716c.325 4.548.325 9.097.325 13.645 0 138.72-105.583 298.558-298.558 298.558-59.452 0-114.68-17.219-161.137-47.106 8.447.974 16.568 1.299 25.34 1.299 49.055 0 94.213-16.568 130.274-44.832-46.132-.975-84.792-31.188-98.112-72.772 6.498.974 12.995 1.624 19.818 1.624 9.421 0 18.843-1.3 27.614-3.573-48.081-9.747-84.143-51.98-84.143-102.985v-1.299c13.969 7.797 30.214 12.67 47.431 13.319-28.264-18.843-46.781-51.005-46.781-87.391 0-19.492 5.197-37.36 14.294-52.954 51.655 63.675 129.3 105.258 216.365 109.807-1.624-7.797-2.599-15.918-2.599-24.04 0-57.828 46.782-104.934 104.934-104.934 30.213 0 57.502 12.67 76.67 33.137 23.715-4.548 46.456-13.32 66.599-25.34-7.798 24.366-24.366 44.833-46.132 57.827 21.117-2.273 41.584-8.122 60.426-16.243-14.292 20.791-32.161 39.308-52.628 54.253z">
                            </path>
                        </svg>
                        <span>Twitter</span>
                    </a></li>
                <li><a href="#" class="flex items-center gap-2 text-[#8a3ab9]">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" fill="currentColor"
                            stroke="currentColor" class="size-8">
                            <path
                                d="M224,202.66A53.34,53.34,0,1,0,277.36,256,53.38,53.38,0,0,0,224,202.66Zm124.71-41a54,54,0,0,0-30.41-30.41c-21-8.29-71-6.43-94.3-6.43s-73.25-1.93-94.31,6.43a54,54,0,0,0-30.41,30.41c-8.28,21-6.43,71.05-6.43,94.33S91,329.26,99.32,350.33a54,54,0,0,0,30.41,30.41c21,8.29,71,6.43,94.31,6.43s73.24,1.93,94.3-6.43a54,54,0,0,0,30.41-30.41c8.35-21,6.43-71.05,6.43-94.33S357.1,182.74,348.75,161.67ZM224,338a82,82,0,1,1,82-82A81.9,81.9,0,0,1,224,338Zm85.38-148.3a19.14,19.14,0,1,1,19.13-19.14A19.1,19.1,0,0,1,309.42,189.74ZM400,32H48A48,48,0,0,0,0,80V432a48,48,0,0,0,48,48H400a48,48,0,0,0,48-48V80A48,48,0,0,0,400,32ZM382.88,322c-1.29,25.63-7.14,48.34-25.85,67s-41.4,24.63-67,25.85c-26.41,1.49-105.59,1.49-132,0-25.63-1.29-48.26-7.15-67-25.85s-24.63-41.42-25.85-67c-1.49-26.42-1.49-105.61,0-132,1.29-25.63,7.07-48.34,25.85-67s41.47-24.56,67-25.78c26.41-1.49,105.59-1.49,132,0,25.63,1.29,48.33,7.15,67,25.85s24.63,41.42,25.85,67.05C384.37,216.44,384.37,295.56,382.88,322Z">
                            </path>
                        </svg>
                        <span>Instagram</span>
                    </a></li>
                <li><a href="#" class="flex items-center gap-2 text-[#24292e]">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 496 512" fill="currentColor"
                            stroke="currentColor" class="size-8">
                            <path
                                d="M165.9 397.4c0 2-2.3 3.6-5.2 3.6-3.3.3-5.6-1.3-5.6-3.6 0-2 2.3-3.6 5.2-3.6 3-.3 5.6 1.3 5.6 3.6zm-31.1-4.5c-.7 2 1.3 4.3 4.3 4.9 2.6 1 5.6 0 6.2-2s-1.3-4.3-4.3-5.2c-2.6-.7-5.5.3-6.2 2.3zm44.2-1.7c-2.9.7-4.9 2.6-4.6 4.9.3 2 2.9 3.3 5.9 2.6 2.9-.7 4.9-2.6 4.6-4.6-.3-1.9-3-3.2-5.9-2.9zM244.8 8C106.1 8 0 113.3 0 252c0 110.9 69.8 205.8 169.5 239.2 12.8 2.3 17.3-5.6 17.3-12.1 0-6.2-.3-40.4-.3-61.4 0 0-70 15-84.7-29.8 0 0-11.4-29.1-27.8-36.6 0 0-22.9-15.7 1.6-15.4 0 0 24.9 2 38.6 25.8 21.9 38.6 58.6 27.5 72.9 20.9 2.3-16 8.8-27.1 16-33.7-55.9-6.2-112.3-14.3-112.3-110.5 0-27.5 7.6-41.3 23.6-58.9-2.6-6.5-11.1-33.3 2.6-67.9 20.9-6.5 69 27 69 27 20-5.6 41.5-8.5 62.8-8.5s42.8 2.9 62.8 8.5c0 0 48.1-33.6 69-27 13.7 34.7 5.2 61.4 2.6 67.9 16 17.7 25.8 31.5 25.8 58.9 0 96.5-58.9 104.2-114.8 110.5 9.2 7.9 17 22.9 17 46.4 0 33.7-.3 75.4-.3 83.6 0 6.5 4.6 14.4 17.3 12.1C428.2 457.8 496 362.9 496 252 496 113.3 383.5 8 244.8 8zM97.2 352.9c-1.3 1-1 3.3.7 5.2 1.6 1.6 3.9 2.3 5.2 1 1.3-1 1-3.3-.7-5.2-1.6-1.6-3.9-2.3-5.2-1zm-10.8-8.1c-.7 1.3.3 2.9 2.3 3.9 1.6 1 3.6.7 4.3-.7.7-1.3-.3-2.9-2.3-3.9-2-.6-3.6-.3-4.3.7zm32.4 35.6c-1.6 1.3-1 4.3 1.3 6.2 2.3 2.3 5.2 2.6 6.5 1 1.3-1.3.7-4.3-1.3-6.2-2.2-2.3-5.2-2.6-6.5-1zm-11.4-14.7c-1.6 1-1.6 3.6 0 5.9 1.6 2.3 4.3 3.3 5.6 2.3 1.6-1.3 1.6-3.9 0-6.2-1.4-2.3-4-3.3-5.6-2z">
                            </path>
                        </svg>
                        <span>GitHub</span>
                    </a></li>
                <li><a href="#" class="flex items-center gap-2 text-[#e96651]">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" fill="currentColor"
                            stroke="currentColor" class="size-8">
                            <path
                                d="M549.655 124.083c-6.281-23.65-24.787-42.276-48.284-48.597C458.781 64 288 64 288 64S117.22 64 74.629 75.486c-23.497 6.322-42.003 24.947-48.284 48.597-11.412 42.867-11.412 132.305-11.412 132.305s0 89.438 11.412 132.305c6.281 23.65 24.787 41.5 48.284 47.821C117.22 448 288 448 288 448s170.78 0 213.371-11.486c23.497-6.321 42.003-24.171 48.284-47.821 11.412-42.867 11.412-132.305 11.412-132.305s0-89.438-11.412-132.305zm-317.51 213.508V175.185l142.739 81.205-142.739 81.201z">
                            </path>
                        </svg>
                        <span>Youtube</span>
                    </a></li>
                <li><a href="#" class="flex items-center gap-2 text-[#1c86c6]">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" fill="currentColor"
                            stroke="currentColor" class="size-8">
                            <path
                                d="M416 32H31.9C14.3 32 0 46.5 0 64.3v383.4C0 465.5 14.3 480 31.9 480H416c17.6 0 32-14.5 32-32.3V64.3c0-17.8-14.4-32.3-32-32.3zM135.4 416H69V202.2h66.5V416zm-33.2-243c-21.3 0-38.5-17.3-38.5-38.5S80.9 96 102.2 96c21.2 0 38.5 17.3 38.5 38.5 0 21.3-17.2 38.5-38.5 38.5zm282.1 243h-66.4V312c0-24.8-.5-56.7-34.5-56.7-34.6 0-39.9 27-39.9 54.9V416h-66.4V202.2h63.7v29.2h.9c8.9-16.8 30.6-34.5 62.9-34.5 67.2 0 79.7 44.3 79.7 101.9V416z">
                            </path>
                        </svg>
                        <span>Linkedin</span>
                    </a></li>
                <li><a href="#" class="flex items-center gap-2 text-[#464646]">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="currentColor"
                            stroke="currentColor" class="size-8">
                            <path
                                d="M61.7 169.4l101.5 278C92.2 413 43.3 340.2 43.3 256c0-30.9 6.6-60.1 18.4-86.6zm337.9 75.9c0-26.3-9.4-44.5-17.5-58.7-10.8-17.5-20.9-32.4-20.9-49.9 0-19.6 14.8-37.8 35.7-37.8.9 0 1.8.1 2.8.2-37.9-34.7-88.3-55.9-143.7-55.9-74.3 0-139.7 38.1-177.8 95.9 5 .2 9.7.3 13.7.3 22.2 0 56.7-2.7 56.7-2.7 11.5-.7 12.8 16.2 1.4 17.5 0 0-11.5 1.3-24.3 2l77.5 230.4L249.8 247l-33.1-90.8c-11.5-.7-22.3-2-22.3-2-11.5-.7-10.1-18.2 1.3-17.5 0 0 35.1 2.7 56 2.7 22.2 0 56.7-2.7 56.7-2.7 11.5-.7 12.8 16.2 1.4 17.5 0 0-11.5 1.3-24.3 2l76.9 228.7 21.2-70.9c9-29.4 16-50.5 16-68.7zm-139.9 29.3l-63.8 185.5c19.1 5.6 39.2 8.7 60.1 8.7 24.8 0 48.5-4.3 70.6-12.1-.6-.9-1.1-1.9-1.5-2.9l-65.4-179.2zm183-120.7c.9 6.8 1.4 14 1.4 21.9 0 21.6-4 45.8-16.2 76.2l-65 187.9C426.2 403 468.7 334.5 468.7 256c0-37-9.4-71.8-26-102.1zM504 256c0 136.8-111.3 248-248 248C119.2 504 8 392.7 8 256 8 119.2 119.2 8 256 8c136.7 0 248 111.2 248 248zm-11.4 0c0-130.5-106.2-236.6-236.6-236.6C125.5 19.4 19.4 125.5 19.4 256S125.6 492.6 256 492.6c130.5 0 236.6-106.1 236.6-236.6z">
                            </path>
                        </svg>
                        <span>WordPress</span>
                    </a></li>
                <li><a href="#" class="flex items-center gap-2 text-[#0a5c5d]">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" fill="currentColor"
                            stroke="currentColor" class="size-8">
                            <path
                                d="M162.7 210c-1.8 3.3-25.2 44.4-70.1 123.5-4.9 8.3-10.8 12.5-17.7 12.5H9.8c-7.7 0-12.1-7.5-8.5-14.4l69-121.3c.2 0 .2-.1 0-.3l-43.9-75.6c-4.3-7.8.3-14.1 8.5-14.1H100c7.3 0 13.3 4.1 18 12.2l44.7 77.5zM382.6 46.1l-144 253v.3L330.2 466c3.9 7.1.2 14.1-8.5 14.1h-65.2c-7.6 0-13.6-4-18-12.2l-92.4-168.5c3.3-5.8 51.5-90.8 144.8-255.2 4.6-8.1 10.4-12.2 17.5-12.2h65.7c8 0 12.3 6.7 8.5 14.1z">
                            </path>
                        </svg>
                        <span>Xing</span>
                    </a></li>
            </ul>
        </div>
    </div>
    <!-- footer end -->


</body>

</html>
