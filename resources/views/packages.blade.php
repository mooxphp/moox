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
                        <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20">7</span>
                    </a>
                </li>
                <li class="mb-4">
                    <a href="#" class="text-gray-200 flex items-center gap-2">
                        <span class="material-symbols-rounded">category</span>
                        Taxonomy
                        <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20">3</span>
                    </a>
                </li>
                <li class="mb-4">
                    <a href="#" class="text-gray-200 flex items-center gap-2">
                        <span class="material-symbols-rounded">rocket_launch</span>
                        Builder
                        <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20">2</span>
                    </a>
                </li>
                <li class="mb-4">
                    <a href="#" class="text-gray-200 flex items-center gap-2">
                        <span class="material-symbols-rounded">settings</span>
                        System
                        <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20">1</span>
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
                        <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20">1</span>
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
                        Devops
                        <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20">4</span>
                    </a>
                </li>
                <li class="mb-4">
                    <a href="#" class="text-gray-200 flex items-center gap-2">
                        <span class="material-symbols-rounded">perm_media</span>
                        Media
                        <span class="bg-pink-500/20 text-pink-200 text-xs px-2 py-0.5 rounded-full border border-pink-500/20">1</span>
                    </a>
                </li>
            </ul>
            <div class="mb-10">
                <div class="flex items-center gap-3 group mb-3">
                    <div class="relative">
                        <input type="checkbox" id="beta" class="peer appearance-none w-5 h-5 border border-gray-500 rounded bg-gray-800/50 hover:bg-gray-800 checked:bg-gradient-to-r checked:from-pink-600 checked:to-violet-600 checked:border-transparent transition-all duration-200">
                        <span class="material-symbols-rounded absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-gray-200 opacity-0 peer-checked:opacity-100 transition-opacity duration-200 pointer-events-none text-sm">check</span>
                    </div>
                    <label for="beta" class="text-gray-400 group-hover:text-gray-200 transition-colors duration-200 select-none">Beta Releases</label>
                </div>
                <div class="flex items-center gap-3 group">
                    <div class="relative">
                        <input type="checkbox" id="alpha" class="peer appearance-none w-5 h-5 border border-gray-500 rounded bg-gray-800/50 hover:bg-gray-800 checked:bg-gradient-to-r checked:from-pink-600 checked:to-violet-600 checked:border-transparent transition-all duration-200">
                        <span class="material-symbols-rounded absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-gray-200 opacity-0 peer-checked:opacity-100 transition-opacity duration-200 pointer-events-none text-sm">check</span>
                    </div>
                    <label for="alpha" class="text-gray-400 group-hover:text-gray-200 transition-colors duration-200 select-none">Alpha Releases</label>
                </div>
            </div>
            <div class="max-w-6xl mx-auto my-10 mr-20">
                <img src="{{ asset('web/73-delivery-2.png') }}" alt="Moox Logo" class="w-full">
            </div>
        </div>
        <div class="w-3/4">
            <div class="bg-slate-950/60 rounded-lg mb-6 border border-pink-500/20 shadow-[0px_-4px_15px_-5px_rgba(139,92,246,0.5),0px_4px_15px_-5px_rgba(236,72,153,0.5)]">
                <div class="flex gap-6">
                    <div class="w-3/5">
                        <a href="{{ route('docs', ['package' => 'job-monitor']) }}">
                            <img src="https://github.com/mooxphp/moox/raw/main/art/screenshot/jobs-jobs.jpg" alt="Moox Jobs" class="w-full rounded">
                        </a>
                    </div>
                    <div class="w-2/5 p-6">
                        <h2 class="text-gray-200 text-2xl font-bold mb-5">
                            Moox Core
                            <span class="bg-pink-500/20 text-pink-200 text-sm px-2 py-0.5 rounded-full border border-pink-500/20 ml-2">4.2.33</span>
                        </h2>
                        <p class="text-gray-300 mb-10">
                            Moox Core is the core package for Moox. It provides the core functionality for Moox.
                            <br>
                            <br>
                            Package Registry: Stability, Ships (Entity Type, Theme),
                            Category, Downloads, Stars, Alternate, Update.
                        </p>
                        <div class="flex gap-5">
                            <a href="#" class="text-gray-200 border px-2 py-1 border-pink-700">Learn More</a>
                            <a href="#" class="text-gray-200 h-8">
                                <img src="{{ asset('web/github.png') }}" alt="GitHub" class="h-8">
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional package cards... -->
        </div>
    </div>
</div>
@endsection
