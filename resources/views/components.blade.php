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

                <div class="flex flex-row justify-end pt-10 pr-10">
                    <select class="bg-slate-950/60 rounded-lg border border-pink-500/20 shadow-[0px_-4px_15px_-5px_rgba(139,92,246,0.5),0px_4px_15px_-5px_rgba(236,72,153,0.5)]">
                        <option value="renderless">No Theme</option>
                        <option value="moox_base">Moox Base</option>
                        <option value="featherlight">Featherlight</option>
                    </select>
                </div>

                <div class="p-10">
                    <h2 class="text-slate-300 text-2xl font-bold mb-5">Buttons</h2>
                    <p class="text-slate-300 mb-10">
                        Buttons can be used as link or form button.
                    </p>

                    @php
                        $buttonCode =
'<x-moox-button icon="arrow_forward">
Click Me
</x-moox-button>';
                    @endphp

                    <x-component-viewer :code="$buttonCode" />


                    <x-moox-button icon="arrow_forward">
                        Click Me
                    </x-moox-button>

                    <br>
                    <br>

                    <h2 class="text-slate-300 text-xl font-bold mb-5">Flags Circle</h2>

                    <div class="flex flex-row gap-3 mb-5">
                        <x-flag-de class="w-6 h-6" />
                        <x-flag-gb class="w-6 h-6" />
                        <x-flag-pl class="w-6 h-6" />
                        <x-flag-cz class="w-6 h-6" />
                    </div>

                    <h2 class="text-slate-300 text-xl font-bold mb-5">Flags Square (rounded-sm)</h2>

                    <div class="flex flex-row gap-3 mb-5">
                        <x-flags-de class="w-6 h-auto rounded-sm" />
                        <x-flags-gb class="w-6 h-auto rounded-sm" />
                        <x-flags-pl class="w-6 h-auto rounded-sm" />
                        <x-flags-cz class="w-6 h-auto rounded-sm" />
                    </div>


                    <h2 class="text-slate-300 text-xl font-bold mb-5">Flags Rect (rounded-md)</h2>

                    <div class="flex flex-row gap-3 mb-5">
                        <x-flagr-de class="w-6 h-6 rounded-md" />
                        <x-flagr-gb class="w-6 h-6 rounded-md" />
                        <x-flagr-pl class="w-6 h-6 rounded-md" />
                        <x-flagr-cz class="w-6 h-6 rounded-md" />
                    </div>

                    <h2 class="text-slate-300 text-xl font-bold mb-5">Flags Origin</h2>

                    <div class="flex flex-row gap-3 mb-5">
                        <x-flago-de class="w-auto h-6" />
                        <x-flago-gb class="w-auto h-6" />
                        <x-flago-pl class="w-auto h-6" />
                        <x-flago-cz class="w-auto h-6" />
                    </div>


                    <h2 class="text-slate-300 text-xl font-bold mb-5">Laravel Icons</h2>

                    <div class="flex flex-row gap-3 mb-5">
                        <x-laraicon-breeze class="w-6 h-6" />
                        <x-laraicon-cashier class="w-auto h-6" />
                        <x-laraicon-dusk class="w-auto h-6" />
                        <x-laraicon-echo class="w-auto h-6" />
                        <x-laraicon-envoyer class="w-auto h-6" />
                        <x-laraicon-forge class="w-auto h-6" />
                        <x-laraicon-horizon class="w-auto h-6" />
                        <x-laraicon-jetstream class="w-auto h-6" />
                        <x-laraicon-laravel class="w-auto h-6" />
                        <x-laraicon-nova class="w-auto h-6" />
                        <x-laraicon-octane class="w-auto h-6" />
                        <x-laraicon-pint class="w-auto h-6" />
                        <x-laraicon-sail class="w-auto h-6" />
                        <x-laraicon-sanctum class="w-auto h-6" />
                        <x-laraicon-scout class="w-auto h-6" />
                        <x-laraicon-socialite class="w-auto h-6" />
                        <x-laraicon-spark class="w-auto h-6" />
                        <x-laraicon-telescope class="w-auto h-6" />
                        <x-laraicon-valet class="w-auto h-6" />
                        <x-laraicon-vapor class="w-auto h-6" />
                    </div>

                        <h2 class="text-slate-300 text-xl font-bold mb-5">File Icons</h2>

                    <div class="flex flex-row gap-3 mb-5">
                        <x-fileicon-acrobat class="w-6 h-6" />
                        <x-fileicon-cad class="w-6 h-6" />
                        <x-fileicon-excel class="w-6 h-6" />
                        <x-fileicon-folder class="w-6 h-6" />
                        <x-fileicon-illustrator class="w-6 h-6" />
                        <x-fileicon-indesign class="w-6 h-6" />
                        <x-fileicon-onedrive class="w-6 h-6" />
                        <x-fileicon-outlook class="w-6 h-6" />
                        <x-fileicon-photoshop class="w-6 h-6" />
                        <x-fileicon-powerpoint class="w-6 h-6" />
                        <x-fileicon-video class="w-6 h-6" />
                        <x-fileicon-word class="w-6 h-6" />
                        <x-fileicon-zip class="w-6 h-6" />
                        <x-fileicon-zip2 class="w-6 h-6" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection
