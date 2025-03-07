@extends('layouts.guest')

@section('content')
<div class="max-w-6xl mx-auto my-10">
    <div class="flex">
        <div class="w-1/4">
            <div class="mr-20 mb-5">
                <div class="relative flex items-center gap-2">
                    <span class="material-symbols-rounded text-gray-200">search</span>
                    <input type="text" placeholder="Search docs..."
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
                <a href="{{ route('docsingle', ['package' => 'job-monitor']) }}">
                    <img src="{{ asset('web/package.jpg') }}" alt="Moox Jobs" class="w-full">
                </a>

                <div class="p-10">
                    <h2 class="text-slate-300 text-2xl font-bold mb-5">Moox Jobs</h2>
                    <p class="text-slate-300 mb-10">
                        Moox Jobs is a job board package for Moox. It allows you to create a job board and manage your jobs.
                    </p>
                    <p class="text-slate-300 mb-10">
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                    </p>
                <img src="https://github.com/mooxphp/moox/raw/main/art/screenshot/jobs-jobs.jpg" alt="Moox Jobs" class="w-full mb-10">

                <h2 class="text-slate-300 text-2xl font-bold mb-5">Installation</h2>
                <p class="text-slate-300 mb-10">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                </p>

                <h2 class="text-slate-300 text-2xl font-bold mb-5">Configuration</h2>
                <p class="text-slate-300 mb-10">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                </p>

                <h2 class="text-slate-300 text-2xl font-bold mb-5">Usage</h2>
                <p class="text-slate-300 mb-10">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
