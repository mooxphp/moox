@extends('layouts.guest')

@section('content')
<div class="max-w-6xl mx-auto my-10">
    <div class="flex">
        <div class="w-1/4">
            <div class="mr-20 mb-5">
                <div class="relative flex items-center gap-2">
                    <span class="material-symbols-rounded text-gray-200">search</span>
                    <input type="text" placeholder="Search components..."
                        class="w-full py-2 bg-transparent border-b border-pink-500/20 text-gray-200 placeholder-gray-400 focus:outline-none focus:border-pink-500/40">
                </div>
            </div>

            <div x-data="{ active: 'getting-started' }">
                <ul class="mb-10">
                    <li class="mb-4">
                        <a href="#" class="text-gray-200 flex items-center gap-2"
                            @click.prevent="active = active === 'getting-started' ? null : 'getting-started'">
                            <span class="material-symbols-rounded">rocket_launch</span>
                            Getting Started
                            <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20 ml-auto mr-20">
                                <span class="material-symbols-rounded text-sm" x-text="active === 'getting-started' ? 'expand_less' : 'expand_more'"></span>
                            </span>
                        </a>
                        <ul class="ml-8 mt-5 mb-7" x-show="active === 'getting-started'" x-collapse>
                            <li class="my-3">
                                <a href="#" class="text-gray-400 flex items-center gap-2">Introduction</a>
                            </li>
                            <li class="my-3">
                                <a href="#" class="text-gray-400 flex items-center gap-2">Installation</a>
                            </li>
                            <li class="my-3">
                                <a href="#" class="text-gray-400 flex items-center gap-2">Updates</a>
                            </li>
                            <li class="my-3">
                                <a href="#" class="text-gray-400 flex items-center gap-2">Configuration</a>
                            </li>
                        </ul>
                    </li>

                    <li class="mb-4">
                        <a href="#" class="text-gray-200 flex items-center gap-2"
                            @click.prevent="active = active === 'packages' ? null : 'packages'">
                            <span class="material-symbols-rounded">deployed_code</span>
                            Packages
                            <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20 ml-auto mr-20">
                                <span class="material-symbols-rounded text-sm" x-text="active === 'packages' ? 'expand_less' : 'expand_more'"></span>
                            </span>
                        </a>
                        <ul class="ml-8 mt-2" x-show="active === 'packages'" x-collapse>
                            <li class="mb-2">
                                <a href="#" class="text-gray-400 flex items-center gap-2">
                                    <span class="material-symbols-rounded text-sm">schema</span>
                                    Architecture
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="#" class="text-gray-400 flex items-center gap-2">
                                    <span class="material-symbols-rounded text-sm">extension</span>
                                    Plugins
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="mb-4">
                        <a href="#" class="text-gray-200 flex items-center gap-2"
                            @click.prevent="active = active === 'advanced' ? null : 'advanced'">
                            <span class="material-symbols-rounded">code</span>
                            Advanced
                            <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20 ml-auto mr-20">
                                <span class="material-symbols-rounded text-sm" x-text="active === 'advanced' ? 'expand_less' : 'expand_more'"></span>
                            </span>
                        </a>
                        <ul class="ml-8 mt-2" x-show="active === 'advanced'" x-collapse>
                            <li class="mb-2">
                                <a href="#" class="text-gray-400 flex items-center gap-2">
                                    <span class="material-symbols-rounded text-sm">api</span>
                                    API Reference
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="#" class="text-gray-400 flex items-center gap-2">
                                    <span class="material-symbols-rounded text-sm">terminal</span>
                                    CLI Commands
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="mb-4">
                        <a href="#" class="text-gray-200 flex items-center gap-2"
                            @click.prevent="active = active === 'support' ? null : 'support'">
                            <span class="material-symbols-rounded">help</span>
                            Support
                            <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20 ml-auto mr-20">
                                <span class="material-symbols-rounded text-sm" x-text="active === 'support' ? 'expand_less' : 'expand_more'"></span>
                            </span>
                        </a>
                        <ul class="ml-8 mt-2" x-show="active === 'support'" x-collapse>
                            <li class="mb-3">
                                <a href="#" class="text-gray-400 flex items-center gap-2">
                                    <span class="material-symbols-rounded text-sm">bug_report</span>
                                    Troubleshooting
                                </a>
                            </li>
                            <li class="mb-3">
                                <a href="#" class="text-gray-400 flex items-center gap-2">
                                    <span class="material-symbols-rounded text-sm">contact_support</span>
                                    FAQ
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>

        <div class="w-3/4">
            <div class="bg-slate-950/60 rounded-lg mb-6 border border-pink-500/20 shadow-[0px_-4px_15px_-5px_rgba(139,92,246,0.5),0px_4px_15px_-5px_rgba(236,72,153,0.5)]">

                <div class="p-10">

                    <h1 class="text-slate-300 text-2xl font-bold mb-5">Moox <Icons></Icons></h1>

                    <h2 class="text-slate-300 text-xl font-bold mb-5 mt-10">Flags Circle</h2>

                    <div class="flex flex-row gap-5 mb-5">
                        <x-flag-de class="w-10 h-10" />
                        <x-flag-gb class="w-10 h-10" />
                        <x-flag-us class="w-10 h-10" />
                        <x-flag-fr class="w-10 h-10" />
                        <x-flag-es class="w-10 h-10" />
                        <x-flag-it class="w-10 h-10" />
                        <x-flag-nl class="w-10 h-10" />
                        <x-flag-pl class="w-10 h-10" />
                        <x-flag-th class="w-10 h-10" />
                        <x-flag-ru class="w-10 h-10" />
                        <x-flag-tr class="w-10 h-10" />
                        <x-flag-ua class="w-10 h-10" />
                        <x-flag-cz class="w-10 h-10" />
                    </div>

                    <h2 class="text-slate-300 text-xl font-bold mb-5 mt-10">Flags Square (rounded-sm)</h2>

                    <div class="flex flex-row gap-4 mb-5">
                        <x-flags-de class="w-10" />
                        <x-flags-gb class="w-10" />
                        <x-flags-pl class="w-10" />
                        <x-flags-cz class="w-10" />
                        <x-flags-th class="w-10" />
                        <x-flags-ru class="w-10" />
                        <x-flags-tr class="w-10" />
                        <x-flags-ua class="w-10" />
                        <x-flags-fr class="w-10" />
                        <x-flags-es class="w-10" />
                        <x-flags-it class="w-10" />
                        <x-flags-nl class="w-10" />
                        <x-flags-pl class="w-10" />
                        <x-flags-cz class="w-10" />
                    </div>


                    <h2 class="text-slate-300 text-xl font-bold mb-5 mt-10">Flags Rect (rounded-md)</h2>

                    <div class="flex flex-row gap-5 mb-5">
                        <x-flagr-de class="w-10 h-10 rounded-md" />
                        <x-flagr-gb class="w-10 h-10 rounded-md" />
                        <x-flagr-us class="w-10 h-10 rounded-md" />
                        <x-flagr-fr class="w-10 h-10 rounded-md" />
                        <x-flagr-es class="w-10 h-10 rounded-md" />
                        <x-flagr-it class="w-10 h-10 rounded-md" />
                        <x-flagr-nl class="w-10 h-10 rounded-md" />
                        <x-flagr-be class="w-10 h-10 rounded-md" />
                        <x-flagr-se class="w-10 h-10 rounded-md" />
                        <x-flagr-pl class="w-10 h-10 rounded-md" />
                        <x-flagr-cz class="w-10 h-10 rounded-md" />
                        <x-flagr-ro class="w-10 h-10 rounded-md" />
                        <x-flagr-pt class="w-10 h-10 rounded-md" />
                    </div>

                    <h2 class="text-slate-300 text-xl font-bold mb-5 mt-10">Flags Origin</h2>

                    <div class="flex flex-row gap-4 mb-5">
                        <x-flago-de class="w-auto h-10" />
                        <x-flago-gb class="w-auto h-10" />
                        <x-flago-us class="w-auto h-10" />
                        <x-flago-fr class="w-auto h-10" />
                        <x-flago-es class="w-auto h-10" />
                        <x-flago-it class="w-auto h-10" />
                        <x-flago-nl class="w-auto h-10" />
                        <x-flago-be class="w-auto h-10" />
                        <x-flago-pl class="w-auto h-10" />
                        <x-flago-cz class="w-auto h-10" />
                    </div>


                    <h2 class="text-slate-300 text-xl font-bold mb-5 mt-10">Laravel Icons</h2>

                    <div class="flex flex-row gap-5 mb-5">
                        <x-laraicon-laravel />
                        <x-laraicon-breeze class="text-gray-600" />
                        <x-laraicon-cashier class="text-gray-600" />
                        <x-laraicon-cloud class="text-gray-600" />
                        <x-laraicon-dusk class="text-gray-600" />
                        <x-laraicon-echo class="text-gray-600" />
                        <x-laraicon-envoyer class="text-orange-600" />
                        <x-laraicon-vscode />
                    </div>

                    <div class="flex flex-row gap-5 mb-5">
                        <x-laraicon-filament class="text-amber-600" />
                        <x-laraicon-forge class="text-teal-600" />
                        <x-laraicon-horizon class="text-gray-600" />
                        <x-laraicon-jetstream class="text-indigo-600" />
                        <x-laraicon-livewire class="text-gray-600" />
                        <x-laraicon-nova class="text-gray-600" />
                        <x-laraicon-octane class="text-gray-600" />
                        <x-laraicon-react />
                    </div>

                    <div class="flex flex-row gap-5 mb-5">
                        <x-laraicon-pint class="text-gray-600" />
                        <x-laraicon-sail class="text-gray-600" />
                        <x-laraicon-sanctum class="text-gray-600" />
                        <x-laraicon-scout class="text-red-400" />
                        <x-laraicon-socialite class="text-gray-600" />
                        <x-laraicon-spark class="text-gray-600" />
                        <x-laraicon-telescope class="text-gray-600" />
                        <x-laraicon-svelte  />
                    </div>

                    <div class="flex flex-row gap-5 mb-5">
                        <x-laraicon-alpine />
                        <x-laraicon-moox class="text-violet-700" />
                        <x-laraicon-tailwind />
                        <x-laraicon-vite />
                        <x-laraicon-vue />
                        <x-laraicon-composer />
                        <x-laraicon-github />
                        <x-laraicon-lumen />
                    </div>

                    <div class="flex flex-row gap-5 mb-5">
                        <x-laraicon-git />
                        <x-laraicon-vapor class="text-gray-600" />
                        <x-laraicon-valet class="text-gray-600" />
                        <x-laraicon-php />
                        <x-laraicon-mysql />
                        <x-laraicon-postgresql />
                    </div>

                    <h2 class="text-slate-300 text-xl font-bold mb-5">File Icons</h2>

                    <div class="flex flex-row gap-5 mb-5">
                        <x-fileicon-jpg class="w-15 h-15 bg-white rounded-md p-5" />
                        <x-fileicon-png class="w-15 h-15 bg-white rounded-md p-5" />
                        <x-fileicon-html class="w-15 h-15 bg-white rounded-md p-5" />
                        <x-fileicon-txt class="w-15 h-15 bg-white rounded-md p-5" />
                        <x-fileicon-css class="w-15 h-15 bg-white rounded-md p-5" />
                        <x-fileicon-avi class="w-15 h-15 bg-white rounded-md p-5" />
                        <x-fileicon-doc class="w-15 h-15 bg-white rounded-md p-5" />
                    </div>

                    <div class="flex flex-row gap-5 mb-5">
                        <x-fileicon-psd class="w-15 h-15 bg-white rounded-md p-5" />
                        <x-fileicon-indd class="w-15 h-15 bg-white rounded-md p-5" />
                        <x-fileicon-ai class="w-15 h-15 bg-white rounded-md p-5" />
                        <x-fileicon-ae class="w-15 h-15 bg-white rounded-md p-5" />
                        <x-fileicon-ppj class="w-15 h-15 bg-white rounded-md p-5" />
                        <x-fileicon-html class="w-15 h-15 bg-white rounded-md p-5" />
                        <x-fileicon-js class="w-15 h-15 bg-white rounded-md p-5" />
                    </div>

                    <div class="flex flex-row gap-5 mb-5">
                        <x-fileicon-dwg class="w-15 h-15 bg-white rounded-md p-5" />
                        <x-fileicon-mp3 class="w-15 h-15 bg-white rounded-md p-5" />
                        <x-fileicon-mp4 class="w-15 h-15 bg-white rounded-md p-5" />
                        <x-fileicon-ppt class="w-15 h-15 bg-white rounded-md p-5" />
                        <x-fileicon-svg class="w-15 h-15 bg-white rounded-md p-5" />
                        <x-fileicon-xls class="w-15 h-15 bg-white rounded-md p-5" />
                        <x-fileicon-xml class="w-15 h-15 bg-white rounded-md p-5" />
                    </div>

                    <div class="flex flex-row gap-5 mb-5">
                        <x-fileicon-svg class="w-15 h-15 bg-white rounded-md p-5" />
                        <x-fileicon-pdf class="w-15 h-15 bg-white rounded-md p-5" />
                        <x-fileicon-cad class="w-15 h-15 bg-white rounded-md p-5" />
                        <x-fileicon-folder class="w-15 h-15 bg-white rounded-md p-5" />
                        <x-fileicon-zip class="w-15 h-15 bg-white rounded-md p-5" />
                        <x-fileicon-unknown class="w-15 h-15 bg-white rounded-md p-5" />
                        <x-fileicon-onedrive class="w-15 h-15 bg-white rounded-md p-5" />
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>


@endsection
