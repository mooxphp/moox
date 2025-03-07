@extends('layouts.guest')

@section('content')
<div class="max-w-6xl mx-auto my-10">
    <div class="flex">
        <div class="w-1/4">
            <ul class="mb-10">
                <li class="mb-4">
                    <a href="#" class="text-gray-200 flex items-center gap-2">
                        <span class="material-symbols-rounded">article</span>
                        Content
                        <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20">11</span>
                    </a>
                </li>
                <li class="mb-4">
                    <a href="#" class="text-gray-200 flex items-center gap-2">
                        <span class="material-symbols-rounded">perm_media</span>
                        Media
                        <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20">1</span>
                    </a>
                </li>
                <li class="mb-4">
                    <a href="#" class="text-gray-200 flex items-center gap-2">
                        <span class="material-symbols-rounded">tag</span>
                        Taxonomy
                        <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20">3</span>
                    </a>
                </li>
                <li class="mb-4">
                    <a href="#" class="text-gray-200 flex items-center gap-2">
                        <span class="material-symbols-rounded">shopping_cart</span>
                        Shop
                        <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20">8</span>
                    </a>
                </li>
                <li class="mb-4">
                    <a href="#" class="text-gray-200 flex items-center gap-2">
                        <span class="material-symbols-rounded">palette</span>
                        Theme
                        <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20">3</span>
                    </a>
                </li>
                <li class="mb-4">
                    <a href="#" class="text-gray-200 flex items-center gap-2">
                        <span class="material-symbols-rounded">extension</span>
                        Module
                        <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20">3</span>
                    </a>
                </li>
                <li class="mb-4">
                    <a href="#" class="text-gray-200 flex items-center gap-2">
                        <span class="material-symbols-rounded">settings</span>
                        System
                        <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20">7</span>
                    </a>
                </li>
                <li class="mb-4">
                    <a href="#" class="text-gray-200 flex items-center gap-2">
                        <span class="material-symbols-rounded">person</span>
                        User
                        <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20">6</span>
                    </a>
                </li>
                <li class="mb-4">
                    <a href="#" class="text-gray-200 flex items-center gap-2">
                        <span class="material-symbols-rounded">build</span>
                        Tools
                        <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20">6</span>
                    </a>
                </li>
                <li class="mb-4">
                    <a href="#" class="text-gray-200 flex items-center gap-2">
                        <span class="material-symbols-rounded">newspaper</span>
                        Press
                        <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20">2</span>
                    </a>
                </li>
                <li class="mb-4">
                    <a href="#" class="text-gray-200 flex items-center gap-2">
                        <span class="material-symbols-rounded">terminal</span>
                        DevOps
                        <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20">4</span>
                    </a>
                </li>
                <li class="mb-4">
                    <a href="#" class="text-gray-200 flex items-center gap-2">
                        <span class="material-symbols-rounded">rocket_launch</span>
                        Builder
                        <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20">7</span>
                    </a>
                </li>
                <li class="mb-4">
                    <a href="#" class="text-gray-200 flex items-center gap-2">
                        <span class="material-symbols-rounded">code</span>
                        Coding
                        <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20">4</span>
                    </a>
                </li>
                <li class="mb-4">
                    <a href="#" class="text-gray-200 flex items-center gap-2">
                        <span class="material-symbols-rounded">category</span>
                        Icons
                        <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20">2</span>
                    </a>
                </li>
            </ul>

            <div class="max-w-6xl mx-auto my-10 mr-20">
                <img src="{{ asset('web/73-delivery-2.png') }}" alt="Moox Logo" class="w-full">
            </div>
        </div>
        <div class="w-3/4">
            <div class="bg-slate-950/60 rounded-lg mb-6 border border-pink-500/20 shadow-[0px_-4px_15px_-5px_rgba(139,92,246,0.5),0px_4px_15px_-5px_rgba(236,72,153,0.5)]">
                <div class="flex gap-6">
                    <div class="w-2/5 p-6">
                        <div class="flex flex-row gap-2">
                            <h2 class="text-gray-200 text-2xl font-bold mb-5">
                                Press
                            </h2>
                            <div class=" h-5 mt-2 bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20 ml-2">4.2.1</div>
                        </div>
                        <p class="text-gray-300 mb-5 mr-5">
                            Moox Press connects your Laravel and Filament application with WordPress.
                        </p>
                        <div class="h-10 mt-10 mb-10"></div>
                        <div class="flex justify-between mr-3">
                            <div class="text-gray-400 text-center text-sm bg-slate-950/60 px-2 py-1 min-w-20 rounded-full px-1 py-1">
                                <span class="material-symbols-rounded">download</span>
                                4.7k
                            </div>
                            <div class="text-gray-400 text-center text-sm bg-slate-950/60 px-2 py-1 min-w-20 rounded-full px-1 py-1">
                                <span class="material-symbols-rounded">star</span>
                                175
                            </div>
                            <div class="text-gray-400 text-center text-sm bg-slate-950/60 px-2 py-1 min-w-20 rounded-full px-1 py-1">
                                <span class="material-symbols-rounded">schedule</span>
                                2d
                            </div>
                        </div>
                    </div>
                    <div class="w-3/5">
                        <a href="{{ route('docs', ['package' => 'job-monitor']) }}">
                            <img src="https://github.com/mooxphp/moox/raw/main/art/screenshot/jobs-jobs.jpg" alt="Moox Jobs" class="w-full rounded">
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-slate-950/60 rounded-lg mb-6 border border-pink-500/20 shadow-[0px_-4px_15px_-5px_rgba(139,92,246,0.5),0px_4px_15px_-5px_rgba(236,72,153,0.5)]">
                <div class="flex gap-6">
                    <div class="w-2/5 p-6">
                        <h2 class="text-gray-200 text-2xl font-bold mb-5">
                            Press
                            <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20 ml-2">4.2.1</span>
                        </h2>
                        <p class="text-gray-300 mb-5 mr-5">
                            Moox Press connects your Laravel and Filament application with WordPress.
                        </p>
                        <div class="flex gap-5">
                            <a href="#" class="text-gray-200 border px-2 py-1 border-pink-900">Learn More</a>
                            <a href="#" class="text-gray-200 h-8">
                                <img src="{{ asset('web/github.png') }}" alt="GitHub" class="h-8">
                            </a>
                        </div>
                        <div class="h-10 mt-5"></div>
                        <div class="flex justify-between mr-3">
                            <div class="text-gray-400 text-center text-sm bg-slate-950/60 px-2 py-1 min-w-20 rounded-full px-1 py-1">
                                <span class="material-symbols-rounded">download</span>
                                4.7k
                            </div>
                            <div class="text-gray-400 text-center text-sm bg-slate-950/60 px-2 py-1 min-w-20 rounded-full px-1 py-1">
                                <span class="material-symbols-rounded">star</span>
                                175
                            </div>
                            <div class="text-gray-400 text-center text-sm bg-slate-950/60 px-2 py-1 min-w-20 rounded-full px-1 py-1">
                                <span class="material-symbols-rounded">schedule</span>
                                2d
                            </div>
                        </div>
                    </div>
                    <div class="w-3/5">
                        <a href="{{ route('docs', ['package' => 'job-monitor']) }}">
                            <img src="https://github.com/mooxphp/moox/raw/main/art/screenshot/jobs-jobs.jpg" alt="Moox Jobs" class="w-full rounded">
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-slate-950/60 rounded-lg mb-6 border border-pink-500/20 shadow-[0px_-4px_15px_-5px_rgba(139,92,246,0.5),0px_4px_15px_-5px_rgba(236,72,153,0.5)]">
                <div class="flex gap-6">
                    <div class="w-3/5">
                        <a href="{{ route('docs', ['package' => 'job-monitor']) }}">
                            <img src="https://github.com/mooxphp/moox/raw/main/art/screenshot/jobs-jobs.jpg" alt="Moox Jobs" class="w-full rounded">
                        </a>
                    </div>
                    <div class="w-2/5 p-6">
                        <h2 class="text-gray-200 text-2xl font-bold mb-5">
                            Core
                            <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20 ml-2">4.2.33</span>
                            <span class="text-xs text-gray-400">beta</span>
                        </h2>
                        <p class="text-gray-300 mb-5">
                            Moox Core is the core package for Moox. It provides the core functionality for Moox.
                        </p>
                        <div class="flex gap-5">
                            <a href="#" class="text-gray-200 border px-2 py-1 border-pink-700">Learn More</a>
                            <a href="#" class="text-gray-200 h-8">
                                <img src="{{ asset('web/github.png') }}" alt="GitHub" class="h-8">
                            </a>
                        </div>
                        <div class="h-10 mt-5 mb-5"></div>
                        <div class="flex justify-between mr-10">
                            <span class="text-gray-400 text-sm">
                                <span class="material-symbols-rounded">download</span>
                                4k
                            </span>
                            <span class="text-gray-400 text-sm">
                                <span class="material-symbols-rounded">star</span>
                                17
                            </span>
                            <span class="text-gray-400 text-sm">
                                <span class="material-symbols-rounded">schedule</span>
                                2d
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-slate-950/60 rounded-lg mb-6 border border-pink-500/20 shadow-[0px_-4px_15px_-5px_rgba(139,92,246,0.5),0px_4px_15px_-5px_rgba(236,72,153,0.5)]">
                <div class="flex gap-6">
                    <div class="w-3/5">
                        <a href="{{ route('docs', ['package' => 'job-monitor']) }}">
                            <img src="https://github.com/mooxphp/moox/raw/main/art/screenshot/jobs-jobs.jpg" alt="Moox Jobs" class="w-full rounded">
                        </a>
                    </div>
                    <div class="w-2/5 p-6">
                        <h2 class="text-gray-200 text-2xl font-bold mb-5">
                            Jobs
                            <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20 ml-2">4.2.33</span>
                            <span class="text-xs text-gray-400">dev</span>
                        </h2>
                        <p class="text-gray-300 mb-5">
                            Moox Core is the core package for Moox. It provides the core functionality for Moox.
                        </p>
                        <div class="flex gap-5">
                            <a href="#" class="text-gray-200 border px-2 py-1 border-pink-700">Learn More</a>
                            <a href="#" class="text-gray-200 h-8">
                                <img src="{{ asset('web/github.png') }}" alt="GitHub" class="h-8">
                            </a>
                        </div>
                        <div class="h-10 mt-5 mb-5"></div>
                        <div class="flex justify-between mr-10">
                            <span class="text-gray-400 text-sm">
                                <span class="material-symbols-rounded">download</span>
                                4k
                            </span>
                            <span class="text-gray-400 text-sm">
                                <span class="material-symbols-rounded">star</span>
                                17
                            </span>
                            <span class="text-gray-400 text-sm">
                                <span class="material-symbols-rounded">schedule</span>
                                2d
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-slate-950/60 rounded-lg mb-6 border border-pink-500/20 shadow-[0px_-4px_15px_-5px_rgba(139,92,246,0.5),0px_4px_15px_-5px_rgba(236,72,153,0.5)]">
                <div class="flex gap-6">
                    <div class="w-3/5">
                        <a href="{{ route('docs', ['package' => 'job-monitor']) }}">
                            <img src="https://github.com/mooxphp/moox/raw/main/art/screenshot/jobs-jobs.jpg" alt="Moox Jobs" class="w-full rounded">
                        </a>
                    </div>
                    <div class="w-2/5 p-6">
                        <h2 class="text-gray-200 text-2xl font-bold mb-5">
                            User Session
                            <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20 ml-2">4.2.33</span>
                            <span class="text-xs text-gray-400">dev</span>
                        </h2>
                        <p class="text-gray-300 mb-5">
                            Moox Core is the core package for Moox. It provides the core functionality for Moox.
                        </p>
                        <div class="flex gap-5">
                            <a href="#" class="text-gray-200 border px-2 py-1 border-pink-700">Learn More</a>
                            <a href="#" class="text-gray-200 h-8">
                                <img src="{{ asset('web/github.png') }}" alt="GitHub" class="h-8">
                            </a>
                        </div>
                        <div class="h-10 mt-5 mb-5"></div>
                        <div class="flex justify-between mr-10">
                            <span class="text-gray-400 text-sm">
                                <span class="material-symbols-rounded">download</span>
                                4k
                            </span>
                            <span class="text-gray-400 text-sm">
                                <span class="material-symbols-rounded">star</span>
                                17
                            </span>
                            <span class="text-gray-400 text-sm">
                                <span class="material-symbols-rounded">schedule</span>
                                2d
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-slate-950/60 rounded-lg mb-6 border border-pink-500/20 shadow-[0px_-4px_15px_-5px_rgba(139,92,246,0.5),0px_4px_15px_-5px_rgba(236,72,153,0.5)]">
                <div class="flex gap-6">
                    <div class="w-3/5">
                        <a href="{{ route('docs', ['package' => 'job-monitor']) }}">
                            <img src="https://github.com/mooxphp/moox/raw/main/art/screenshot/jobs-jobs.jpg" alt="Moox Jobs" class="w-full rounded">
                        </a>
                    </div>
                    <div class="w-2/5 p-6">
                        <h2 class="text-gray-200 text-2xl font-bold mb-5">
                            User
                            <span class="bg-orange-500/20 text-orange-200 text-xs px-2 py-0.5 rounded-full border border-orange-500/20 ml-2">4.2.33</span>
                            <span class="text-xs text-gray-400">dev</span>
                        </h2>
                        <p class="text-gray-300 mb-5">
                            Moox Core is the core package for Moox. It provides the core functionality for Moox.
                            <br><br>
                            <a href="#" class="text-gray-300">Learn More</a>
                        </p>
                        <div class="h-10 mt-5 mb-5"></div>
                        <div class="flex justify-between mr-10">
                            <span class="text-gray-400 text-sm">
                                <span class="material-symbols-rounded">download</span>
                                4k
                            </span>
                            <span class="text-gray-400 text-sm">
                                <span class="material-symbols-rounded">star</span>
                                17
                            </span>
                            <span class="text-gray-400 text-sm">
                                <span class="material-symbols-rounded">schedule</span>
                                2d
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-slate-950/60 rounded-lg mb-6 border border-pink-500/20 shadow-[0px_-4px_15px_-5px_rgba(139,92,246,0.5),0px_4px_15px_-5px_rgba(236,72,153,0.5)]">
                <div class="flex gap-6">
                    <div class="w-3/5">
                        <a href="{{ route('docs', ['package' => 'job-monitor']) }}">
                            <img src="https://github.com/mooxphp/moox/raw/main/art/screenshot/jobs-jobs.jpg" alt="Moox Jobs" class="w-full rounded">
                        </a>
                    </div>
                    <div class="w-2/5 p-6">
                        <h2 class="text-gray-200 text-2xl font-bold mb-5">
                            Theme Light
                            <span class="bg-red-500/20 text-red-200 text-xs px-2 py-0.5 rounded-full border border-red-500/20 ml-2">4.2.33</span>
                        </h2>
                        <p class="text-gray-300 mb-5">
                            Moox Core is the core package for Moox. It provides the core functionality for Moox.
                        <div class="h-10 mt-5 mb-5">
                            <span class="text-gray-300">
                                Learn More
                            </span>
                            <span class="pl-1 text-gray-300 text-xs">
                                <span class="material-symbols-rounded text-sm">arrow_forward</span>
                            </span>
                        </div>
                        </p>
                        <div class="h-10 mt-5 mb-5"></div>
                        <div class="flex justify-between mr-10">
                            <span class="text-gray-400 text-sm">
                                <span class="material-symbols-rounded">download</span>
                                4k
                            </span>
                            <span class="text-gray-400 text-sm">
                                <span class="material-symbols-rounded">star</span>
                                17
                            </span>
                            <span class="text-gray-400 text-sm">
                                <span class="material-symbols-rounded">schedule</span>
                                2d
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-slate-950/60 rounded-lg mb-6 border border-pink-500/20 shadow-[0px_-4px_15px_-5px_rgba(139,92,246,0.5),0px_4px_15px_-5px_rgba(236,72,153,0.5)]">
                <div class="flex gap-6">
                    <div class="w-3/5">
                        <a href="{{ route('docs', ['package' => 'job-monitor']) }}">
                            <img src="https://github.com/mooxphp/moox/raw/main/art/screenshot/jobs-jobs.jpg" alt="Moox Jobs" class="w-full rounded">
                        </a>
                    </div>
                    <div class="w-2/5 p-6">
                        <h2 class="text-gray-200 text-2xl font-bold mb-5">
                            Backup Server
                            <span class="bg-green-500/20 text-green-200 text-xs px-2 py-0.5 rounded-full border border-green-500/20 ml-2">4.2.33</span>
                        </h2>
                        <p class="text-gray-300 mb-5">
                            Moox Core is the core package for Moox. It provides the core functionality for Moox.
                        </p>
                        <div class="flex gap-5">
                            <a href="#" class="text-gray-200 border px-2 py-1 border-pink-700">Learn More</a>
                            <a href="#" class="text-gray-200 h-8">
                                <img src="{{ asset('web/github.png') }}" alt="GitHub" class="h-8">
                            </a>
                        </div>
                        <div class="h-10 mt-5 mb-5"></div>
                        <div class="flex justify-between mr-10">
                            <span class="text-gray-400 text-sm">
                                <span class="material-symbols-rounded">download</span>
                                4k
                            </span>
                            <span class="text-gray-400 text-sm">
                                <span class="material-symbols-rounded">star</span>
                                17
                            </span>
                            <span class="text-gray-400 text-sm">
                                <span class="material-symbols-rounded">schedule</span>
                                2d
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Additional package cards... -->
        </div>
    </div>
</div>
@endsection
